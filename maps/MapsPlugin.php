<?php
namespace Craft;

class MapsPlugin extends BasePlugin
{

	function getName()
	{
		return Craft::t('Maps');
	}

	function getVersion()
	{
		return '1.2.1';
	}

	function getDeveloper()
	{
		return 'Top Shelf Craft';
	}

	function getDeveloperUrl()
	{
		return 'https://topshelfcraft.com';
	}

	public function hasCpSection()
	{
		return false;
	}

	function getReleaseFeedUrl()
	{
		return 'https://raw.githubusercontent.com/TopShelfCraft/Maps/master/releases.json';
	}

	public function getDocumentationUrl()
	{
		return 'https://raw.githubusercontent.com/TopShelfCraft/Maps';
	}

	protected function defineSettings()
	{
		return array(
			'googleMapsApiKey'		=> array(AttributeType::String, 'required' => true)
		);
	}

	public function getSettingsHtml()
	{
		return craft()->templates->render('maps/_settings', array(
			'settings' => $this->getSettings()
		));
	}

}
