<?
define("STOP_STATISTICS", true);
define("PUBLIC_AJAX_MODE", true);
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/iblock/admin_tools.php");

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Loader;
use Cetacs\MultiEdit\Core;

Loc::loadMessages(dirname(__FILE__) . "/../lib/core.php");

if (!Loader::includeModule("fileman"))
    exit;
if (!Loader::includeModule("iblock"))
    exit;
if (!Loader::includeModule("cetacs.multiedit"))
    exit;

$propertyId = htmlspecialcharsbx($_POST["PROPERTY_ID"]);
if (intval($propertyId) > 0) {
    $prop = CIBlockProperty::GetPropertyArray($propertyId, intval($_POST["IBLOCK_ID"]));
    if (strlen($prop["USER_TYPE_SETTINGS"]) > 0) {
        $prop["USER_TYPE_SETTINGS"] = unserialize($prop["USER_TYPE_SETTINGS"]);
    }
}

$tableId = htmlspecialcharsbx($_POST["TABLE_ID"]);
$checkInputName = htmlspecialcharsbx($_POST["CHECK_INPUT_NAME"]);


$props = [
    ["ID" => "PREVIEW_PICTURE"],
    ["ID" => "DETAIL_PICTURE"],
    ["ID" => "SORT"],
];

$props = array_merge($props, Core::getPropertyList(intval($_POST["IBLOCK_ID"])));

?>
    <?// Это для свойства привязка к элемента в виде автодополнения?>
    <script src="/bitrix/components/bitrix/main.lookup.input/script.js"></script>
    <script src="/bitrix/components/bitrix/main.lookup.input/templates/iblockedit/script2.js"></script>

    <form action="" method="post" id="cetacs_multiedit_dialog_form" enctype="multipart/form-data">
        <table class="adm-detail-content-table">
            <tr>
                <td style="width: 40%"></td>
                <td>
                    <?= Core::getPropertiesSelectBox(intval($_POST["IBLOCK_ID"])) ?>
                    <br>
                    <br>
                    <br>
                </td>
            </tr>

            <? foreach ($props as $prop):
                $propertyId = $prop['ID'];

                ?>
                <tr class="cme_tr cme_tr_<?= $propertyId ?>" style="display: none;">
                    <? if ($propertyId == "PREVIEW_PICTURE" || $propertyId == "DETAIL_PICTURE"): ?>
                        <td class="adm-detail-content-cell-l" width="40%">
                            <?= Loc::getMessage("CETACS_MULTIEDIT_" . $propertyId) ?>
                        </td>
                        <td class="adm-detail-content-cell-r">
                            <?
                            echo CFileInput::Show(
                                "PROP[" . $propertyId . "]", "",
                                array(
                                    "IMAGE" => "Y",
                                    "PATH" => "Y",
                                    "FILE_SIZE" => "Y",
                                    "DIMENSIONS" => "Y",
                                    "IMAGE_POPUP" => "Y",
                                    "MAX_SIZE" => array(
                                        "W" => COption::GetOptionString("iblock", "detail_image_size"),
                                        "H" => COption::GetOptionString("iblock", "detail_image_size"),
                                    )
                                ), array(
                                    'upload' => true,
                                    'medialib' => true,
                                    'file_dialog' => true,
                                    'cloud' => true,
                                    'del' => true,
                                    'description' => false
                                )
                            );
                            ?>
                        </td>
                    <? elseif ($propertyId == "SORT"): ?>
                        <td class="adm-detail-content-cell-l" width="40%">
                            <?= Loc::getMessage("CETACS_MULTIEDIT_SORT") ?>
                        </td>
                        <td class="adm-detail-content-cell-r">
                            <input type="text" value="" name="PROP[SORT]">
                        </td>
                    <? elseif ($prop['USER_TYPE'] == 'HTML'): ?>
                        <td class="adm-detail-content-cell-l" width="40%"><?= $prop["NAME"] ?></td>
                        <td class="adm-detail-content-cell-r">
                            <?
                            $ut = CIBlockProperty::GetUserType($prop['USER_TYPE']);
                            echo call_user_func_array($ut["GetPropertyFieldHtml"],
                                array(
                                    $prop,
                                    array(
                                        "VALUE" => "",
                                        "DESCRIPTION" => ""
                                    ),
                                    array(
                                        "VALUE" => "PROP[" . $prop["ID"] . "][VALUE]",
                                        "DESCRIPTION" => "",
                                        "MODE" => "EDIT_FORM",
                                        "FORM_NAME" => ""
                                    ),
                                ));

                            ?>
                        </td>
                    <? else: ?>
                        <td class="adm-detail-content-cell-l" width="40%"><?= $prop["NAME"] ?></td>
                        <td class="adm-detail-content-cell-r">
                            <? _ShowPropertyField("PROP[{$prop["ID"]}]", $prop, ""); ?>
                            <? if ($prop["PROPERTY_TYPE"] == "L" || $prop['PROPERTY_TYPE'] == 'F'): ?>
                                <input type="hidden" value="" name="PROP[<?= $prop["ID"] ?>][]">
                            <? endif; ?>
                        </td>
                    <? endif; ?>
                </tr>
            <? endforeach; ?>
        </table>
        <input type="hidden" name="cma_action" value="cetacs_multiedit_go"/>
        <input type="hidden" name="PROPERTY_ID" value=""/>
        <input type="hidden" name="TABLE_ID" value="<?= $tableId ?>"/>
        <?= bitrix_sessid_post() ?>
    </form>

    <script>
        var checkInputName = '<?=$checkInputName?>[]';
        var tableId = '<?=$tableId?>';

        document.querySelector("#cetacs_multiedit_props").addEventListener("change", function (e) {
            var val = e.target.value;
            document.querySelectorAll(".cme_tr").forEach(function (tr) {
                tr.style.display = "none"
            });
            document.querySelector(".cme_tr_" + val).style.display = "table-row";
            document.querySelector("input[name=PROPERTY_ID]").setAttribute("VALUE", val);
        });

        BX("cetacs_multiedit_dialog_form").setAttribute("action", top.location.href);

        var inputs = BX.findChild(BX(tableId), {attr: {name: checkInputName}}, true, true);

        inputs.forEach(function (input) {
            if (input.checked) {
                var inp = BX.create("INPUT", {attrs: {type: "hidden", name: checkInputName, value: input.value}});
                BX("cetacs_multiedit_dialog_form").appendChild(inp);
            }
        });

        if (checkInputName === "ID[]" && BX("actallrows_<?= $tableId ?>").checked) {
            var inp = BX.create("INPUT", {attrs: {type: "hidden", name: "action[action_all_rows_<?= $tableId ?>]", value: "Y"}});
            BX("cetacs_multiedit_dialog_form").appendChild(inp);
        }
    </script>
<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");