<?php
namespace Craft;

class SquareBitMapsPlugin extends BasePlugin
{
	function getName()
	{
		return Craft::t('SquareBit Maps');
	}

	function getVersion()
	{
		return '1.1.1.b1';
	}

	function getDeveloper()
	{
		return 'Square Bit';
	}

	function getDeveloperUrl()
	{
		return 'http://squarebit.co.uk';
	}

	public function hasCpSection()
    {
        return false;
    }

	protected function defineSettings()
	{
		return array(
			'googleMapsApiKey'		=> array(AttributeType::String, 'required' => true)
		);
	}


	public function getSettingsHtml()
	{
		return craft()->templates->render('squarebitmaps/_settings', array(
			'settings' => $this->getSettings()
		));
	}


}
