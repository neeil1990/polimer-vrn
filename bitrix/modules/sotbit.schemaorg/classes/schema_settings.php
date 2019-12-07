<?
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;

if (! defined( "B_PROLOG_INCLUDED" ) || B_PROLOG_INCLUDED !== true)
    die();
/**
 * class fro helping to draw settings
 *
 * @author Evgenij Sidorenok < e.sidorenko@sotbit.ru >
 *
 */
class SchemaSettings
{
    private static $instance = false;
    private $options = array();

    protected function __construct($siteId = SITE_ID)
    {
        $this->initOptions($siteId);
    }

    public function getInstance($siteId = SITE_ID)
    {
        if(self::$instance == false)
        {
            self::$instance = new SchemaSettings($siteId);
        }

        return self::$instance;
    }
   
    /**
     * fill array by options
    */
    protected function initOptions($siteId = SITE_ID)
    {
        $this->options = unserialize(current(Option::getForModule(SotbitSchema::MODULE_ID, $siteId)));
    }

    /**
     * return option of module
     *
     * @param string $key - key of option
     * @param string/bool $default - default value of option if batadase there isn't option with this key
     *
     * @return string/bool
    */
    public function getOption($key, $default = false)
    {
        if(isset($this->options[$key]))
            return $this->options[$key];

        return $default;
    }

    /**
     * get array with module settings
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * refresh array of options by module
    */
    public function refreshOptions()
    {
        $this->initOptions();
    }
}
?>