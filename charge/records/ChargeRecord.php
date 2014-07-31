<?php

namespace Craft;

/**
 * Ingredient Record
 *
 * Provides a definition of the database tables required by our plugin,
 * and methods for updating the database. This class should only be called
 * by our service layer, to ensure a consistent API for the rest of the
 * application to use.
 */
class ChargeRecord extends BaseRecord
{
    private $currencySymbol = array('usd' => '&#36;', 'gbp' => '&#163;', 'eur' => '&#128;');

    public function getTableName()
    {
        return 'charges';
    }

    public function defineAttributes()
    {
        return array(
            'sourceUrl'             => AttributeType::Url,

            'planAmount'            => array(AttributeType::Number, 'required' => true),
            'planInterval'          => AttributeType::String,
            'planIntervalCount'     => AttributeType::Number,
            'planCurrency'          => array(AttributeType::String, 'required' => true),
            'planType'              => array(AttributeType::Enum, 'values' => 'charge,recurring', 'required' => true),
            'planCoupon'            => AttributeType::String,
            'planCouponStripeId'    => AttributeType::String,
            'planDiscount'          => AttributeType::Number,
            'planFullAmount'        => AttributeType::Number,

            'cardName'              => AttributeType::String,
            'cardAddressLine1'      => AttributeType::String,
            'cardAddressLine2'      => AttributeType::String,
            'cardAddressCity'       => AttributeType::String,
            'cardAddressState'      => AttributeType::String,
            'cardAddressZip'        => AttributeType::String,
            'cardAddressCountry'    => AttributeType::String,
            'cardLast4'             => AttributeType::String,
            'cardType'              => AttributeType::String,
            'cardExpMonth'          => AttributeType::String,
            'cardExpYear'           => AttributeType::String,

            'customerName'          => array(AttributeType::String, 'required' => true),
            'customerEmail'         => array(AttributeType::Email, 'required' => true),

            'hash'                  => array(AttributeType::String, 'label' => 'Transaction Hash'),
            'stripe'                => AttributeType::String,
            'mode'                  => array(AttributeType::Enum, 'values' => 'test,live'),
            'description'           => array(AttributeType::String, 'label' => 'Description'),
            'timestamp'             => array(AttributeType::DateTime, 'label' => 'Time'),
            'notes'                 => array(AttributeType::String, 'column' => ColumnType::Text),
        );
    }

    /**
     * @return array
     */
    public function defineRelations()
    {
        return array(
            'element'   => array(static::BELONGS_TO, 'ElementRecord', 'id', 'required' => true, 'onDelete' => static::CASCADE),
            'user'      => array(static::BELONGS_TO, 'UserRecord', 'required' => false, 'onDelete' => static::SET_NULL)
        );
    }


    /**
     * @return array
     */
    public function defineIndexes()
    {
        return array(
            array('columns' => array('hash'), 'unique' => true),
            array('columns' => array('customerName')),
            array('columns' => array('customerEmail')),
            array('columns' => array('mode')),
            array('columns' => array('timestamp'))
        );
    }

}
