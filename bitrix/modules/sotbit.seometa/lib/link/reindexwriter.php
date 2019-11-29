<?php
namespace Sotbit\Seometa\Link;

class ReindexWriter extends AbstractWriter
{
    private static $Writer = false;
    private $index = 0;

    private function __construct($oCallback, $callback_method)
    {
        $this->oCallback = $oCallback;
        $this->callback_method = $callback_method;
    }

    public static function GetInstance($oCallback, $callback_method)
    {
        if(self::$Writer === false)
            self::$Writer = new ReindexWriter($oCallback, $callback_method);

        return self::$Writer;
    }

    public function AddRow(array $arFields)
    {
        $this->data[] = $arFields;
    }

    public function Write(array $arFields)
    {
        if(empty($this->oCallback) || empty($this->callback_method))
            return;

        $filter = array (
            'ITEMS' => array ()
        );
        $meta = unserialize($this->arCondition['META']);
        unset( $cond_properties );
        \CSeoMeta::SetFilterResult( $filter, $arFields['section_id'] );
        $sku = new \Bitrix\Iblock\Template\Entity\Section( $arFields['section_id'] );

        \CSeoMetaTagsProperty::$params = $arFields['properties'];
        $Title = \Bitrix\Iblock\Template\Engine::process( $sku, $meta['ELEMENT_TITLE']);
        $body = trim($meta['ELEMENT_TOP_DESC'].' '.$meta['ELEMENT_BOTTOM_DESC'].' '.$meta['ELEMENT_ADD_DESC']);
        $sites = unserialize($this->arCondition['SITES']);

        if(is_array($sites))
        {

            $Result = array (
                //"ID" => 'seometa_' . $condition["ID"].implode('', $sites),
                "ID" => 'seometa_' . self::unicConditionKey($this->arCondition, $arFields),
                "DATE_CHANGE" => $this->arCondition["DATE_CHANGE"],
                "PERMISSIONS" => array (
                    2
                ),
                "BODY" =>  $body,
                'TITLE' => trim($Title),
                'SITE_ID' => $sites,
                'URL' => trim( $arFields['real_url'] ),
                'PARAM1' => $this->arCondition['TYPE_OF_INFOBLOCK'],
                'PARAM2' => $this->arCondition['INFOBLOCK']
            );

            $index_res = call_user_func( array (
                $this->oCallback,
                $this->callback_method
            ), $Result );

            if (! $index_res)
                $this->data[] = $Result["ID"];
        }
    }

    private function unicConditionKey($condition, $arFields)
    {
        $key = $condition['ID'].'_'.$arFields['section_id'].'_'.count($this->data).rand(0, 1000);
        
        foreach($arFields['properties'] as $idx => $cond) {
            $key .= '_'.$condition['ID'].'_'.implode('+',$cond);
        }

        return $key;
    }

    public function getData()
    {
        return $this->data;
    }
}