<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Corsik\YaDelivery\Handler;
use Corsik\YaDelivery\Helper;
use Corsik\YaDelivery\Table\RulesTable;

global $by, $order;

$module_id = 'corsik.yadelivery';
$messages = Loc::loadLanguageFile(__FILE__);
$messagesJS = Loc::loadLanguageFile(dirname(__DIR__) . '/corsik_js_message.php');
Extension::load(["jquery2", "translit"]);

Loader::includeModule('main');
Loader::includeModule($module_id);

$request = Context::getCurrent()->getRequest();
$handler = Handler::getInstance();
$get = $request->getQueryList();
$sites = Handler::getSites(true);
$arrRule = [
    'MIN' => "",
    'MAX' => ""
];

if ($get['ID'] > 0) {
    $ruleTable = new RulesTable();
    $fields = $ruleTable->getList(['select' => ['*'], 'filter' => ['ID' => $get['ID']]])->fetchRaw();
    $arrRule = Helper::JsonDecode($fields['RULE']);
} else {
    $fields = [
        'ID' => 0,
        'ACTIVE' => 'Y',
        'SITE_ID' => $sites['reference_id'][0],
        'TYPE' => $get['type_rule']
    ];
}
/**
 * TODO
 * не сохраняется при не активном ACTIVE
 * так же необходимо убрать обязательно поле ACTIVE при создание складов и зон.
 */
?>
<form id="corsik_create_rule" action="/bitrix/tools/corsik.yadelivery/ajax_admin.php">
    <table width="100%">
        <tbody>
        <input name="SITE_ID" type="hidden" value="<?= $fields['SITE_ID'] ?>">
        <input name="ID" type="hidden" value="<?= $fields['ID'] ?>">
        <input name="TYPE" type="hidden" value="<?= $fields['TYPE'] ?>">
        <? switch ($fields['TYPE']) {
            case 'weight':
            case 'price':
                ?>
                <tr>
                    <td><?= Loc::getMessage("CORSIK_DELIVERY_SERVICE_LABEL_ACTIVE") ?></td>
                    <td class="adm-detail-content-cell-r">
                        <input id="ACTIVE_<?= strtoupper($fields['TYPE']) ?>" type="checkbox" name="ACTIVE"
                               value="Y"
                               class="adm-designed-checkbox" <?= $fields['ACTIVE'] === 'Y' ? 'checked' : '' ?>>
                        <label class="adm-designed-checkbox-label"
                               for="ACTIVE_<?= strtoupper($fields['TYPE']) ?>"></label></td>
                </tr>
                <tr>
                    <td><?= Loc::getMessage("CORSIK_DELIVERY_SERVICE_LABEL_NAME") ?></td>
                    <td><input type="text" name="NAME" value="<?= $fields['NAME'] ?>"></td>
                </tr>
                <tr>
                    <td valign="top"
                        style="padding-right:20px;"><?= Loc::getMessage("CORSIK_DELIVERY_SERVICE_" . strtoupper($fields['TYPE']) . "_LABEL_MIN") ?></td>
                    <td><input type="text" name="RULE[MIN]" value="<?= $arrRule['MIN'] ?>"
                               size="5" placeholder=""></td>
                </tr>
                <tr>
                    <td valign="top"
                        style="padding-right:20px;"><?= Loc::getMessage("CORSIK_DELIVERY_SERVICE_" . strtoupper($fields['TYPE']) . "_LABEL_MAX") ?></td>
                    <td><input type="text" name="RULE[MAX]" value="<?= $arrRule['MAX'] ?>"
                               size="5" placeholder=""></td>
                </tr>
                <tr>
                    <td><?= Loc::getMessage("CORSIK_DELIVERY_SERVICE_LABEL_SORT") ?></td>
                    <td><input type="text" name="SORT" value="<?= $fields['SORT'] ?? 500 ?>"></td>
                </tr>
                <tr>
                    <td><?= Loc::getMessage("CORSIK_DELIVERY_SERVICE_DESCRIPTION") ?></td>
                    <td>
                        <div
                                class="adm-sale-delivery-restriction-descr"><?= Loc::getMessage("CORSIK_DELIVERY_SERVICE_" . strtoupper($fields['TYPE']) . "_DESCRIPTION") ?></div>
                    </td>
                </tr>
                <?
                break;
        } ?>
        </tbody>
    </table>
</form>
