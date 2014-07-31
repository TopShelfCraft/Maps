<?php
namespace Craft;

class Charge_CouponModel extends BaseModel
{
    protected function defineAttributes()
    {
        return array(
            'id'                => AttributeType::Number,
            'stripeId'          => AttributeType::String,
			'name'   	        => array(AttributeType::String, 'required' => true),
            'code'              => array(AttributeType::String, 'required' => true),
            'paymentType'       => array(AttributeType::String, 'required' => true),
            'couponType'        => array(AttributeType::Enum, 'values' => 'amount,percentage', 'required' => true),
            'percentageOff'     => array(AttributeType::Number, 'min' => 0, 'max' => 100),
            'amountOff'         => array(AttributeType::Number, 'min' => 0),
            'currency'          => array(AttributeType::String),
            'duration'          => array(AttributeType::Enum, 'values' => 'forever,once,repeating'),
            'durationInMonths'  => array(AttributeType::Number, 'min' => 0),
            'maxRedemptions'    => array(AttributeType::Number, 'min' => 0),
            'redeemBy'          => array(AttributeType::Number)
        );
    }


   /**
     * @param null $attributes
     * @param bool $clearErrors
     * @return bool|void
     */
    public function validate($attributes = null, $clearErrors = true)
    {
        // Don't allow whitespace in the code.
      /*  if (preg_match('/\s+/', $this->code)) {
            $this->addError('code', Craft::t('Spaces are not allowed in the coupon code.'));
        }*/

        if($this->couponType == 'percentage' AND $this->percentageOff == '' ) {
            $this->addError('percentageOff', Craft::t('Percentage Off is required'));
        }

        if($this->couponType == 'amount' AND $this->amountOff == '') {
            $this->addError('amountOff', Craft::t('Amount Off is required'));
        }

        if($this->couponType == 'amount' AND $this->amountOff == '') {
            $this->addError('amountOff', Craft::t('Amount Off is required'));
        }

        if($this->couponType == 'amount' AND $this->amountOff == '0') {
            $this->addError('amountOff', Craft::t('Amount Off must be more than 0'));
        }

        if($this->duration == 'repeating' AND ($this->durationInMonths == '' OR $this->durationInMonths == '0'))  {
            $this->addError('durationInMonths', Craft::t('Duration in Months is required if the Duration is set to \'Repeating\'. Set to \'Forever\' for no limit'));
        }



        return parent::validate($attributes, false);
    }

}
