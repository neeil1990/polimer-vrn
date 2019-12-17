<?
namespace Zverushki\Microm\Tools\Page;

use Bitrix\Main\UrlRewriter,
	Bitrix\Main\IO;

/**
* class UrlRewrite
*
*
* @package Zverushki\Microm\Tools\Page\UrlRewrite
*/
class UrlRewrite {
	private static $__instance = array();

	private $__siteId = false;
	private $__urlRewrite = array();

	private function __construct ($siteId) {
		$this->__siteId = $siteId;

		foreach (UrlRewriter::getList($this->__siteId) as $rule)
			$this->__urlRewrite[$rule['ID']][] = $rule;
	} // end __construct

	public static function getInstance ($siteId = false) {
		$siteId = $siteId ? $siteId : SITE_ID;

		if (!array_key_exists($siteId, static::$__instance))
			static::$__instance[$siteId] = new self($siteId);

		return static::$__instance[$siteId];
	} // end function getInstance


	public function getRuleByComponentId ($componentId) {
		$componentId = trim($componentId);

		if (!array_key_exists($componentId, $this->__urlRewrite))
			return false;

		foreach ($this->__urlRewrite[$componentId] as $rule) {
			$status = $this->__checkRule($rule);

			if ($status !== false)
				return $rule;
		}

		return false;
	} // end function getRuleByComponentId


	public function getRuleByUrl ($pageUrlTemplate, $requestPageUrl = false) {
		$pageUrlTemplate = trim($pageUrlTemplate);
		$pageUrlTemplate = str_replace('#SITE_DIR#', '', $pageUrlTemplate);

		$pageUrlTemplateReg = preg_replace("'#[^#]+?#'", "([^/]+?)", $pageUrlTemplate);
		$requestPageUrl = $requestPageUrl
							? $requestPageUrl
							: $_SERVER['REQUEST_URI'];

		$arValues = array();
		$arVariables = array();

		if (preg_match("'^".$pageUrlTemplateReg."$'", $requestPageUrl, $arValues)) {
			$arMatches = array();

			if (preg_match_all("'#([^#]+?)#'", $pageUrlTemplate, $arMatches)) {
				for ($i = 0, $cnt = count($arMatches[1]); $i < $cnt; $i++)
					$arVariables[$arMatches[1][$i]] = $arValues[$i + 1];
			}

			return array(
				'CONDITION' => $pageUrlTemplate,
				'VARIABLES' => $arVariables
			);
		}

		return false;
	} // end function getRuleByUrl


	private function __checkRule ($rule, $requestUri = false) {
		$requestUri = $requestUri ? $requestUri : $_SERVER['REQUEST_URI'];

		if (preg_match($rule["CONDITION"], $requestUri)) {
			if (strlen($rule["RULE"]) > 0)
				$url = preg_replace($rule["CONDITION"], (strlen($rule["PATH"]) > 0 ? $rule["PATH"]."?" : "").$rule["RULE"], $requestUri);
			else
				$url = $rule["PATH"];

			if (($pos = strpos($url, "?")) !== false)
				$url = substr($url, 0, $pos);

			$url = IO\Path::normalize($url);

			if (!file_exists($_SERVER['DOCUMENT_ROOT'].$url))
				return false;

			if (IO\Path::getExtension($url) != 'php')
				return false;

			return $rule;
		}

		return false;
	} // end function __checkRule

} // end class UrlRewrite