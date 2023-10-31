<?php

namespace Sotbit\Seometa\Link;

use Bitrix\Iblock\Template\Engine;
use Bitrix\Iblock\Template\Entity\Section;
use Sotbit\Seometa\SeoMetaMorphy;
use Sotbit\Seometa\Orm\SeometaUrlTable;
use Bitrix\Main\Text\Emoji;

class ReindexWriter extends AbstractWriter
{
    private static $Writer = false;
    private $index = 0;

    private function __construct(
        $oCallback,
        $callback_method
    ) {
        $this->oCallback = $oCallback;
        $this->callback_method = $callback_method;
        $this->reindex = true;
    }

    public static function GetInstance(
        $oCallback,
        $callback_method
    ) {
        if (self::$Writer === false) {
            self::$Writer = new ReindexWriter($oCallback, $callback_method);
        }

        return self::$Writer;
    }

    public function AddRow(
        array $arFields
    ) {
        $this->data[] = $arFields;
    }

    public function Write(
        array $arFields
    ) {
//        $morphyObject = SeoMetaMorphy::morphyLibInit();
        if (empty($this->oCallback) || empty($this->callback_method)) {
            return false;
        }
        $rsSites = \CSite::GetById($arFields['site_id']);
        $arSite = $rsSites->Fetch();
        $arSiteDir = substr($arSite['DIR'], 0, -1);

        $filter = [
            'ITEMS' => []
        ];
        $arFields['real_url'] = $arSiteDir . $arFields['real_url'];
        $arFields['new_url'] = $arSiteDir . $arFields['new_url'];
        $site = $this->arCondition['SITES'];
        if(!SeometaUrlTable::checkExistChpuReindex($arFields['new_url'],$this->arCondition['ID'],$site)){
            return false;
        }
        $meta = $this->arCondition['META'];
        unset($cond_properties);
        \CSeoMeta::SetFilterResult( $filter, $arFields['section_id'] );
        $sku = new Section( $arFields['section_id'] );
        \CSeoMetaTagsProperty::$params = $arFields['properties'];
        $Title = Engine::process( $sku , SeoMetaMorphy::prepareForMorphy($meta['ELEMENT_TITLE']));
        $Title = Emoji::decode($Title);
//        $Title = SeoMetaMorphy::convertMorphy($Title, $morphyObject);
        $body = trim($meta['ELEMENT_TOP_DESC'] . ' ' . $meta['ELEMENT_BOTTOM_DESC'] . ' ' . $meta['ELEMENT_ADD_DESC']);
        $fromPhpFormat = \Bitrix\Main\Type\Date::convertFormatToPhp("DD.MM.YYYY HH:MI:SS");
        $date = date($fromPhpFormat, $this->arCondition["DATE_CHANGE"]->getTimestamp());
        if (!empty($site)) {
            $Result = [
                "ID" => 'seometa_' . $this->unicConditionKey($this->arCondition, $arFields),
                "DATE_CHANGE" => $date,
                "PERMISSIONS" => [2],
                "BODY" => $body,
                'TITLE' => htmlspecialchars_decode(trim($Title)),
                'SITE_ID' => $site,
                'URL' => trim($arFields['real_url']),
                'PARAM1' => $this->arCondition['TYPE_OF_INFOBLOCK'],
                'PARAM2' => $this->arCondition['INFOBLOCK']
            ];

            $index_res = call_user_func([$this->oCallback, $this->callback_method], $Result);
            if (!$index_res) {
                $this->data[] = $Result["ID"];
                return true;
            }
        }

        return false;
    }

    private function unicConditionKey(
        $condition,
        $arFields
    ) {
        $items = $this->data ?: [];
        $key = $condition['ID'] . '_' . $arFields['section_id'] . '_' . count($items) . rand(0, 1000);
        foreach ($arFields['properties'] as $idx => $cond) {
            $key .= '_' . $condition['ID'] . '_' . implode('+',
                    $cond);
        }

        return htmlspecialchars_decode($key);
    }

    public function getData(
    ) {
        return $this->data;
    }
}
