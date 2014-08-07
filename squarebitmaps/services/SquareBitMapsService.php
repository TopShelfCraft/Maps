<?php
namespace Craft;

class SquareBitMapsService extends BaseApplicationComponent
{


	public function renderMap()
	{
		return craft()->templates->render('SquareBitMaps/map', array(
            'name'  => 'test',
            'arr' => array(),
            'value' => 'test',
            'settings' => array()
        ));

		return 'service';
	}
}