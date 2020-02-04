<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
if(!Loader::includeModule('sotbit.seometa') || !Loader::includeModule('iblock'))
{
	return false;
}

global $SeoMetaWorkingConditions;
if(!$arParams['CACHE_TIME'])
{
	$arParams['CACHE_TIME'] = '36000000';
}
if(!$arParams['SORT'])
{
	$arParams['SORT'] = 'NAME';
}

$cacheTime = $arParams['CACHE_TIME'];
$cache_id = serialize(array($arParams, $SeoMetaWorkingConditions, ($arParams['CACHE_GROUPS'] === 'N' ? false : $USER->GetGroups())));
$cacheDir = '/sotbit.seometa.tags/';
$cache = \Bitrix\Main\Data\Cache::createInstance();
$Tags = array();

if($cache->initCache($cacheTime, $cache_id, $cacheDir))
{
	$Tags = $cache->getVars();
}
elseif($cache->startDataCache())
{
	$strict_relinking = false;
	$Conditions = array();
	$sections = \Sotbit\Seometa\Tags::findNeedSections($arParams['SECTION_ID'], $arParams['INCLUDE_SUBSECTIONS']); // list of all sections
	$SectionConditions = \Sotbit\Seometa\ConditionTable::GetConditionsBySections($sections); // list of all conditions by sections

	// if condition is active
	if($SeoMetaWorkingConditions)
	{
		foreach($SeoMetaWorkingConditions as $SeoMetaWorkingCondition)
		{
			$wasSections = false;
			if($SectionConditions[$SeoMetaWorkingCondition]) // if among all conditions by sections there is one that is active
			{
				if($SectionConditions[$SeoMetaWorkingCondition]['STRICT_RELINKING'] == 'Y')
				{
					$strict_relinking = true;
				}

				if(sizeof($SectionConditions[$SeoMetaWorkingCondition]['SECTIONS']) > 0)
				{
					$wasSections = true;
				}

				//unset($SectionConditions[$SeoMetaWorkingCondition]['SECTIONS'][array_search($arParams['SECTION_ID'], $SectionConditions[$SeoMetaWorkingCondition]['SECTIONS'])]);

				if(sizeof($SectionConditions[$SeoMetaWorkingCondition]['SECTIONS']) == 0 && $wasSections)
				{
					unset($SectionConditions[$SeoMetaWorkingCondition]);
				}
			}
		}
	}

	$WorkingConditions = \Sotbit\Seometa\ConditionTable::GetConditionsFromWorkingConditions($SeoMetaWorkingConditions); // conditions selected in relinking

	if(is_array($SectionConditions) && is_array($WorkingConditions))
	{
		if(!$strict_relinking)
		{
			$Conditions = $SectionConditions;
		}

		// merge conditions selected in relinking with other
		foreach($WorkingConditions as $key => $WorkingCondition)
		{
			$Conditions[$key] = $WorkingCondition;
		}
	}
	elseif(is_array($SectionConditions))
	{
		$Conditions = $SectionConditions;
	}
	elseif(is_array($WorkingConditions))
	{
		$Conditions = $WorkingConditions;
	}

	$TagsObject = new \Sotbit\Seometa\Tags();
	$Tags = $TagsObject->GenerateTags($Conditions, $SeoMetaWorkingConditions);

	if($strict_relinking)
	{
		foreach($Tags as $key => $tag)
		{
			if($tag['URL'] == $APPLICATION->GetCurPage(false))
				unset($Tags[$key]);
		}
		$Tags = array_values($Tags);
	}

	$Tags = $TagsObject->SortTags($Tags, $arParams['SORT'], $arParams['SORT_ORDER']);
	$Tags = $TagsObject->CutTags($Tags, $arParams['CNT_TAGS']);
	$Tags = $TagsObject->ReplaceChpuUrls($Tags);
	unset($Conditions);
	$cache->endDataCache($Tags);
}
$curPage = array_search($APPLICATION->GetCurPage(false), array_column($Tags, 'URL'));
if($curPage !== false)
    unset($Tags[$curPage]);
$arResult['ITEMS'] = $Tags;
unset($Tags);

$this->IncludeComponentTemplate();
?>