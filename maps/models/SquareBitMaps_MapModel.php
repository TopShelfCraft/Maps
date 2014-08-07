<?php
namespace Craft;

class SquareBitMaps_MapModel extends BaseModel
{
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


    public function render()
    {
        craft()->templates->includeJsFile('//maps.google.com/maps/api/js?sensor=false');
        craft()->path->setTemplatesPath(craft()->path->getPluginsPath());

        $arr['map'] = $this;
        $arr['id'] = substr(md5(microtime()),rand(0,26),5);

        $plugin = craft()->plugins->getPlugin('squarebitmaps');
        if (!$plugin)
        {
            throw new Exception('Couldn’t find the SquareBit Maps plugin!');
        }
        $settings = $plugin->getSettings();
        $arr['googleMapsApiKey'] = $settings->googleMapsApiKey;

        $ret = craft()->templates->render('squarebitmaps/templates/render', $arr);

        return $ret;

    }

    public function __toString()
    {
        return $this->render();
    }
}
