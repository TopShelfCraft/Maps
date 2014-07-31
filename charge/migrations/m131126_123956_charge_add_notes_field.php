<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m131126_123956_charge_add_notes_field extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$chargesTable = $this->dbConnection->schema->getTable('{{charges}}');

		if ($chargesTable->getColumn('notes') === null)
		{
			// Add the 'hash' column to the charges table
			$this->addColumnAfter('charges', 'notes', array('column' => ColumnType::Text), 'description');
		}

		return true;
	}
}
