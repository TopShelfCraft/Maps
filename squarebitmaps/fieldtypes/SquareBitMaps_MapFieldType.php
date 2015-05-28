<?php
namespace Craft;

class SquareBitMaps_MapFieldType extends BaseFieldType
{
    private $_apiKey = '';
    private $_fallbackSettings = array(
                    'mapLat' => '51.507350899999985',
                    'mapLng' => '-0.1277582999999849', // London
                    'mapZoom' => '5',
                    'showHeight' => '300',
                    'showAddress' => '1',
                    'showPinCenter' => '1');

    public function getName()
    {
        return Craft::t('Map (SquareBit)');
    }

    public function defineContentAttribute()
    {
        return AttributeType::Mixed;
    }


    public function prepValueFromPost($value)
    {
        if (empty($value))
         {
            return new SquareBitMaps_MapModel();
        }

        $array = $this->_splitCoords($value);
        $map = new SquareBitMaps_MapModel($array);

        $this->onBeforeSave(new Event($this, array(
               'data'  => $array,
            'map'   => $map
        )));

        return $map;
    }


    public function getInputHtml($name, $map)
    {
        craft()->templates->includeJsFile('//maps.google.com/maps/api/js?sensor=false');

        // Figure out what that ID is going to look like once it has been namespaced
        $id = craft()->templates->formatInputId($name);
        $namespacedId = craft()->templates->namespaceInputId($id);


        $plugin = craft()->plugins->getPlugin('squarebitmaps');
        if (!$plugin)
        {
            throw new Exception('Couldn’t find the SquareBit Maps plugin!');
        }
        $settings = $plugin->getSettings();
        $this->_apiKey = $settings->googleMapsApiKey;


        // Create a raw string representation
        $value = '';
        //$this->_implodeCoords($map);
        if($map == null) $map = new SquareBitMaps_MapModel();


        $optional = false;
        $attr = $this->model->getAttributes();
        if(isset($attr['required']) && $attr['required'] === true)
        {
            $optional = false;  // disabled for the moment
            // There is a bug(?) in craft where this never returns correctly
            // Im sure BK/brad will fix it in the next few days
            // $optional = false;
        }

        return craft()->templates->render('SquareBitMaps/map', array(
            'name'  => $name,
            'id'    => $id,
            'namespacedId' => $namespacedId,
            'map' => $map,
            'value' => $value,
            'settings' => $this->getSettings(),
            'optional' => $optional,
            'googleMapsApiKey' => $this->_apiKey
        ));
    }

    public function getSettingsHtml()
    {
        craft()->templates->includeJsFile('//maps.google.com/maps/api/js?sensor=false');

        $name = 'map_settings';
        $id = 'map_settings';
        $arr = array();
        $value = '';

        $id = craft()->templates->formatInputId($name);
        $namespacedId = craft()->templates->namespaceInputId($id);


        $plugin = craft()->plugins->getPlugin('squarebitmaps');
        if (!$plugin)
        {
            throw new Exception('Couldn’t find the SquareBit Maps plugin!');
        }
        $settings = $plugin->getSettings();
        $this->_apiKey = $settings->googleMapsApiKey;

        return craft()->templates->render('SquareBitMaps/settings', array(
            'name'  => $name,
            'id'    => $id,
            'namespacedId' => $namespacedId,
            'arr' => $arr,
            'value' => $value,
            'settings' => $this->getSettings(),
            'optional' => false,
            'googleMapsApiKey' => $this->_apiKey
        ));
    }

    public function getSettings()
    {
        $settings = parent::getSettings();

        $settings = $this->_fallbackSettings($settings);

        return $settings;
    }

    public function prepSettings($settings)
    {
        if(isset($settings['map_settings'])) $settings = array_merge($settings, $this->_splitCoords($settings['map_settings']));

        $cleaned = array();
        foreach($this->defineSettings() as $key => $type)
        {
            if(isset($settings[$key]))
            {
                $cleaned[$key] = $settings[$key];
            }
        }

        return $cleaned;
    }

    protected function defineSettings()
    {
        return array(
            'mapLat'           => array(AttributeType::Number, 'decimals' => '10'),
            'mapLng'           => array(AttributeType::Number, 'decimals' => '10'),
            'mapZoom'          => array(AttributeType::Number),
            'showHeight'       => array(AttributeType::Number),
            'showAddress'      => array(AttributeType::String),
            'showMapType'      => array(AttributeType::String),
            'showPinCenter'    => array(AttributeType::String),
            'showMapType'      => array(AttributeType::String)
        );
    }


    private function _splitCoords($value)
    {
        $ret = array('mapLat' => '', 'mapLng' => '', 'mapZoom' => '', 'pinLat' => '', 'pinLng' => '');

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


    private function _implodeCoords(SquareBitMaps_MapModel $map)
    {
        $ret = '';
        $keys = array('mapLat' => '', 'mapLng' => '', 'mapZoom' => '', 'pinLat' => '', 'pinLng' => '');
        $arr = array();

        foreach($keys as $key => $val)
        {
            $arr[] = $map->$key;
        }

        if(count($arr) != 5) return $ret;
        $ret = implode('|', $arr);
    }


    private function _fallbackSettings($settings)
    {
        foreach($this->_fallbackSettings as $key => $val)
        {
            if(!isset($settings[$key]) || $settings[$key] == '')
            {
                $settings[$key] = $val;
            }
        }

        return $settings;
    }

    /**
     * Fires an 'onBeforeSquareBitMapsSave' event.
     *
     * @param Event $event
     */
    public function onBeforeSquareBitMapsSave(Event $event)
    {
        $this->raiseEvent('onBeforeSquareBitMapsSave', $event);
    }

}