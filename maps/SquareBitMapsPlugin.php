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
		return '0.1';
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
			'something'					=> array(AttributeType::String, 'required' => true)
		);
	}


}
