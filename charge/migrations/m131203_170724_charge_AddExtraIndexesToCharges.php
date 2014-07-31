<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m131203_170724_charge_AddExtraIndexesToCharges extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$this->createIndex('charges', 'timestamp');
		$this->createIndex('charges', 'customerName');
		$this->createIndex('charges', 'customerEmail');
		$this->createIndex('charges', 'mode');
		$this->createIndex('charges', 'hash', true);

		return true;
	}
}
