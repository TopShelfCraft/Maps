<?php
namespace Craft;

class ChargeModel extends BaseElementModel
{
    protected $elementType = 'Charge';

	protected function defineAttributes()
	{
		return array_merge(parent::defineAttributes(), array(
			'customerName'          => array(AttributeType::String, 'required' => true, 'label' => 'Name'),
			'customerEmail'         => array(AttributeType::Email, 'required' => true, 'label' => 'Email'),

            'cardToken'             => array(AttributeType::String,  'required' => true, 'label' => 'Stripe Card Token'),
            'cardName'              => array(AttributeType::String, 'label' => 'Cardholder Name'),
            'cardAddressLine1'      => array(AttributeType::String, 'label' => 'Card Address 1'),
            'cardAddressLine2'      => array(AttributeType::String, 'label' => 'Card Address 2'),
            'cardAddressCity'       => array(AttributeType::String, 'label' => 'Card Address City'),
            'cardAddressState'      => array(AttributeType::String, 'label' => 'Card Address State'),
            'cardAddressZip'        => array(AttributeType::String, 'label' => 'Card Address Zip'),
            'cardAddressCountry'    => array(AttributeType::String, 'label' => 'Card Address Country'),
            'cardLast4'             => array(AttributeType::String, 'label' => 'Card Last 4'),
            'cardType'              => array(AttributeType::String, 'label' => 'Card Type'),
            'cardExpMonth'          => array(AttributeType::String, 'label' => 'Card Expiry Month'),
            'cardExpYear'           => array(AttributeType::String, 'label' => 'Card Expiry Year'),

            'planAmount'            => array(AttributeType::Number, 'required' => true, 'label' => 'Amount'),
			'planCurrency'	        => array(AttributeType::String, 'label' => 'Currency'),
            'planInterval'          => array(AttributeType::String, 'label' => 'Plan Interval'),
            'planIntervalCount'     => array(AttributeType::Number, 'label' => 'Plan Interval Count'),
            'planType'              => array(AttributeType::Enum, 'values' => 'charge, recuring', 'label' => 'Plan Type'),
            'planName'              => array(AttributeType::String),
            'planCoupon'            => array(AttributeType::String),
            'planCouponStripeId'    => array(AttributeType::String),
            'planDiscount'          => array(AttributeType::Number),
            'planFullAmount'        => array(AttributeType::Number),

            'hasDiscount'           => array(AttributeType::Bool, 'label' => 'Has a Discount?'),

			'description'	        => array(AttributeType::String, 'label' => 'Description'),
            'hash'                  => array(AttributeType::String, 'label' => 'Hash'),
            'stripe'                => array(AttributeType::String, 'label' => 'Stripe Data'),
            'mode'                  => array(AttributeType::Enum, 'values' => 'test,live', 'label' => 'Transaction Mode'),
            'sourceUrl'             => array(AttributeType::Url, 'label' => 'Source URL'),
            'userId'                => array(AttributeType::Number, 'label' => 'User ID'),
            'timestamp'             => array(AttributeType::DateTime, 'lable' => 'Time'),
            'notes'                 => array(AttributeType::String),
		));
	}

    /**
     * @return string
     */
    function __toString()
    {
        return $this->id;
    }

    /**
     * Returns the element's CP edit URL.
     *
     * @return string|false
     */
    public function getCpEditUrl()
    {
        return UrlHelper::getCpUrl('charge/detail/'.$this->id);
    }

    public function formatCard($char = '&#183;')
    {
    	$ret = '';
    	$charset = craft()->templates->getTwig()->getCharset();

        switch($this->cardType) {
            case 'American Express' :
            	$ret = $char.$char.$char.$char.' '.$char.$char.$char.$char.$char.$char.' '.$char.$char.$this->cardLast4;
            break;
            case 'Diners Club' :
                $ret = $char.$char.$char.$char.' '.$char.$char.$char.$char.' '.$char.$char.$this->cardLast4;
            break;
            case 'Visa' :
            case 'MasterCard' :
            case 'Discover' :
            case 'JCB' :
                $ret = $char.$char.$char.$char.' '.$char.$char.$char.$char.' '.$char.$char.$char.$char.' '.$this->cardLast4;
            break;
            default :
                $ret = $char.$char.$char.$char.' '.$char.$char.$char.$char.' '.$char.$char.$char.$char.' '.$char.$char.$char.$char;
            break;
        }

        return new \Twig_Markup($ret, $charset);
    }

    private function _formatAmount($amount, $currency, $format = 'symbol')
    {
        $charset = craft()->templates->getTwig()->getCharset();

        $currency = ChargePlugin::getCurrencies($currency);

        return new \Twig_Markup(html_entity_decode($currency[$format].number_format($amount/100,2), ENT_QUOTES), $charset);
    }


    public function formatPlanAmount($format = 'symbol')
    {
        return $this->_formatAmount($this->planAmount, $this->planCurrency, $format);
    }

    public function formatDiscountAmount($format = 'symbol')
    {
        return $this->_formatAmount($this->planDiscount, $this->planCurrency, $format);
    }

    public function formatPlanFullAmount($format = 'symbol')
    {
        return $this->_formatAmount($this->planFullAmount, $this->planCurrency, $format);
    }

    public function formatPlanName()
    {
        $charset = craft()->templates->getTwig()->getCharset();

        $name = craft()->charge->constructPlanName($this, 'symbol');

        return new \Twig_Markup(html_entity_decode($name,ENT_QUOTES), $charset);
    }

    public function validate($attributes = null, $clear = TRUE)
    {
        parent::validate($attributes, false);

        if($this->planCoupon != '') $model = craft()->charge_coupon->handleCoupon($this);

        return !$this->hasErrors();
    }

    public static function populateModel($row)
    {
        if($row['planDiscount'] > 0) $row['hasDiscount'] = true;

        return parent::populateModel($row);
    }
}
