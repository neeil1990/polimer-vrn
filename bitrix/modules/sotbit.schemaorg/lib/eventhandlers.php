<?
namespace Sotbit\Schemaorg;

use Sotbit\Schemaorg\Helper\Menu;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;

/**
 * class for interception events
 *
 * @author Evgenij Sidorenko < e.sidorenko@sotbit.ru >
 *
 */


class EventHandlers{

    public function OnBuildGlobalMenuHandler(&$arGlobalMenu, &$arModuleMenu){
        Menu::getAdminMenu($arGlobalMenu, $arModuleMenu);
    }

    public function makeContent($url, $entity)
    {
        global $APPLICATION;
        Loader::includeModule("sotbit.schemaorg");

        $result = array();

        if (strpos($url, '/bitrix/') !== false)
            return "";

        if(self::checkExceptions($url))
            return "";

        $params = self::getRules(SITE_ID, $url, $entity);
        $arrRule = SchemaPageMetaTable::getList($params)->fetchAll();

        if(empty($arrRule))
        {
            $params = self::getAllRules(SITE_ID, $entity);
            $arrRule = SchemaPageMetaTable::getList($params)->fetchAll();
        }

        if(isset($arrRule))
        {
            foreach ($arrRule as $rule)
            {
                if(self::checkTemplate($rule["URL"], $url))
                    \SchemaMain::unserializeData($rule["ENTITIES"]);
            }
        }
    }

    static function checkExceptions($url)
    {
        $site = Helper\Site::GetList();
        foreach ($site as $k => $s) {
            $arFields["EXCEPTION_URL"] = \SchemaSettings::getInstance($k)->getOptions();
            if(isset($arFields["EXCEPTION_URL"]) && !empty($arFields["EXCEPTION_URL"]))
                foreach ($arFields["EXCEPTION_URL"] as $field){
                    if(strcmp($field, $url) == 0)
                        return true;
                }
            else
                return false;
        }
        return true;
    }

    static function getRules($site_id = SITE_ID, $url, $entity)
    {
        $params = array(
            'select' => array(
                "ID", "NAME", "URL", "ENTITIES", "SORT"
            ),
            'filter' => array(
                "SITE_ID" => $site_id,
                "ACTIVE" => "Y",
                "URL" => $url,
                "ENTITY_TYPE" => $entity,
            ),
            'order' => array(
                "SORT" => "ASC",
            ),
        );
        return $params;
    }

    static function getAllRules($site_id = SITE_ID, $entity)
    {
        $params = array(
            'select' => array(
                "ID", "NAME", "URL", "ENTITIES", "SORT"
            ),
            'filter' => array(
                "SITE_ID" => $site_id,
                "ACTIVE" => "Y",
                "%URL" => "*",
                "ENTITY_TYPE" => $entity,
            ),
            'order' => array(
                "SORT" => "ASC",
            ),
        );
        return $params;
    }

    static function checkTemplate($template, $url)
    {
        $template = str_replace(array(
            '*',
            '/'
        ), array(
            '.*',
            "\/"
        ), $template);

        if($template == "\/")
            $template = "^\/$";
        return preg_match('/'.$template.'/', $url);
    }

    function ChangeContent(&$content)
    {
        if(\CSite::InDir('/bitrix/'))
            return;

        global $APPLICATION;
        $url = $APPLICATION->GetCurPage(false);
        if(self::checkExceptions($url))
            return;

        $result = "";
        $data = \SchemaMain::getData();

        if(isset($data) && !empty($data)) {
            if(count($data) > 1)
                $result = "[";

            $i = 0;
            foreach ($data as $k => $dat) {
                if (is_array($dat["itemListElement"])) {
                    $dat["itemListElement"] = array_values($dat["itemListElement"]);
                }
                if (is_array($dat["reviews"])) {
                    $dat["reviews"] = array_values($dat["reviews"]);
                }
                if (is_array($dat["offers"])) {
                    $dat["offers"] = array_values($dat["offers"]);
                }
                if (is_array($dat["events"])) {
                    $dat["events"] = array_values($dat["events"]);
                }
                if (is_array($dat["texts"])) {
                    $dat["texts"] = array_values($dat["texts"]);
                }
                if (is_array($dat["things"])) {
                    $dat["things"] = array_values($dat["things"]);
                }

                $result .= \Bitrix\Main\Web\Json::encode($dat) . ( (count($data) > 1 && count($data) - 1 !== $i) ? ",\n" : "" );
                $i++;
            }

            if(!empty($result)){
                $patternArray = array("/reviews\b/mi", "/\"@type\": \"offers\"/mi", "/things\b/mi", "/texts\b/mi", "/events\b/mi", "/listitems\b/mi");
                $replaceArray = array("review", "\"@type\": \"Offer\"", "Thing", "Text", "Event", "ListItem");

                $result = preg_replace($patternArray, $replaceArray, $result);
                if(count($data) > 1)
                    $result .= "]";
                $content = preg_replace("/<\/body>[\s]*<\/html>/mi", "<script type=\"application/ld+json\">" . $result . "</script>\n</body>\n</html>", $content);
            }
        }
    }
}
?>