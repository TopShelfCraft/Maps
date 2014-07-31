<?php
namespace Craft;

class ChargeController extends BaseController
{
	protected $allowAnonymous = true;
	private $charge;
	private $plugin;

	public function init()
	{
		$this->plugin = craft()->plugins->getPlugin('charge');

		if (!$this->plugin)
		{
			throw new Exception('Couldn’t find the Charge plugin!');
		}
	}



    public function actionView(array $variables = array())
    {
        $chargeId = $variables['chargeId'];

        $charge = craft()->charge->getChargeById($chargeId);

        if($charge == null) $this->redirect('charge');

        $variables['charge'] = $charge;

        $this->renderTemplate('charge/payment/_view', $variables);
    }



	public function actionCharge()
	{
		$this->requirePostRequest();

		$this->charge = new ChargeModel();

		$this->_collectData();

		$settings = $this->plugin->getSettings();

		if($this->charge->validate())
		{
			if (craft()->charge->handlePayment($this->charge)) {
				$this->redirectToPostedUrl($this->charge);
			} else {

				if(!empty(craft()->charge->errors)) {
					foreach(craft()->charge->errors as $error) {
						$this->charge->addError('general', $error);
					}
				} else {
					$this->charge->addError('general', 'There was a problem with payment');
				}

				// Also remove any card details
				$this->charge->cardToken = null;
				$this->charge->cardLast4 = null;
				$this->charge->cardType = null;

				if(isset($this->charge->planAmount) AND is_numeric($this->charge->planAmount)) {
					$this->charge->planAmount = $this->charge->planAmount / 100;
				}

			}
		}
		else
		{
			$this->charge->addError('general', 'There was a problem with your details, please check the form and try again');
		}

		$errors = array();
		foreach($this->charge->getErrors() as $key => $errs) {
			foreach($errs as $error)
			{
				if($key != 'general')	$errors[] = $key . ' : ' . $error;
				else $errors[] = $error;
			}
		}

		craft()->urlManager->setRouteVariables(array(
			'charge' => $this->charge,
			'allErrors' => $errors
		));
	}




	public function actionDetails()
	{
		craft()->userSession->requirePermission('accessPlugin-Charge');
		$this->requirePostRequest();

		$chargeId = craft()->request->getPost('chargeId');
		$notes = craft()->request->getPost('notes');

		$details = array('notes' => $notes);

		if(craft()->charge->updateChargeDetails($chargeId, $details))
		{
			craft()->userSession->setNotice(Craft::t('Details updated.'));
      	} else {
            craft()->userSession->setError(Craft::t('Couldn\'t update item details.'));
        }

    	$this->redirectToPostedUrl();
	}



	private function _collectData()
	{
		$this->charge->cardToken 			= craft()->request->getPost('cardToken');
		$this->charge->cardLast4 			= craft()->request->getPost('cardLast4');
		$this->charge->cardType 			= craft()->request->getPost('cardType');
        $this->charge->cardName     		= craft()->request->getPost('cardName');
        $this->charge->cardExpMonth     	= craft()->request->getPost('cardExpMonth');
        $this->charge->cardExpYear     		= craft()->request->getPost('cardExpYear');
        $this->charge->cardAddressLine1     = craft()->request->getPost('cardAddressLine1');
        $this->charge->cardAddressLine2     = craft()->request->getPost('cardAddressLine2');
        $this->charge->cardAddressCity      = craft()->request->getPost('cardAddressCity');
        $this->charge->cardAddressState     = craft()->request->getPost('cardAddressState');
        $this->charge->cardAddressZip       = craft()->request->getPost('cardAddressZip');
        $this->charge->cardAddressCountry   = craft()->request->getPost('cardAddressCountry');

		$this->charge->customerName 		= craft()->request->getPost('customerName');
		$this->charge->customerEmail 		= craft()->request->getPost('customerEmail');

		$this->charge->planAmount 			= craft()->request->getPost('planAmount');
		$this->charge->planInterval 		= craft()->request->getPost('planInterval');
		$this->charge->planIntervalCount 	= craft()->request->getPost('planIntervalCount');
		$this->charge->planCurrency 		= craft()->request->getPost('planCurrency');
		$this->charge->planCoupon			= craft()->request->getPost('planCoupon');
		$this->charge->planName				= craft()->request->getPost('planName');

		$this->charge->description 			= craft()->request->getPost('description');

	}
}
