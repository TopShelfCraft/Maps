<?php
namespace Craft;

class SquareBitMapsVariable
{
    public function map($markers = array(), $options = array())
    {
        $name = 'test';
        $arr = array();
        $value = '';
        $settings = array();

        return craft()->squareBitMaps->renderMap();

        return 'hello there map';

        /*
        return craft()->templates->render('SquareBitMaps/map', array(
            'name'  => $name,
            'arr' => $arr,
            'value' => $value,
            'settings' => $settings
        ));

        return craft()->smartMap_variables->dynamicMap($markers, $options);*/
    }
/*
    // Link to the Google map
    public function linkToGoogle($address)
    {
        return craft()->smartMap_variables->linkToGoogle($address);
    }

    // Link to directions on a Google map
    public function directions($address, $title = null)
    {
        return craft()->smartMap_variables->linkToDirections($address, $title);
    }

    // Display a dynamic Google map
    public function map($markers = false, $options = array())
    {
        return craft()->smartMap_variables->dynamicMap($markers, $options);
    }

    // Display a static map image
    public function img($markers = false, $options = array())
    {
        return craft()->smartMap_variables->staticMap($markers, $options);
    }

    // Render the source for a static map image
    public function imgSrc($markers = false, $options = array())
    {
        return craft()->smartMap_variables->staticMapSrc($markers, $options);
    }

    // Renders details about "my" current location
    public function my()
    {
        return craft()->smartMap->here;
    }

    // Includes front-end Javascript
    public function js()
    {
        craft()->templates->includeJsFile('//maps.google.com/maps/api/js?sensor=false');
        craft()->templates->includeJsResource('smartmap/js/smartmap.js');
    }

    // FOR INTERNAL USE ONLY
    public function settings()
    {
        return craft()->smartMap->settings;
    }
    public function debug()
    {
        $debugData = array(
            'remote_addr'   => $_SERVER['REMOTE_ADDR'],
            'cookieValue'   => false,
            'cookieExpires' => false,
            'cacheValue'    => false,
            'cacheExpires'  => false,
        );
        $dateFormat = 'F j, Y - g:i:s a';
        if (craft()->smartMap->cookieData) {
            $debugData['cookieValue']   = craft()->smartMap->cookieData['ip'];
            $debugData['cookieExpires'] = date($dateFormat, craft()->smartMap->cookieData['expires']);
        }
        if (craft()->smartMap->cacheData) {
            $debugData['cacheValue']    = print_r(craft()->smartMap->cacheData['here'], true);
            $debugData['cacheExpires']  = date($dateFormat, craft()->smartMap->cacheData['expires']);
        }
        return $debugData;
    }*/

}