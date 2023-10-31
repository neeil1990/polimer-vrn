<?
namespace Zverushki\Microm;

use Bitrix\Main\Config;


/**
* class Options
*
* @var $__prefixType - ������� �������� ���� � ������� b_options
* @var $__prefixFormat - ������� �������� ������� � ������� b_options
* @var $__type - ������ ��������� �����
* @var $__format - ������ ��������� �������� �����
* @var $type - ������ ��������� ����� � ������
* @var $format - ������ ��������� �������� ����� � ������
*
* @package Zverushki\Microm\Options
*/
class Options {
	private static $entity = null;

	private $SITE_ID = false;
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
		$site = \CSite::GetList($by = "sort", $order = "desc", array("DEFAULT" => "Y"))
				->fetch();

		$this->SITE_ID = $site !== false ? $site['ID'] : false;

		foreach (static::$__type as $type) {
			$statusShow = Config\Option::get('zverushki.microm', strtolower(static::$__prefixType.$type));

			if ('Y' === strtoupper($statusShow)) {
				$Class = __NAMESPACE__.'\\Reading\\'.ucfirst(strtolower($type));

				$this->type[] = array(
					'key' => $type,
					'options' => method_exists($Class, 'getAdditionalOptions') ? $Class::getAdditionalOptions($this->SITE_ID) : array(),
					'ClassName' => $Class
				);
			}
		}

		$formatActive = Config\Option::get('zverushki.microm', strtolower(static::$__prefixFormat));

		if (in_array($formatActive, static::$__format))
			$this->format[] = strtolower($formatActive);
	} // end function __construct

	public static function entity () {
		if (static::$entity === null)
			static::$entity = new self;

		return static::$entity;
	} // end function entity

	public static function listType () { return static::$__type;}
	public static function listFormat () { return static::$__format;}

	public function settingsType () { return $this->type;}
	public function settingsFormat () { return $this->format;}

} // end class Options