<?php
namespace Craft;

class Charge_CouponController extends BaseController
{
	protected $allowAnonymous = true;

   	public function actionAll(array $variables = array())
    {
		$variables['coupons'] = craft()->charge_coupon->getAll();

        $this->renderTemplate('charge/coupon/_index', $variables);
    }


    public function actionDeleteCoupon()
    {
		$this->requirePostRequest();
		$this->requireAjaxRequest();
		craft()->userSession->requireAdmin();

        $id = craft()->request->getRequiredPost('id');
     	$return = craft()->charge_coupon->deleteCouponById($id);

        return $this->returnJson(array('success' => $return));
    }

   	public function actionEdit(array $variables = array())
    {
		craft()->userSession->requireAdmin();
		$charge = new ChargePlugin;

		if(!isset($variables['coupon'])) {

	    	if(isset($variables['couponId'])) {
	    		$couponId = $variables['couponId'];
	    		$variables['coupon'] = craft()->charge_coupon->getCouponById($couponId);
	    	} else {
	    		// New coupon, load a blank object
	    		$variables['coupon'] = new Charge_CouponModel();
	    	}
		}

		$variables['paymentTypes'] = array('one-off' => 'One-Off','recurring' => 'Recurring');
		$variables['couponTypes'] = array('percentage' => 'Percentage Off', 'amount' => 'Fixed Amount');
		$variables['durations'] = array('once' => 'Once', 'forever' => 'Forever', 'repeating' => 'Repeating');

		foreach($charge->getCurrencies() as $key => $row)
		{
			$variables['currencies'][$key] = strtoupper($key) . ' - ' .$row['name'];
		}


		// Revert the coupon amount just in case
        if($variables['coupon']->amountOff > 0){
        	$variables['coupon']->amountOff = number_format($variables['coupon']->amountOff / 100, 2);
        }

        $this->renderTemplate('charge/coupon/_settings', $variables);
    }




    public function actionSave(array $variables = array())
    {
		craft()->userSession->requireAdmin();
		$this->requirePostRequest();

		$existingCouponId = craft()->request->getPost('couponId');

		if ($existingCouponId)
		{
			$coupon = craft()->charge_coupon->getCouponById($existingCouponId);
		}
		else
		{
			$coupon = new Charge_CouponModel();
		}

		$coupon->stripeId = craft()->request->getPost('stripeId');
		$coupon->name = craft()->request->getPost('name');
		$coupon->code = craft()->request->getPost('code');
		$coupon->paymentType = craft()->request->getPost('paymentType');
		$coupon->couponType = craft()->request->getPost('couponType');
        $coupon->percentageOff = craft()->request->getPost('percentageOff');
        $coupon->amountOff = craft()->request->getPost('amountOff');
        $coupon->currency = craft()->request->getPost('currency');
        $coupon->duration = craft()->request->getPost('duration');
        $coupon->durationInMonths = craft()->request->getPost('durationInMonths');
        $coupon->maxRedemptions = craft()->request->getPost('maxRedemptions');
        $coupon->redeemBy = craft()->request->getPost('redeemBy');

        // amountOff is passed as a double. Turn it into cents/pence
        if($coupon->amountOff > 0){
        	$coupon->amountOff = floor($coupon->amountOff * 100);
        }

		// Did it save?
		if (craft()->charge_coupon->saveCoupon($coupon))
		{
			craft()->userSession->setNotice(Craft::t('Coupon saved.'));
			$this->redirectToPostedUrl();
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t save coupon.'));
		}

		// Revert the coupon amount just in case
        if($coupon->amountOff > 0){
        	$coupon->amountOff = $coupon->amountOff / 100;
        }

		// Send the source back to the template
		craft()->urlManager->setRouteVariables(array(
			'coupon' => $coupon
		));
	}




}
