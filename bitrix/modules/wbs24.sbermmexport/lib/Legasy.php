<?php
namespace Wbs24\Sbermmexport;

use Bitrix\Iblock;

class Legasy
{
    public function text2xml(string $text, array $options)
    {
        $text = htmlspecialcharsbx($text, ENT_QUOTES|ENT_XML1);

        $text = preg_replace("/[\x1-\x8\xB-\xC\xE-\x1F]/", "", $text);

        $error = '';
        return \Bitrix\Main\Text\Encoding::convertEncoding($text, LANG_CHARSET, $options['CHARSET'], $error);
    }

    public function prepareItems(array &$list, array $parents, array $options)
    {
        $descrField = 'PREVIEW_TEXT';
        $descrTypeField = 'PREVIEW_TEXT_TYPE';
        if (isset($options['DESCRIPTION']))
        {
            $descrField = $options['DESCRIPTION'];
            $descrTypeField = $options['DESCRIPTION'].'_TYPE';
        }

        foreach (array_keys($list) as $index)
        {
            $row = &$list[$index];

            $row['DETAIL_PAGE_URL'] = (string)$row['DETAIL_PAGE_URL'];
            if ($row['DETAIL_PAGE_URL'] !== '')
            {
                $safeRow = array();
                foreach ($row as $field => $value)
                {
                    if ($field == 'PREVIEW_TEXT' || $field == 'DETAIL_TEXT')
                        continue;
                    if (\CProductQueryBuilder::isValidField($field))
                        continue;
                    if (is_array($value))
                        continue;
                    if (preg_match("/[;&<>\"]/", $value))
                        $safeRow[$field] = htmlspecialcharsEx($value);
                    else
                        $safeRow[$field] = $value;
                    $safeRow['~'.$field] = $value;
                }
                unset($field, $value);

                if (isset($row['PARENT_ID']) && isset($parents[$row['PARENT_ID']]))
                {
                    $safeRow['~DETAIL_PAGE_URL'] = str_replace(
                        array('#SERVER_NAME#', '#SITE_DIR#', '#PRODUCT_URL#'),
                        array($options['SITE_NAME'], $options['SITE_DIR'], $parents[$row['PARENT_ID']]),
                        $safeRow['~DETAIL_PAGE_URL']
                    );
                }
                else
                {
                    $safeRow['~DETAIL_PAGE_URL'] = str_replace(
                        array('#SERVER_NAME#', '#SITE_DIR#'),
                        array($options['SITE_NAME'], $options['SITE_DIR']),
                        $safeRow['~DETAIL_PAGE_URL']
                    );
                }
                $row['DETAIL_PAGE_URL'] = \CIBlock::ReplaceDetailUrl($safeRow['~DETAIL_PAGE_URL'], $safeRow, false, 'E');
                unset($safeRow);
            }

            if ($row['DETAIL_PAGE_URL'] == '')
                $row['DETAIL_PAGE_URL'] = '/';
            else
                $row['DETAIL_PAGE_URL'] = str_replace(' ', '%20', $row['DETAIL_PAGE_URL']);

            $row['PICTURE'] = $this->getPreparedPicture($row, $options);

            $row['DESCRIPTION'] = '';
            if ($row[$descrField] !== null)
            {
                $preparedValue = preg_replace_callback("'&[^;]*;'", function($arg) {
                    if (in_array($arg[0], array("&quot;", "&amp;", "&lt;", "&gt;"))) {
                        return $arg[0];
                    } else {
                        return " ";
                    }
                }, $row[$descrField]);

                $row['DESCRIPTION'] = $this->text2xml(
                    \TruncateText(
                        $row[$descrTypeField] == 'html' ? strip_tags($preparedValue) : $preparedValue,
                        $options['MAX_DESCRIPTION_LENGTH']
                    ),
                    $options
                );
            }

            unset($row);
        }
        unset($index);
    }

    public function getPreparedPicture(array $row, array $options)
    {
        $preparedPicture = false;
        $row['DETAIL_PICTURE'] = (int)$row['DETAIL_PICTURE'];
        $row['PREVIEW_PICTURE'] = (int)$row['PREVIEW_PICTURE'];

        $pictureId = 0;
        if ($options['PICTURE'] == 'AUTO') {
            $pictureId = $row['DETAIL_PICTURE'] > 0 ? $row['DETAIL_PICTURE'] : $row['PREVIEW_PICTURE'];
        } else {
            $pictureId = $row[$options['PICTURE']] ?? 0;
        }

        if ($pictureId) {
            $pictureFile = \CFile::GetFileArray($pictureId);
            if (!empty($pictureFile)) {
                if (strncmp($pictureFile['SRC'], '/', 1) == 0) {
                    $picturePath = $options['PROTOCOL'].$options['SITE_NAME'].\CHTTP::urnEncode($pictureFile['SRC'], 'utf-8');
                } else {
                    $picturePath = $pictureFile['SRC'];
                }
                $preparedPicture = $picturePath;
            }
        }

        return $preparedPicture;
    }

