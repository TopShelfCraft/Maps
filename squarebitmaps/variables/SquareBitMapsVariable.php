<?php
namespace Craft;

class SquareBitMapsVariable
{

    /*
    * Returns a full map from a google map object
    * with options supplied
    */
    public function map($obj, $options = array())
    {
        $ret = $obj->render($options);

        return TemplateHelper::getRaw($ret);
    }
}