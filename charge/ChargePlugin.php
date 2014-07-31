<?php
namespace Craft;

class ChargePlugin extends BasePlugin
{
	function getName()
	{
		return Craft::t('Charge');
	}

	function getVersion()
	{
		return '1.3.5';
	}

	function getDeveloper()
	{
		return 'Square Bit';
	}

	function getDeveloperUrl()
	{
		return 'http://squarebit.co.uk';
	}

	public function hasCpSection()
    {
        return true;
    }

    public function registerCpRoutes()
    {
        return array(
            'charge/detail/(?P<chargeId>\d+)' 	=> array('action' => 'charge/view'),
            'charge/coupons' 					=> array('action' => 'charge/coupon/all'),
            'charge/coupons/new' 				=> array('action' => 'charge/coupon/edit'),
            'charge/coupons/(?P<couponId>\d+)' 	=> array('action' => 'charge/coupon/edit')
        );

    }

	protected function defineSettings()
	{
		return array(
			'stripeAccountMode'			=> array(AttributeType::String, 'required' => true),
			'stripeTestCredentialsSK' 	=> array(AttributeType::String, 'required' => true),
			'stripeTestCredentialsPK' 	=> array(AttributeType::String, 'required' => true),
			'stripeLiveCredentialsSK' 	=> array(AttributeType::String, 'required' => true),
			'stripeLiveCredentialsPK' 	=> array(AttributeType::String, 'required' => true),
			'stripeDefaultCurrency' 	=> array(AttributeType::String, 'required' => true),
		);
	}

	public function getSettingsHtml()
	{
		$currencies = array();

		foreach($this->getCurrencies('all') as $key => $currency) {
			$currencies[strtoupper($key)] = strtoupper($key) . ' - '. $currency['name'];
		}


		return craft()->templates->render('charge/_settings', array(
			'settings' => $this->getSettings(),
			'currencies' => $currencies,
			'accountModes'	=> array('test' => 'Test Mode', 'live' => 'Live Mode')
		));
	}

	public function getCurrencies($key = 'all')
	{
		$defaultCurrency = 'usd';

		$supportedCurrencies = array(  		'usd' => array('name' => 'American Dollar', 'symbol' => '&#36;', 'symbol_long' => 'US&#36;', 'default' => true),
                                            'gbp' => array('name' => 'British Pound Sterling', 'symbol' => '&#163;', 'symbol_long' => '&#163;'),
                                            'eur' => array('name' => 'Euro', 'symbol' => '&#128;', 'symbol_long' => '&#128;'),
                                            'cad' => array('name' => 'Canadian Dollars', 'symbol' => '&#36;', 'symbol_long' => 'CA&#36;'),
                                            'aud' => array('name' => 'Australian Dollar', 'symbol' => '&#36;', 'symbol_long' => 'AU&#36;'),
                                            'hkd' => array('name' => 'Hong Kong Dollar', 'symbol' => '&#36;', 'symbol_long' => 'HK&#36;'),
                                            'sek' => array('name' => 'Swedish Krona', 'symbol' => ':-', 'symbol_long' => 'kr'),
                                            'dkk' => array('name' => 'Danish Krone', 'symbol' => ',-', 'symbol_long' => 'dkr'),
                                            'pen' => array('name' => 'Peruvian Nuevo Sol', 'symbol' => 'S/.', 'symbol_long' => 'S/.'),
                                            'jpy' => array('name' => 'Japanese Yen', 'symbol' => '&#165;', 'symbol_long' => '&#165;') );

		if($key == 'all') return $supportedCurrencies;

		if(!isset($supportedCurrencies[$key])) return $supportedCurrencies[$defaultCurrency];

		return $supportedCurrencies[$key];
	}

}
