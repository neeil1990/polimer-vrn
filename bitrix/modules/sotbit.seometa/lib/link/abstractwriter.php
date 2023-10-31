<?php
namespace Sotbit\Seometa\Link;

abstract class AbstractWriter
{
    protected $data = false;
    protected $iblockID = false;
    protected $id = false;
    protected $arCondition = false;

    abstract public function AddRow(
        array $arFields
    );

    abstract public function Write(
        array $arFields
    );

    public function SetCondition(
        array $arCondition
    ) {
        $this->arCondition = $arCondition;
    }

    public function SetIBlockID(
        $ID
    ) {
        $this->iblockID = $ID;
    }

    public function isEmptyData(
    ) {
        return empty($this->data);
    }
}
?>