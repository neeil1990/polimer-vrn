<?php
namespace Sotbit\Seometa\Helper;
use Sotbit\Seometa\ConditionTable;
use Sotbit\Seometa\SeoMeta;
use Bitrix\Main\Loader;

class Mask
{
    private $mask = false;
    private $template = array();
    private $delimiter = '-or-';

    public function GetTemplate($IblockId = 0, $ChpuType = '', $fullMask = true)
    {
        $key = $ChpuType.$IblockId.intval($fullMask);
        if(isset($this->template[$key]))
            return $this->template[$key];

        $sectionMask = self::GetSectionMask($IblockId, $fullMask);

        switch ($ChpuType)
        {
            case 'bitrix_chpu':
                $MASK = $sectionMask."/filter/#FILTER_PARAMS#apply/";
                break;

            case 'bitrix_not_chpu':
                $MASK = $sectionMask."/?set_filter=y#FILTER_PARAMS#";
                break;

            case 'misshop_chpu':
                $MASK = $sectionMask."/filter/#FILTER_PARAMS#apply/";
                break;

            case 'combox_chpu':
                $MASK = $sectionMask."/filter/#FILTER_PARAMS#";
                break;

            case 'combox_not_chpu':
                $MASK = $sectionMask."/?#FILTER_PARAMS#";
                break;

            default:
                $MASK = $sectionMask;
        }

        // $this->mask = $MASK;
        $this->template = $MASK;

        if($this->HasIblockPlaceholders())
            $this->ReplaceIblockHolders($IblockId);
        

        return $MASK;
    }

    public function isEmpty()
    {
        return empty($this->mask);
    }

    protected static function GetSectionMask($IblockId = 0, $fullMask = true)
    {
        if($IblockId == 0)
            return '';

        $iblock = \CIBlock::GetById($IblockId)->Fetch();

        return ($fullMask) ? trim($iblock['SECTION_PAGE_URL'], '/') : '/'.trim(str_replace('#SITE_DIR#', '', $iblock['SECTION_PAGE_URL']), '/');
    }

    public function SetTemplate($template = true)
    {
        if(is_string($template))
        {
            $this->mask = $template;
            $this->template = $template;
        }
        if($template === true)
            $this->mask = $this->template;
    }

    public function ReplaceIblockHolders($IblockId)
    {
        $iblock = \CIBlock::GetById($IblockId)->Fetch();
        $keys = $this->placeholders['IBLOCK'];
        $result = array();
        foreach($keys as &$key)
        {
            $clearKey = str_replace(array('#IBLOCK_', '#'), '', $key);
            if (isset($iblock[$clearKey]) && $clearKey != 'TYPE_ID')
                    $result[$key] = $iblock[str_replace(array('#IBLOCK_', '#'), '', $key)];
            elseif($clearKey == 'TYPE_ID')
            {
                $result[$key] = $iblock['IBLOCK_TYPE_ID'];
            }
        }

        $this->ReplaceHolders($result);
    }

    public function HasIblockPlaceholders()
    {
        preg_match_all('/\#(IBLOCK_ID|IBLOCK_CODE|IBLOCK_TYPE_ID)\#/', $this->mask, $match);
        if(isset($match[0]) && !empty($match[0]))
            $this->placeholders['IBLOCK'] = $match[0];

        return !empty($match[0]);
    }

    public function HasSectionPlaceholders()
    {
        preg_match_all('/\#(ID|SECTION_ID|CODE|SECTION_CODE|SECTION_CODE_PATH|EXTERNAL_ID)\#/', $this->mask, $match);
        if(isset($match[0]) && !empty($match[0]))
            $this->placeholders['SECTION'] = $match[0];

        return !empty($match[0]);
    }

    public function ReplaceHolders($arHolderValues = array())
    {
        $arHolderValues = $this->CheckFields($arHolderValues);
        $this->mask = str_replace(array_keys($arHolderValues), $arHolderValues, $this->mask);
    }

    private function CheckFields($arFields)
    {
       if(is_array($arFields))
            foreach ($arFields as &$arField)
                if(is_array($arField))
                {
                    $arField = implode($this->delimiter, $arField);
                }

        return is_array($arFields) ? $arFields : array();
    }

    public function GetMask()
    {
        return $this->mask;
    }

    public function SetDelimiter($str)
    {
        if(is_string($str))
            $this->delimiter = $str;
    }

    /**
     * Get glue for implements parameters by type of Generator
     * @param \Sotbit\Seometa\Generater\Common $Generator
     * @return true
     */
    public static function GetLinkGlue(\Sotbit\Seometa\Generater\Common $Generator)
    {
        if(mb_strpos('\Sotbit\Seometa\Generater\ComboxGenerator', get_class($Generator)) !== false)
            return '&';

        return '';
    }
}
?>