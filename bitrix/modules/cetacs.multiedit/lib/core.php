<?

namespace Cetacs\MultiEdit;

use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Core
{
    private $adminList;
    private $iblockId;
    private $IDS;
    private $checkInputName;
    private static $allowTablesId = array("tbl_iblock_list_", "tbl_iblock_element_", "tbl_iblock_sub_element_", "tbl_product_list_", "tbl_product_admin_");
    public static $allowEditFields = array("SORT", "PREVIEW_PICTURE", "DETAIL_PICTURE");

    function __construct(\CAdminList $list)
    {
        $this->adminList = $list;
        if ($list instanceof \CAdminSubList) {
            $this->iblockId = $GLOBALS["intSubIBlockID"];
            $this->IDS = is_array($_POST["SUB_ID"]) ? $_POST["SUB_ID"] : array();
            $this->checkInputName = "SUB_ID";
        } else {
            $this->iblockId = $_GET["IBLOCK_ID"];
            $this->IDS = is_array($_POST["ID"]) ? $_POST["ID"] : array();
            $this->checkInputName = "ID";
        }
    }

    function checkTableId()
    {
        foreach (self::$allowTablesId as $tableId)
            if (strpos($this->adminList->table_id, $tableId) === 0)
                return true;
        return false;
    }

    public static function getPropertyList($iblockId)
    {
        $props = array();
        $params = array(
            "order" => array("SORT" => "ASC", "ID" => "ASC"),
            "filter" => array("ACTIVE" => "Y", "IBLOCK_ID" => $iblockId)
        );
        $rsProperties = PropertyTable::getList($params);
        while ($p = $rsProperties->fetch()) {
            if ($p["USER_TYPE_SETTINGS"]) {
                $p["USER_TYPE_SETTINGS"] = unserialize($p["USER_TYPE_SETTINGS"]);
            }
            $props[$p["ID"]] = $p;
        }
        return $props;
    }

    function showScript()
    {
        // для множественного свойства типа файл
        \CJSCore::Init(array('fileinput'));
        ?>
        <script>
            BX.ready(function () {
                var btn_save = {
                    title: BX.message('JS_CORE_WINDOW_SAVE'),
                    id: 'savebtn_multiedit',
                    name: 'savebtn',
                    className: 'adm-btn-save',
                    action: function () {
                        BX("cetacs_multiedit_dialog_form").submit();
                    }
                };

                function createDialog() {
                    var postData = {
                        IBLOCK_ID:<?=$this->iblockId?>,
                        TABLE_ID: '<?=$this->adminList->table_id?>',
                        CHECK_INPUT_NAME: '<?=$this->checkInputName?>',
                    };

                    return new BX.CDialog({
                        title: '<?=Loc::getMessage("CETACS_MULTIEDIT_CORE_SET_VALUE_PROP")?>',
                        content_url: "/bitrix/tools/cetacs.multiedit/dialog_content.php",
                        content_post: postData,
                        draggable: true,
                        resizable: true,
                        buttons: [btn_save, BX.CDialog.btnCancel]
                    });
                }

                window.cetacs_multiedit_dialog = createDialog();
                // window.cetacs_multiedit_dialog.Show();
            });
        </script>
        <?
    }

    public static function getPropertiesSelectBox($iblockId)
    {
        $values = array(
            "FIELDS" => array(
                "NAME" => Loc::getMessage("CETACS_MULTIEDIT_FIELDS"),
                "OPTIONS" => array(
                    "SORT" => Loc::getMessage("CETACS_MULTIEDIT_SORT"),
                    "PREVIEW_PICTURE" => Loc::getMessage("CETACS_MULTIEDIT_PREVIEW_PICTURE"),
                    "DETAIL_PICTURE" => Loc::getMessage("CETACS_MULTIEDIT_DETAIL_PICTURE")
                ),
            ),
            "PROPS" => array(
                "NAME" => Loc::getMessage("CETACS_MULTIEDIT_PROPS"),
                "OPTIONS" => array(),
            ),
        );
        foreach (self::getPropertyList($iblockId) as $prop) {
            $values["PROPS"]["OPTIONS"][$prop["ID"]] = $prop["NAME"];
        }
        return self::selectBox($values);
    }

    private static function selectBox(array $values)
    {
        $html = "<select id='cetacs_multiedit_props'>";
        $html .= "<option value=''>" . Loc::getMessage("CETACS_MULTIEDIT_CORE_SELECT_PROP") . "</option>";
        foreach ($values as $group) {
            $html .= "<optgroup label='{$group["NAME"]}'>";
            foreach ($group["OPTIONS"] as $id => $value) {
                $html .= "<option value='$id'>$value</option>";
            }
            $html .= "</optgroup>";
        }
        $html .= "</select>";
        return $html;
    }

    function initGroupOption()
    {
        $this->adminList->arActions["cetacs_multiedit"] = [
            "type" => "customJs",
            "lable" => Loc::getMessage("CETACS_MULTIEDIT_CORE_SET_VALUE_PROP"),
            "js" => "top.cetacs_multiedit_dialog.Show()",
        ];
    }

    function isModifyMode()
    {
        if ($_SERVER["REQUEST_METHOD"] != "POST") {
            return false;
        }
        if ($_POST["cma_action"] != "cetacs_multiedit_go") {
            return false;
        }
        if ($_POST["TABLE_ID"] != $this->adminList->table_id) {
            return false;
        }
        if (!(intval($_POST["PROPERTY_ID"]) > 0 || in_array($_POST["PROPERTY_ID"], self::$allowEditFields))) {
            return false;
        }

        $this->preparePost();
        return isset($_POST["PROP"][$_POST["PROPERTY_ID"]]) ? $_POST["PROP"][$_POST["PROPERTY_ID"]] : false;
    }

    private function preparePost()
    {
        $props = $this->getPropertyList($this->iblockId);
        $pId = $_POST["PROPERTY_ID"];

        if (is_array($_FILES["PROP"]))
            \CFile::ConvertFilesToPost($_FILES["PROP"], $_POST["PROP"]);

        if ($props[$pId]["PROPERTY_TYPE"] == "F" && is_array($_POST["PROP"][$pId])) {
            foreach ($_POST["PROP"][$pId] as &$p) {
                $p = \CIBlock::makeFilePropArray($p);
            }
        }
        if ($pId == "PREVIEW_PICTURE" || $pId == "DETAIL_PICTURE") {
            $_POST["PROP"][$pId] = \CIBlock::makeFilePropArray($_POST["PROP"][$pId]);
            $_POST["PROP"][$pId] = $_POST["PROP"][$pId]["VALUE"];
            if ($_POST["PROP"][$pId]["error"] == 4)
                $_POST["PROP"][$pId]["del"] = "Y";
        }
    }

    function getIds()
    {
        return $this->IDS;
    }

    function getIblockId()
    {
        return $this->iblockId;
    }
}
