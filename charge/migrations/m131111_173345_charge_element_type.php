<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m131111_173345_charge_element_type extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		// Rename the table to charges
		MigrationHelper::renameTable('charge_stripe', 'charges');

		// Make it an element type
		MigrationHelper::makeElemental('charges', 'Charge');

		return true;
	}
}
