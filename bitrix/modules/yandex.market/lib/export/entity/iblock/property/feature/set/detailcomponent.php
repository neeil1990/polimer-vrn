<?php
namespace Yandex\Market\Export\Entity\Iblock\Property\Feature\Set;

use Bitrix\Iblock;
use Yandex\Market\Reference\Concerns;
use Yandex\Market\Data;
use Yandex\Market\Utils;
use Yandex\Market\Export;

class DetailComponent extends Skeleton
{
	use Concerns\HasMessage;

	public function key()
	{
		return 'iblock.DETAIL_PAGE_SHOW';
	}

	public function title()
	{
		return self::getMessage('TITLE');
	}

	public function properties()
	{
		$result = [];
		$configured = $this->configuredCodes();

		foreach ($this->sourcesMap() as $source => $iblockId)
		{
			if (empty($configured[$source])) { continue; }

			$result[$source] = $this->iblockProperties($iblockId, $configured[$source]);
		}

		return $result;
	}

	protected function configuredCodes()
	{
		$pageUrl = $this->pageUrl();
		$scriptPath = $this->urlScript($pageUrl);
		$catalogParameters = $this->searchComponentParameters($scriptPath);

		return $this->extractComponentCodes($catalogParameters);
	}

	protected function pageUrl()
	{
		$iblock = \CIBlock::GetArrayByID($this->context['IBLOCK_ID']);

		if (!is_array($iblock)) { return ''; }

		$template = $this->iblockPageTemplate($iblock);
		$template = $this->replaceSiteVariables($template);

		return $this->compileUrlTemplate($template, $iblock);
	}

	protected function iblockPageTemplate(array $iblock)
	{
		return (string)($iblock['DETAIL_PAGE_URL'] ?: $iblock['SECTION_PAGE_URL'] ?: $iblock['LIST_PAGE_URL']);
	}

	protected function replaceSiteVariables($template)
	{
		$variables = Data\Site::getUrlVariables($this->context['SITE_ID']);

		if ($variables === false) { return $template; }

		return str_replace($variables['from'], $variables['to'], $template);
	}

	protected function compileUrlTemplate($template, array $iblock)
	{
		return \CIBlock::ReplaceDetailUrl($template, [
			'IBLOCK_ID' => $iblock['ID'],
			'IBLOCK_CODE' => $iblock['CODE'],
			'IBLOCK_TYPE_ID' => $iblock['IBLOCK_TYPE_ID'],
			'IBLOCK_EXTERNAL_ID' => $iblock['IBLOCK_TYPE_ID'],
			'CODE' => 'element',
			'ID' => 1,
			'ELEMENT_CODE' => 'element',
			'ELEMENT_ID' => 1,
			'SECTION_CODE' => 'section',
			'SECTION_ID' => 1,
			'SECTION_CODE_PATH' => 'section',
		]);
	}

	protected function urlScript($url)
	{
		if ($url === '') { return null; }

		$finder = new Utils\ScriptFinder($this->context['SITE_ID']);

		return $finder->resolveUrl($url);
	}

	protected function searchComponentParameters($path)
	{
		if ($path === null) { return []; }

		$fileContent = file_get_contents($path);

		if ($fileContent === false) { return []; }

		$result = [];

		foreach (\PHPParser::ParseScript($fileContent) as $component)
		{
			if (!isset($component['DATA']['COMPONENT_NAME'])) { continue; }

			$name = (string)$component['DATA']['COMPONENT_NAME'];

			if ($name === '' || !preg_match('/:catalog(\.section)?$/', $name)) { continue; }

			$result = (array)$component['DATA']['PARAMS'];
			break;
		}

		return $result;
	}

	protected function extractComponentCodes(array $componentParameters)
	{
		$all = [];

		foreach ($this->parameterMap() as $source => $parameters)
		{
			foreach ($parameters as $parameter)
			{
				if (!isset($componentParameters[$parameter]) || !is_array($componentParameters[$parameter])) { continue; }

				$selected = array_filter($componentParameters[$parameter]);

				if (!isset($all[$source])) { $all[$source] = []; }

				$all[$source] += array_flip($selected);
			}
		}

		return array_map(
			static function(array $codes) { return array_keys($codes); },
			$all
		);
	}

	protected function parameterMap()
	{
		return [
			Export\Entity\Manager::TYPE_IBLOCK_ELEMENT_PROPERTY => [
				'DETAIL_PROPERTY_CODE',
				'PROPERTY_CODE',
			],
			Export\Entity\Manager::TYPE_IBLOCK_OFFER_PROPERTY => [
				'DETAIL_OFFERS_PROPERTY_CODE',
				'OFFERS_PROPERTY_CODE',
				'OFFER_TREE_PROPS',
			],
		];
	}

	protected function iblockProperties($iblockId, array $codes)
	{
		if (empty($codes)) { return []; }

		$iterator = Iblock\PropertyTable::getList([
			'select' => [ 'ID' ],
			'filter' => [
				'=IBLOCK_ID' => $iblockId,
				'=ACTIVE' => 'Y',
				'!=PROPERTY_TYPE' => Iblock\PropertyTable::TYPE_FILE,
				$this->propertyCodesFilter($codes),
			],
			'order' => [
				'SORT' => 'ASC',
				'ID' => 'ASC',
			],
		]);

		return array_column($iterator->fetchAll(), 'ID', 'ID');
	}

	protected function propertyCodesFilter(array $codes)
	{
		$numericCodes = array_filter($codes, static function($code) { return is_numeric($code); });

		if (!empty($numericCodes))
		{
			$result = [
				'LOGIC' => 'OR',
				[ '=CODE' => $codes ],
				[ '=ID' => $numericCodes ],
			];
		}
		else
		{
			$result = [
				'=CODE' => $codes,
			];
		}

		return $result;
	}
}