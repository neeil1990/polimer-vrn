<?
namespace Zverushki\Microm\Tools\Component;

use Bitrix\Main\Loader,
	Zverushki\Microm\Tools;

/**
* class Catalog
*
*
* @package Zverushki\Microm\Tools\Component\Catalog
*/
class Catalog extends Component {

	protected static function __onPrepareComponentParams (&$params) { }

	protected static function __component (&$component) {
		if ($component->arParams["SEF_MODE"] == "Y")
			$result = static::__sefMode($component);
		else
			$result = static::__sefModeNo($component);

		if ($result !== false) {
			if ($result['componentPage'] == 'element') {
				$componentElement = new Tools\Component('bitrix:catalog.element');

				foreach ($result['variables'] as $code => $val)
					$result['params'][$code] = $val;

				$result = $componentElement
							->setData(array('templateName' => '', 'params' => $result['params']))
							->execute();

			} else
				$result = false;
		}

		return $result;
	} // end function execute

	private static function __sefMode (&$component) {
		$arVariables = array();

		$smartBase = ($component->arParams["SEF_URL_TEMPLATES"]["section"] ? $component->arParams["SEF_URL_TEMPLATES"]["section"]: "#SECTION_ID#/");
		$arDefaultUrlTemplates404 = array(
			"sections" => "",
			"section" => "#SECTION_ID#/",
			"element" => "#SECTION_ID#/#ELEMENT_ID#/",
			"compare" => "compare.php?action=COMPARE",
			"smart_filter" => $smartBase."filter/#SMART_FILTER_PATH#/apply/"
		);

		$arComponentVariables = array(
			"SECTION_ID",
			"SECTION_CODE",
			"ELEMENT_ID",
			"ELEMENT_CODE",
			"action",
		);
		$engine = new \CComponentEngine($component);
		if (Loader::includeModule('iblock')) {
			$engine->addGreedyPart("#SECTION_CODE_PATH#");
			$engine->addGreedyPart("#SMART_FILTER_PATH#");
			$engine->setResolveCallback(array("\CIBlockFindTools", "resolveComponentEngine"));
		}

		if (!is_array($arDefaultVariableAliases404))
			$arDefaultVariableAliases404 = array();

		$arUrlTemplates = \CComponentEngine::MakeComponentUrlTemplates($arDefaultUrlTemplates404, $component->arParams["SEF_URL_TEMPLATES"]);
		$arVariableAliases = \CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases404, $component->arParams["VARIABLE_ALIASES"]);

		$componentPage = $engine->guessComponentPath(
			$component->arParams["SEF_FOLDER"],
			$arUrlTemplates,
			$arVariables
		);

		\CComponentEngine::InitComponentVariables($componentPage, $arComponentVariables, $arVariableAliases, $arVariables);

		return array(
			'componentPage' => $componentPage,
			'variables' => $arVariables,
			'params' => $component->arParams
		);
	} // end function __sefMode

	private static function __sefModeNo (&$component) {
		return false;
	} // end function __sefModeNo

} // end class Catalog