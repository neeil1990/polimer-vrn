<?
namespace Zverushki\Microm\Tools;

use Bitrix\Main\IO;

/**
* class Component
*
*
* @package Zverushki\Microm\Tools\Component
*/
class Component {
	private $id = false;
	private $path = false;
	private $data = false;
	private $siteDocRoot = false;
	private $exist = false;
	private $Class = false;

	private static $__components = array(
		'catalog' => 'Catalog',
		'catalog.element' => 'CatalogDetail',
	);

	function __construct ($componentId) {
		$this->id = $componentId;
		$this->siteDocRoot = \CSite::GetSiteDocRoot(SITE_ID);

		list($__componentNamecpace, $__componentName) = explode(':', trim($componentId));
		$this->Class = (__NAMESPACE__.'\\Component\\'.static::$__components[$__componentName]);

		if (class_exists($this->Class))
			$this->exist = true;

	} // end __construct


	public function execute () {
		if (!$this->exist)
			return false;

		if ($this->path !== false) {
			global $APPLICATION;

			$__components = \PHPParser::ParseScript($s = $APPLICATION->getFileContent($this->siteDocRoot.$this->path));

			foreach ($__components as $component) {
				if ($this->id != $component['DATA']['COMPONENT_NAME'])
					continue;

				if (!empty($this->data['DATA']['PARAMS']))
					$component['DATA']['PARAMS'] = array_merge_recursive($component['DATA']['PARAMS'], $this->data['DATA']['PARAMS']);

				$Ob = $this->__initComponent($component);

				return call_user_func(array($this->Class, 'execute'), $Ob);
			}

		} elseif ($this->data !== false) {
			$Ob = $this->__initComponent($this->data);

			return call_user_func(array($this->Class, 'execute'), $Ob);
		}

		return false;
	} // end function execute

	public function setPath ($path) {
		$this->path = $path;

		return $this;
	} // end function setPath

	public function setData ($data) {
		$this->data = array(
			'DATA' => array(
				// 'COMPONENT_NAME' => $data['componentName'],
				'TEMPLATE_NAME' => $data['templateName'],
				'PARAMS' => $data['params']
			)
		);

		return $this;
	} // end function setPath


	private function __parsePhpStr (&$param) {
		foreach (array(
			'IBLOCK_TYPE',
			'IBLOCK_ID',
			'ELEMENT_ID',
			'ELEMENT_CODE',
			'PRICE_CODE',
			'SEF_FOLDER',
			'CURRENCY_ID'
		) as $code) {
			if (array_key_exists($code, $param)) {
				if (is_array($param[$code])) {
					foreach ($param[$code] as $i => &$v)
						if (!is_array($v)) {
							$param[$code][$i] = preg_replace("/=\{(.+?)\}/","$1", $param[$code][$i], "-1", $count);
							if($count){
								$str = "\$param[\$code][\$i]=".$param[$code][$i].";";
								eval($str);
							}
						}

				} else {
					$param[$code] = preg_replace("/=\{(.+?)\}/","$1", $param[$code], "-1", $count);
					if($count){
						$str = "\$param[\$code]=".$param[$code].";";
						eval($str);
					}
				}

			}
		}

	} // end function __parsePhpStr


	/**
	 * Инициализация компонента
	 *
	 * @param  [type] $component [description]
	 * @return [type]            [description]
	 */
	private function __initComponent ($component) {
		$obComponent = new \CBitrixComponent();

		$obComponent->initComponent($this->id, $component['DATA']['TEMPLATE_NAME']);
		$obComponent->arParams = $component['DATA']['PARAMS'];

		$this->__parsePhpStr($obComponent->arParams);

		return $obComponent;
	} // end function __initComponent

} // end class Component