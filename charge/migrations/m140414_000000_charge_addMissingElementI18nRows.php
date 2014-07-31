<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m140414_000000_charge_addMissingElementI18nRows extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		// Find all of the elements that don't have a row in this table yet
		$elementIds = craft()->db->createCommand()
			->select('elements.id')
			->from('elements elements')
			->leftJoin('elements_i18n elements_i18n', 'elements_i18n.elementId = elements.id')
			->where(
				array('and', 'elements_i18n.id is null', 'elements.type = :elementType'),
				array(':elementType' => 'Charge')
			)
			->queryColumn();

		if ($elementIds)
		{
			$locale = craft()->i18n->getPrimarySiteLocaleId();
			$values = array();

			foreach ($elementIds as $elementId)
			{
				$values[] = array($elementId, $locale, 1);
			}

			$this->insertAll('elements_i18n', array('elementId', 'locale', 'enabled'), $values);
		}
	}
}
