<?php
namespace Craft;

class SquareBitMaps_MapModel extends BaseModel
{

    public $default_options = array(
        'class' => '',
        'width' => '',
        'height' => ''
    );


    protected function defineAttributes()
    {
        return array(
            'mapLat' => AttributeType::String,
            'mapLng' => AttributeType::String,
            'mapZoom' => AttributeType::String,
            'pinLat' => AttributeType::String,
            'pinLng' => AttributeType::String
        );
    }

    public function __construct($data = array())
    {
        foreach($data as $key => $val)
        {
            $this->$key = $val;
        }
    }


    public function render($options = array())
    {
        craft()->templates->includeJsFile('//maps.google.com/maps/api/js?sensor=false');
        $path = craft()->path->getTemplatesPath();
        craft()->path->setTemplatesPath(craft()->path->getPluginsPath());

        // Merge with the default options
        $arr['map'] = $this;
        $arr['options'] = $this->_clean_options($options);

        if(!isset($arr['id'])) $arr['id'] = substr(md5(microtime()),rand(0,26),5);

        $plugin = craft()->plugins->getPlugin('squarebitmaps');
        if (!$plugin)
        {
            throw new Exception('Couldn’t find the SquareBit Maps plugin!');
        }
        $settings = $plugin->getSettings();
        $arr['googleMapsApiKey'] = $settings->googleMapsApiKey;

        $ret = craft()->templates->render('squarebitmaps/templates/render', $arr);
        craft()->path->setTemplatesPath($path);

        return $ret;
    }

    public function __toString()
    {
        return $this->render();
    }

    private function _clean_options($options = array())
    {
        if(empty($options)) return $this->default_options;

        $ret = $this->default_options;
        foreach($options as $opt => $val)
        {
            switch($opt)
            {
                case 'controls' :
                    $val = explode('|', $val);
                    foreach( array('zoom', 'scale', 'overview', 'map_type', 'pan', 'rotate', 'streetview') as $type )
                    {
                        $ret['ui_'.$type] = (array_search($type, $val) !== false);
                    }

                break;
                case 'interact' :
                    $val = explode('|', $val);
                    foreach( array('click_zoom', 'drag', 'scroll_zoom') as $type )
                    {
                        $ret['map_'.$type] = (array_search($type, $val) !== false);
                    }

                break;
                default :
                    $ret[$opt] = $val;
                break;
            }

        }

        return $ret;
    }
}
