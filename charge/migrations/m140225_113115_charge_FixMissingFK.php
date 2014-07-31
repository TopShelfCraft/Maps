<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m140225_113115_charge_FixMissingFK extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		Craft::log('Dropping FK if it exists.', LogLevel::Info, true);
		MigrationHelper::dropForeignKeyIfExists('charges', array('id', 'userId'));

		// This throws errors on some installs
		// It's not fully requried, so we'll avoid it for now, and update it in a future version
		//Craft::log('Adding FK to charges table.', LogLevel::Info, true);
		//$this->addForeignKey('charges', 'id', 'elements', 'id', 'CASCADE', 'CASCADE');
		//$this->addForeignKey('charges', 'userId', 'users', 'id', 'SET NULL');

		return true;
	}
}
