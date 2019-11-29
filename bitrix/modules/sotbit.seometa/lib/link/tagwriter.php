<?
namespace Sotbit\Seometa\Link;

class TagWriter extends AbstractWriter
{
    private static $Writer = false;

    private function __construct($WorkingConditions)
    {
        $this->WorkingConditions = $WorkingConditions;
    }

    public static function getInstance($WorkingConditions)
    {
        if(self::$Writer === false)
            self::$Writer = new TagWriter($WorkingConditions);

        return self::$Writer;
    }

    public function AddRow(array $arFields)
    {
    }

    public function Write(array $arFields)
    {
        $filter = array('ITEMS' => array());

        //\CSeoMeta::SetFilterResult($filter, $Section);
        $sku = new \Bitrix\Iblock\Template\Entity\Section($arFields['section_id']);
        \CSeoMetaTagsProperty::$params = $arFields['properties'];

        $conditionTag = unserialize($this->arCondition['CONDITION_TAG']);

        if($arFields['strict_relinking'] != 'Y')
            $Title = \Bitrix\Iblock\Template\Engine::process($sku, $this->arCondition['TAG']);
        else if(in_array($this->arCondition['ID'], $this->WorkingConditions) && $conditionTag)
            $Title = \Bitrix\Iblock\Template\Engine::process($sku, $this->arCondition['TAG']);

        $this->data[] = array(
            'URL' => trim($arFields['real_url']),
            'TITLE' => trim($Title)
        );
    }

    public function getData()
    {
        return $this->data;
    }
}
