<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m140218_110053_charge_AddPlanCoupon extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$chargesTable = $this->dbConnection->schema->getTable('{{charges}}');

		if ($chargesTable->getColumn('planCoupon') === null)
		{
			// Add the 'hash' column to the charges table
			$this->addColumnAfter('charges', 'planCoupon', array('column' => ColumnType::Varchar), 'planAmount');
			$this->addColumnAfter('charges', 'planDiscount', array('column' => ColumnType::Int), 'planAmount');
			$this->addColumnAfter('charges', 'planFullAmount', array('column' => ColumnType::Int), 'planAmount');
			$this->addColumnAfter('charges', 'planCouponStripeId', array('column' => ColumnType::Varchar), 'planCoupon');
		}

		return true;
	}

}
