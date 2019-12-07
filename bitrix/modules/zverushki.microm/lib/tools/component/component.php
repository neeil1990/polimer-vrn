<?
namespace Zverushki\Microm\Tools\Component;

/**
* class Component
*
* @package Zverushki\Microm\Tools\Component\Component
*/
abstract class Component {

    abstract protected static function __onPrepareComponentParams (&$params);
    abstract protected static function __component (&$component);

    public static function execute (\CBitrixComponent $component) {
        static::__onPrepareComponentParams($component->arParams);
        $result = static::__component($component);

        return $result;
    } // end function execute

} // end class Catalog