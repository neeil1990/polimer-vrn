<?
namespace Zverushki\Microm;

use Bitrix\Main\Config,
	Bitrix\Main\Context;
use COption;


/**
* class Options
*
* @var $__prefixType - префикс названия типа в таблице b_options
* @var $__prefixFormat - префикс названия формата в таблице b_options
* @var $__type - список возможных типов
* @var $__format - список возможных форматов схемы
* @var $type - список включеный типов в модуле
* @var $format - список включеных форматов схемы в модуле
*
* @package Zverushki\Microm\Options
*/
class Options {
	private static $entity = null;

	private $SITE_ID = false;
	private $VERSION = false;
	private static $__prefixType = 'microm_view_';
	private static $__prefixFormat = 'microm_format_active';

	private static $__type = array(
		'breadcrumb',
		'business',
		'product',
		'article'
	);
	private static $__format = array(
		'json-ld',
		'microdata'
	);

	private $type = array();
	private $format = array();

	private function __construct () {
		include __DIR__.'/../install/version.php';

		$this->SITE_ID = Context::getCurrent()->getSite();
		$this->VERSION = $arModuleVersion;

		foreach (static::$__type as $type) {
			$statusShow = Config\Option::get('zverushki.microm', strtolower(static::$__prefixType.$type), $this->SITE_ID);

			if ('Y' === strtoupper($statusShow)) {
				$Class = __NAMESPACE__.'\\Reading\\'.ucfirst(strtolower($type));

				$this->type[] = array(
					'key' => $type,
					'options' => method_exists($Class, 'getAdditionalOptions') ? $Class::getAdditionalOptions($this->SITE_ID) : array(),
					'ClassName' => $Class
				);
			}
		}

		$formatActive = Config\Option::get('zverushki.microm', strtolower(static::$__prefixFormat), $this->SITE_ID);

		if (in_array($formatActive, static::$__format))
			$this->format[] = strtolower($formatActive);
	}

	public static function entity () {
		if (static::$entity === null)
			static::$entity = new self;

		return static::$entity;
	}

	public static function listType () { return static::$__type;}
	public static function listFormat () { return static::$__format;}

    public function isOpenGraph()
    {
        return Config\Option::get('zverushki.microm', strtolower(static::$__prefixType.'open_graph'), $this->SITE_ID) == 'Y';
    }

	public function getSiteId () { return $this->SITE_ID;}
	public function getVersion () { return $this->VERSION;}
	public function settingsType () { return $this->type;}
	public function settingsFormat () { return $this->format;}
	public function getSite () {
		$Request = Context::getCurrent()->getRequest();

		$serverName = $_SERVER['SERVER_NAME'];
		$protocol = $Request->isHttps() ? 'https' : 'http';

        return [
            'id'       => $this->SITE_ID,
            'name'     => (string)COption::GetOptionString('main', 'site_name'),
            'domain'   => $serverName,
            'protocol' => $protocol,
            'url'      => $protocol.'://'.$serverName,
        ];
    }

}
?>