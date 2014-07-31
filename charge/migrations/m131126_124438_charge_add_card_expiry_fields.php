<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m131126_124438_charge_add_card_expiry_fields extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$chargesTable = $this->dbConnection->schema->getTable('{{charges}}');

		if ($chargesTable->getColumn('cardExpMonth') === null)
		{
			// Add the 'hash' column to the charges table
			$this->addColumnAfter('charges', 'cardExpMonth', array('column' => ColumnType::Varchar), 'cardType');
			$this->addColumnAfter('charges', 'cardExpYear', array('column' => ColumnType::Varchar), 'cardExpMonth');
		}

		return true;
	}
}
