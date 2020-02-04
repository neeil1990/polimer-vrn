<?php
namespace Sotbit\Seometa\Link;

class ChpuWriter extends AbstractWriter
{
    private static $Writer = false;

    private function __construct($id, $isProgress = false)
    {
        $this->id = $id;
        if (!$isProgress)
            \Sotbit\Seometa\SeometaUrlTable::deleteByConditionId( $id);
    }

    public static function GetInstance($Id, $isProgress = false)
    {
        if(self::$Writer === false)
            self::$Writer = new ChpuWriter($Id, $isProgress);
        return self::$Writer;
    }

    public function getWriterForAutogenerator($id)
    {
        self::$Writer = new ChpuWriter($id);
        return self::$Writer;
    }

    public function AddRow(array $arFields)
    {
        $this->data[] = $arFields;
    }

    public function Write(array $arFields)
    {
        $chpu['CONDITION_ID'] = $this->id;
        $chpu['REAL_URL'] = $arFields['real_url'];
        $chpu['ACTIVE'] = (in_array($arFields['active'], array('Y', 'N'), true)) ? $arFields['active'] : 'N';
        $chpu['NAME'] = $arFields['name'];
        $chpu['NEW_URL'] = $arFields['new_url'];
        $chpu['CATEGORY_ID'] = 0;
        $chpu['DATE_CHANGE'] = new \Bitrix\Main\Type\DateTime( date( 'Y-m-d H:i:s' ), 'Y-m-d H:i:s' );
        $chpu['iblock_id'] = $this->iblockID;
        $chpu['section_id'] = $arFields['section_id'];
        $chpu['PROPERTIES'] = serialize( $arFields['properties'] );
        $chpu['PRODUCT_COUNT'] = $arFields['product_count'];

        $new_id = \Sotbit\Seometa\SeometaUrlTable::add( $chpu );

        if ($new_id->isSuccess())
        {
            $new_id = $new_id->getId();
            $this->data[$new_id] = $chpu;
        }
        else
        {
        }
    }

    public function getData()
    {
        return $this->data;
    }
}