    public function getValue(
        array $arOffer,
        string $param,
        $PROPERTY,
        array $arProperties,
        array $arUserTypeFormat,
        array $options
    )
    {
        $strProperty = '';
        $bParam = (strncmp($param, 'PARAM_', 6) == 0);
        if (isset($arProperties[$PROPERTY]) && !empty($arProperties[$PROPERTY]))
        {
            $iblockProperty = $arProperties[$PROPERTY];
            $PROPERTY_CODE = $iblockProperty['CODE'];
            if (!isset($arOffer['PROPERTIES'][$PROPERTY_CODE]) && !isset($arOffer['PROPERTIES'][$PROPERTY]))
                return $strProperty;
            $arProperty = (
                isset($arOffer['PROPERTIES'][$PROPERTY_CODE])
                ? $arOffer['PROPERTIES'][$PROPERTY_CODE]
                : $arOffer['PROPERTIES'][$PROPERTY]
            );
            if ($arProperty['ID'] != $PROPERTY)
                return $strProperty;

            $value = '';
            $description = '';
            switch ($iblockProperty['PROPERTY_TYPE'])
            {
                case 'USER_TYPE':
                    if ($iblockProperty['MULTIPLE'] == 'Y')
                    {
                        if (!empty($arProperty['~VALUE']))
                        {
                            $arValues = array();
                            foreach($arProperty["~VALUE"] as $oneValue)
                            {
                                $isArray = is_array($oneValue);
                                if (
                                    ($isArray && !empty($oneValue))
                                    || (!$isArray && $oneValue != '')
                                )
                                {
                                    $arValues[] = call_user_func_array($arUserTypeFormat[$PROPERTY],
                                        array(
                                            $iblockProperty,
                                            array("VALUE" => $oneValue),
                                            array('MODE' => 'SIMPLE_TEXT'),
                                        )
                                    );
                                }
                            }
                            $value = implode(', ', $arValues);
                        }
                    }
                    else
                    {
                        $isArray = is_array($arProperty['~VALUE']);
                        if (
                            ($isArray && !empty($arProperty['~VALUE']))
                            || (!$isArray && $arProperty['~VALUE'] != '')
                        )
                        {
                            $value = call_user_func_array($arUserTypeFormat[$PROPERTY],
                                array(
                                    $iblockProperty,
                                    array("VALUE" => $arProperty["~VALUE"]),
                                    array('MODE' => 'SIMPLE_TEXT'),
                                )
                            );
                        }
                    }
                    break;
                case Iblock\PropertyTable::TYPE_ELEMENT:
                    if (!empty($arProperty['VALUE']))
                    {
                        $arCheckValue = array();
                        if (!is_array($arProperty['VALUE']))
                        {
                            $arProperty['VALUE'] = (int)$arProperty['VALUE'];
                            if ($arProperty['VALUE'] > 0)
                                $arCheckValue[] = $arProperty['VALUE'];
                        }
                        else
                        {
                            foreach ($arProperty['VALUE'] as $intValue)
                            {
                                $intValue = (int)$intValue;
                                if ($intValue > 0)
                                    $arCheckValue[] = $intValue;
                            }
                            unset($intValue);
                        }
                        if (!empty($arCheckValue))
                        {
                            $filter = array(
                                '@ID' => $arCheckValue
                            );
                            if ($iblockProperty['LINK_IBLOCK_ID'] > 0)
                                $filter['=IBLOCK_ID'] = $iblockProperty['LINK_IBLOCK_ID'];

                            $iterator = Iblock\ElementTable::getList(array(
                                'select' => array('ID', 'NAME'),
                                'filter' => array($filter)
                            ));
                            while ($row = $iterator->fetch())
                            {
                                $value .= ($value ? ', ' : '').$row['NAME'];
                            }
                            unset($row, $iterator);
                        }
                    }
                    break;
                case Iblock\PropertyTable::TYPE_SECTION:
                    if (!empty($arProperty['VALUE']))
                    {
                        $arCheckValue = array();
                        if (!is_array($arProperty['VALUE']))
                        {
                            $arProperty['VALUE'] = (int)$arProperty['VALUE'];
                            if ($arProperty['VALUE'] > 0)
                                $arCheckValue[] = $arProperty['VALUE'];
                        }
                        else
                        {
                            foreach ($arProperty['VALUE'] as $intValue)
                            {
                                $intValue = (int)$intValue;
                                if ($intValue > 0)
                                    $arCheckValue[] = $intValue;
                            }
                            unset($intValue);
                        }
                        if (!empty($arCheckValue))
                        {
                            $filter = array(
                                '@ID' => $arCheckValue
                            );
                            if ($iblockProperty['LINK_IBLOCK_ID'] > 0)
                                $filter['=IBLOCK_ID'] = $iblockProperty['LINK_IBLOCK_ID'];

                            $iterator = Iblock\SectionTable::getList(array(
                                'select' => array('ID', 'NAME'),
                                'filter' => array($filter)
                            ));
                            while ($row = $iterator->fetch())
                            {
                                $value .= ($value ? ', ' : '').$row['NAME'];
                            }
                            unset($row, $iterator);
                        }
                    }
                    break;
                case Iblock\PropertyTable::TYPE_LIST:
                    if (!empty($arProperty['~VALUE']))
                    {
                        if (is_array($arProperty['~VALUE']))
                            $value .= implode(', ', $arProperty['~VALUE']);
                        else
                            $value .= $arProperty['~VALUE'];
                    }
                    break;
                case Iblock\PropertyTable::TYPE_FILE:
                    if (!empty($arProperty['VALUE']))
                    {
                        if (is_array($arProperty['VALUE']))
                        {
                            foreach ($arProperty['VALUE'] as $intValue)
                            {
                                $intValue = (int)$intValue;
                                if ($intValue > 0)
                                {
                                    if ($ar_file = \CFile::GetFileArray($intValue))
                                    {
                                        if(substr($ar_file["SRC"], 0, 1) == "/")
                                            $strFile = $options['PROTOCOL'].$options['SITE_NAME'].\CHTTP::urnEncode($ar_file['SRC'], 'utf-8');
                                        else
                                            $strFile = $ar_file["SRC"];
                                        $value .= ($value ? ', ' : '').$strFile;
                                    }
                                }
                            }
                            unset($intValue);
                        }
                        else
                        {
                            $arProperty['VALUE'] = (int)$arProperty['VALUE'];
                            if ($arProperty['VALUE'] > 0)
                            {
                                if ($ar_file = \CFile::GetFileArray($arProperty['VALUE']))
                                {
                                    if(substr($ar_file["SRC"], 0, 1) == "/")
                                        $strFile = $options['PROTOCOL'].$options['SITE_NAME'].\CHTTP::urnEncode($ar_file['SRC'], 'utf-8');
                                    else
                                        $strFile = $ar_file["SRC"];
                                    $value = $strFile;
                                }
                            }
                        }
                    }
                    break;
                default:
                    if ($bParam && $iblockProperty['WITH_DESCRIPTION'] == 'Y')
                    {
                        $description = $arProperty['~DESCRIPTION'];
                        $value = $arProperty['~VALUE'];
                    }
                    else
                    {
                        $value = is_array($arProperty['~VALUE']) ? implode(', ', $arProperty['~VALUE']) : $arProperty['~VALUE'];
                    }
            }

            // !!!! check multiple properties and properties like CML2_ATTRIBUTES

            if ($bParam)
            {
                if (is_array($description))
                {
                    if (!empty($value))
                    {
                        foreach ($value as $key => $val)
                        {
                            if ($val != '')
                            {
                                $strProperty .= $strProperty ? "\n" : "";
                                $strProperty .= '<param name="'.$this->text2xml($description[$key], $options).'">'.
                                    $this->text2xml($val, $options).'</param>';
                            }
                        }
                    }
                }
                else
                {
                    if ($value != '')
                    {
                        $strProperty .= '<param name="'.$this->text2xml($iblockProperty['NAME'], $options).'">'.
                            $this->text2xml($value, $options).'</param>';
                    }
                }
            }
            else
            {
                $param_h = $this->text2xml($param, $options);
                $showedValue = $this->text2xml($value, $options);

                if ($param_h == 'picture' || $param_h == 'offers-picture')
                {
                    $strProperty .= $this->getAdditionalPhotos($showedValue);
                } else {
                    $strProperty .= '<'.$param_h.'>'.$showedValue.'</'.$param_h.'>';
                }
            }

            unset($iblockProperty);
        }

        return $strProperty;
    }

    protected function getAdditionalPhotos($value)
    {
        $xml = '';

        if ($value) {
            $param = 'picture';
            $pictures = explode(', ', $value);

            foreach ($pictures as $picture) {
                $xml .= "<${param}>${picture}</${param}>";
            }
        }

        return $xml;
    }
}
