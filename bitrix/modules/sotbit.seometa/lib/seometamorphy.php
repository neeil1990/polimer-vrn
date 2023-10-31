<?php
namespace Sotbit\Seometa;

use Bitrix\Main\Localization\Loc;

class SeoMetaMorphy
{

    public static function morphyLibInit(){

        $path = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/sotbit.seometa/lib/phpmorphy/';
        $fileName = $path . 'phpmorphy-0.3.7/src/common.php';

        if(mb_strtolower(LANG_CHARSET) != 'utf-8') {
            $dirName = $path . 'dicts-cp1251';
        } else {
            $dirName = $path . 'dicts-utf-8';
        }


        if (file_exists($fileName) && file_exists($dirName)) {

            require_once($fileName);

            $lang = 'ru_RU';

            $opts = array(
                'storage' => PHPMORPHY_STORAGE_FILE,
            );

            try {
                $morphy = new \phpMorphy($dirName, $lang, $opts);
                return $morphy;
            } catch (\phpMorphy_Exception $e) {
                return false;
            }
        }
        else {
            return false;
        }
    }

    public static function prepareForMorphy($metaStr)
    {
        $pregPatternCondition = GetMessage('PATTERN');
        $parsedTemplate = preg_replace('/({=morphy\s*)/',  "*#", $metaStr);
        $charset = mb_strtolower(mb_detect_encoding($metaStr,
            array(
                'utf-8',
                'cp1251'
            )));

        if (mb_strtoupper($charset) == 'UTF-8' && mb_strtoupper(LANG_CHARSET) == 'UTF-8') {
            $pregPatternCondition .= 'u';
        }

        $parsedTemplateArray = @preg_split($pregPatternCondition, $parsedTemplate, -1 ,PREG_SPLIT_DELIM_CAPTURE);

        $replasedArray = array();
        foreach ($parsedTemplateArray as $strElement){
            if (preg_match($pregPatternCondition, $strElement)){
                $strElement = trim($strElement);
                $strElement = str_replace(array('"', "'", '}'), '', $strElement);
                $strElement = "##" . $strElement . "#*";
            }
            array_push($replasedArray, $strElement);
        }
        return implode('', $replasedArray);
    }

    public static function convertMorphy($metaString, $morphy){

        if (mb_strpos($metaString, "*#") !== false) {

            $metaString = str_replace("*#", "*###*#", $metaString);
            $strElements = explode("*###", $metaString);
            $wordsDelimiter = ",";
            $resultString = '';

            foreach ($strElements as $strElement) {


                if (mb_strpos($strElement, "*#") !== false)
                {
                    $strStart = mb_strpos($strElement, "*#");
                    $strEnd = mb_strpos($strElement, "#*");
                    $subStr = mb_substr($strElement, $strStart, $strEnd - $strStart);
                    $subStr = str_replace("*#", '', $subStr);

                    $strings = explode("##", $subStr);

                    $modifiers = array();
                    foreach (explode(',', $strings[1]) as $modifier) {
                        $modifiers[] = trim($modifier);
                    }

                    $words = explode($wordsDelimiter, $strings[0]);
                    $resultWords = array();

                    foreach ($words as $word) {
                        if ($morphy) {
                            $modifiedWords = $morphy->castFormByGramInfo(mb_strtoupper($word), null, $modifiers, true);
                            if (is_array($modifiedWords) && (mb_strlen($modifiedWords[0]) > 0)) {
                                if (is_array($modifiedWords)) {
                                    array_push($resultWords, mb_strtolower($modifiedWords[0]));
                                }
                            } else {
                                array_push($resultWords, $word);
                            }
                        } else {
                            array_push($resultWords, $word);
                        }
                    }
                    $resElement = implode($wordsDelimiter, $resultWords);
                    $resElement = rtrim($resElement);
                    $resElement = mb_substr($resElement, 0, -1);

                    $resultString .= $resElement . mb_substr($strElement, $strEnd + 2);
                }
                else
                {
                    $resultString .= $strElement;
                }
            }
        }
        else
        {
            $resultString = $metaString;
        }

        return $resultString;
    }
}
