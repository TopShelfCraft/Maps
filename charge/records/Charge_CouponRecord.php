<?php
namespace Craft;

class Charge_CouponRecord extends BaseRecord
{
    public function getTableName()
    {
        return 'charge_coupons';
    }

    protected function defineAttributes()
    {
        return array(
            'stripeId'          => array(AttributeType::String),
            'name'   	        => array(AttributeType::String, 'required' => true),
            'code'              => array(AttributeType::String, 'required' => true, 'unique' => true),
            'paymentType'       => array(AttributeType::String, 'required' => true),
            'couponType'        => array(AttributeType::Enum, 'values' => 'amount,percentage', 'required' => true),
            'percentageOff'     => array(AttributeType::Number, 'min' => 0),
            'amountOff'         => array(AttributeType::Number, 'min' => 0),
            'currency'          => array(AttributeType::String),
            'duration'          => array(AttributeType::Enum, 'values' => 'forever,once,repeating'),
            'durationInMonths'  => array(AttributeType::Number, 'min' => 0),
            'maxRedemptions'    => array(AttributeType::Number, 'min' => 0),
            'redeemBy'          => array(AttributeType::Number)
        );
    }
}



