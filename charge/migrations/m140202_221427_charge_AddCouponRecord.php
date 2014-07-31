<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m140202_221427_charge_AddCouponRecord extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		// Create the craft_charge_coupons table
		craft()->db->createCommand()->createTable('charge_coupons', array(
			'stripeId'         => array(),
			'name'             => array('required' => true),
			'code'             => array('required' => true),
			'paymentType'      => array('required' => true),
			'couponType'       => array('values' => 'amount,percentage', 'column' => 'enum', 'required' => true),
			'percentageOff'    => array('maxLength' => 11, 'decimals' => 0, 'unsigned' => true, 'length' => 10, 'column' => 'integer'),
			'amountOff'        => array('maxLength' => 11, 'decimals' => 0, 'unsigned' => true, 'length' => 10, 'column' => 'integer'),
			'currency'         => array(),
			'duration'         => array('values' => 'forever,once,repeating', 'column' => 'enum'),
			'durationInMonths' => array('maxLength' => 11, 'decimals' => 0, 'unsigned' => true, 'length' => 10, 'column' => 'integer'),
			'maxRedemptions'   => array('maxLength' => 11, 'decimals' => 0, 'unsigned' => true, 'length' => 10, 'column' => 'integer'),
			'redeemBy'         => array('maxLength' => 11, 'decimals' => 0, 'unsigned' => false, 'length' => 10, 'column' => 'integer'),
		), null, true);

		// Add indexes to craft_charge_coupons
		craft()->db->createCommand()->createIndex('charge_coupons', 'code', true);
	}
}
