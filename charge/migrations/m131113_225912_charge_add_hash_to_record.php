<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m131113_225912_charge_add_hash_to_record extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{

		$chargesTable = $this->dbConnection->schema->getTable('{{charges}}');

		if ($chargesTable->getColumn('hash') === null)
		{
			// Add the 'hash' column to the charges table
			$this->addColumnAfter('charges', 'hash', array('column' => ColumnType::Varchar), 'userId');
		}


		return true;
	}
}
