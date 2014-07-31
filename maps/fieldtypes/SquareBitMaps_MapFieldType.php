<?php
namespace Craft;

class SquareBitMaps_MapFieldType extends BaseFieldType
{
    public function getName()
    {
        return Craft::t('Map (SquareBit)');
    }

    public function getInputHtml($name, $value)
    {
        $arr = $this->_splitCoords($value);

        craft()->templates->includeJsFile('//maps.google.com/maps/api/js?sensor=false');

        // Figure out what that ID is going to look like once it has been namespaced
        $id = craft()->templates->formatInputId($name);
        $namespacedId = craft()->templates->namespaceInputId($id);

        return craft()->templates->render('SquareBitMaps/map', array(
            'name'  => $name,
            'id'    => $namespacedId,
            'arr' => $arr,
            'value' => $value,
            'settings' => $this->getSettings()
        ));
    }

    public function getSettingsHtml()
    {
        return craft()->templates->render('SquareBitMaps/settings', array(
            'settings' => $this->getSettings()
        ));
    }

    public function getSettings()
    {
        $settings = parent::getSettings();

        return $settings;
    }


    protected function defineSettings()
    {
        return array(
            'map_lat'           => array(AttributeType::Number),
            'map_lng'           => array(AttributeType::Number),
            'map_zoom'          => array(AttributeType::Number),
            'show_height'       => array(AttributeType::Number),
            'show_address'      => array(AttributeType::String),
            'show_map_type'     => array(AttributeType::String),
            'show_pin_center'   => array(AttributeType::String)
        );
    }


    private function _splitCoords($value)
    {
        $ret = array('map_lat' => '', 'map_lng' => '', 'map_zoom' => '', 'pin_lat' => '', 'pin_lng' => '');

        $arr = explode('|', $value);
        if(count($arr) != 5) return $ret;

        $i = 0;
        foreach( $ret as $key => $val)
        {
            $ret[$key] = $arr[$i];
            $i++;
        }

        return $ret;
    }
}