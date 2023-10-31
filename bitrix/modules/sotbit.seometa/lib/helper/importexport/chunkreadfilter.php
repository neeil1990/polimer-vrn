<?php

namespace Sotbit\Seometa\Helper\ImportExport;

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/sotbit.seometa/vendor/autoload.php");

class ChunkReadFilter implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter
{
    private $_startRow = 0;
    private $_endRow = 0;
    private $_arFilePos = array();
    private $_arMerge = array();
    private $_arLines = array();
    private $_params = array();
    /**  Set the list of rows that we want to read  */

    public function setParams($arParams=array())
    {
        $this->_params = $arParams;
    }

    public function getParam($paramName)
    {
        return (array_key_exists($paramName, $this->_params) ? $this->_params[$paramName] : false);
    }

    public function setLoadLines($arLines)
    {
        $this->_arLines = $arLines;
    }

    public function getLoadLines()
    {
        return $this->_arLines;
    }

    public function setMergeCells($mergeRef)
    {
        if(preg_match('/^([A-Z]+)(\d+):([A-Z]+)(\d+)$/', trim($mergeRef), $m) && $m[2]!=$m[4])
        {
            /*$this->_arMerge[$m[1]][$m[2].':'.$m[4]] = array($m[2], $m[4]);
            $this->_arMerge[$m[3]][$m[2].':'.$m[4]] = array($m[2], $m[4]);*/
            $this->_arMerge[$m[2].':'.$m[4]] = array($m[2], $m[4]);
        }
    }

    public function setRows($startRow, $chunkSize) {
        $this->_startRow = $startRow;
        $this->_endRow = $startRow + $chunkSize;
        $this->_arMerge = array();
    }

    public function readCell($column, $row, $worksheetName = '') {
        //  Only read the heading row, and the rows that are configured in $this->_startRow and $this->_endRow
        if (($row == 1) || ($row >= $this->_startRow && $row < $this->_endRow) || in_array($row, $this->_arLines)){
            return true;
        }
        elseif(count($this->_arMerge) > 0){
            foreach($this->_arMerge as $range){
                if($row >= $range[0] && $row <= $range[1] && (($this->_startRow >= $range[0] && $this->_startRow <= $range[1]) || ($this->_endRow >= $range[0] && $this->_endRow <= $range[1]))){
                    return true;
                }
            }
        }
        return false;
    }

    public function getStartRow()
    {
        return $this->_startRow;
    }

    public function getEndRow()
    {
        return $this->_endRow;
    }

    public function setFilePosRow($row, $pos)
    {
        $this->_arFilePos[$row] = $pos;
    }

    public function getFilePosRow($row)
    {
        $nextRow = $row + 1;
        $pos = 0;
        if(!empty($this->_arFilePos))
        {
            if(isset($this->_arFilePos[$nextRow])) $pos = (int)$this->_arFilePos[$nextRow];
            else
            {
                $arKeys = array_keys($this->_arFilePos);
                if(!empty($arKeys))
                {
                    $maxKey = max($arKeys);
                    if($nextRow > $maxKey);
                    {
                        $nextRow = $maxKey;
                        $pos = (int)$this->_arFilePos[$maxKey];
                    }
                }
            }
        }
        return array(
            'row' => $nextRow,
            'pos' => $pos
        );
    }
}