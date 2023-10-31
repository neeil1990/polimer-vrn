<?
use Sotbit\Seometa\Orm\SitemapSectionTable;
use Bitrix\Iblock;
use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Entity;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Type;
use Bitrix\Main\Localization\Loc;

require_once ($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");

$moduleId = 'sotbit.seometa';
if (!Loader::includeModule('iblock') || !Loader::includeModule($moduleId)) {
    die();
}

Loc::loadMessages(__FILE__);
$POST_RIGHT = $APPLICATION->GetGroupRight( $moduleId );
if ($POST_RIGHT == "D") {
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}

$aTabs = [
    [
        "DIV" => "edit1",
        "TAB" => GetMessage("SEO_META_EDIT_TAB_SECTION_TITLE"),
        "ICON" => "main_user_edit",
        "TITLE" => GetMessage("SEO_META_EDIT_TAB_SECTION_TITLE")
    ],
];
$tabControl = new CAdminForm( "tabControl", $aTabs );
$parentID = 0;
if (isset($_REQUEST["parent"]) && $_REQUEST["parent"]) {
    $parentID = $_REQUEST["parent"];
}

$message = null;

// POST
if ($REQUEST_METHOD == "POST" && ($save != "" || $apply != "") && $POST_RIGHT == "W" && check_bitrix_sessid()) {
	$arFields = [
        "ACTIVE" => ($ACTIVE != "Y" ? "N" : "Y"),
        "NAME" => $NAME,
        "SORT" => $SORT,
        "DATE_CHANGE" => new Type\DateTime( date( 'Y-m-d H:i:s' ), 'Y-m-d H:i:s' ),
        "DESCRIPTION" => $DESCRIPTION,
        "PARENT_CATEGORY_ID" => $PARENT_CATEGORY_ID,
    ];
    $res = false;
    if ($ID > 0) {
        $result = SitemapSectionTable::update($ID,
            $arFields);
        if (!$result->isSuccess()) {
            $errors = $result->getErrorMessages();
        } else {
            $res = true;
        }
    } else {
        $arFields["DATE_CREATE"] = new Type\DateTime(date('Y-m-d H:i:s'), 'Y-m-d H:i:s');
        $result = SitemapSectionTable::add($arFields);
        if ($result->isSuccess()) {
            $ID = $result->getId();
            $res = true;
        } else {
            $errors = $result->getErrorMessages();
        }
    }

    if ($res) {
        if ($apply != "") {
            LocalRedirect("/bitrix/admin/sotbit.seometa_section_edit.php?ID=" . $ID . "&mess=ok&lang=" . LANG . "&" . $tabControl->ActiveTabParam());
        } else {
            LocalRedirect("/bitrix/admin/sotbit.seometa_list.php?lang=" . LANG);
        }
    }
}

if ($ID > 0) {
    $Section = SitemapSectionTable::getById($ID)->Fetch();
}

$APPLICATION->SetTitle($ID > 0 ? GetMessage("SEO_META_EDIT_EDIT") . $ID : GetMessage("SEO_META_EDIT_ADD"));
require ($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");

if (CCSeoMeta::ReturnDemo() == 2) {
    ?>
    <div class="adm-info-message-wrap adm-info-message-red">
        <div class="adm-info-message">
            <div class="adm-info-message-title"><?= Loc::getMessage("SEO_META_DEMO") ?></div>
            <div class="adm-info-message-icon"></div>
        </div>
    </div>
    <?
}

if (CCSeoMeta::ReturnDemo() == 3 || CCSeoMeta::ReturnDemo() == 0) {
    ?>
    <div class="adm-info-message-wrap adm-info-message-red">
        <div class="adm-info-message">
            <div class="adm-info-message-title"><?= Loc::getMessage("SEO_META_DEMO_END") ?></div>
            <div class="adm-info-message-icon"></div>
        </div>
    </div>
    <?
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");
    return '';
}

$aMenu = [
    [
        "TEXT" => GetMessage("SEO_META_EDIT_LIST"),
        "TITLE" => GetMessage("SEO_META_EDIT_LIST_TITLE"),
        "LINK" => "sotbit.seometa_list.php?lang=" . LANG,
        "ICON" => "btn_list"
    ]
];
if ($parentID > 0) {
    $aMenu[] = [
        "SEPARATOR" => "Y"
    ];
    $aMenu[] = [
        "TEXT" => GetMessage("SEO_META_EDIT_ADD"),
        "TITLE" => GetMessage("SEO_META_EDIT_ADD_TITLE"),
        "LINK" => "sotbit.seometa_section_edit.php?&lang=" . LANG,
        "ICON" => "btn_new"
    ];
    $aMenu[] = [
        "TEXT" => GetMessage("SEO_META_EDIT_DEL"),
        "TITLE" => GetMessage("SEO_META_EDIT_DEL_TITLE"),
        "LINK" => "javascript:if(confirm('" . GetMessage("SEO_META_EDIT_DEL_CONF") . "'))window.location='sotbit.seometa_list.php?ID=S" . $parentID . "&action=delete&lang=" . LANG . "&" . bitrix_sessid_get() . "';",
        "ICON" => "btn_delete"
    ];
}

$context = new CAdminContextMenu( $aMenu );
$context->Show();
if (!empty($errors) && is_array($errors)) {
    CAdminMessage::ShowMessage([
        "MESSAGE" => $errors[0]
    ]);
}

if ($_REQUEST["mess"] == "ok" && $ID > 0) {
    CAdminMessage::ShowMessage([
        "MESSAGE" => GetMessage("SEO_META_EDIT_SAVED"),
        "TYPE" => "OK"
    ]);
}

// Calculate start values
//***All section***
$AllSections['REFERENCE_ID'][0] = 0;
$AllSections['REFERENCE'][0] = GetMessage("SEO_META_CHECK_CATEGORY");
$RsAllSections = SitemapSectionTable::getList([
    'select' => ['*'],
    'filter' => [],
    'order' => ['SORT' => 'ASC']
]);
while ($AllSection = $RsAllSections->Fetch()) {
    $AllSections['REFERENCE_ID'][] = $AllSection['ID'];
    $AllSections['REFERENCE'][] = $AllSection['NAME'];
}
//

$tabControl->Begin( [
    "FORM_ACTION" => $APPLICATION->GetCurPage()
]);

$tabControl->BeginNextFormTab();

$tabControl->AddViewField( 'ID', GetMessage( "SEO_META_EDIT_ID" ), $ID); // ID
$tabControl->AddCheckBoxField("ACTIVE",
    GetMessage("SEO_META_EDIT_ACT"),
    false,
    "Y",
    $Section['ACTIVE'] == "Y" || !isset($Section['ACTIVE'])
); // ??????????
$tabControl->AddEditField("SORT",
    GetMessage("SEO_META_EDIT_SORT"),
    true,
    [
        "size" => 6,
        "maxlength" => 255
    ],
    $Section['SORT'] ?: 100
);

$tabControl->AddEditField("NAME",
    GetMessage("SEO_META_EDIT_NAME"),
    true,
    [
        "size" => 50,
        "maxlength" => 255
    ],
    htmlspecialcharsbx($Section['NAME'])
);

$tabControl->BeginCustomField("PARENT_CATEGORY_ID",
    GetMessage('SEO_META_EDIT_PARENT_CATEGORY_ID')
);
?>
<tr id="PARENT_CATEGORY_ID">
	<td width="40%"><?= $tabControl->GetCustomLabelHTML(); ?></td>
	<td width="60%">
        <?= SelectBoxFromArray('PARENT_CATEGORY_ID',
            $AllSections,
            $Section['PARENT_CATEGORY_ID'] ?: $parentID,
            '',
            false,
            '',
            'style="min-width:350px"');
        ?>
    </td>
</tr><?
$tabControl->EndCustomField( "PARENT_CATEGORY_ID" );

$tabControl->AddTextField( 'DESCRIPTION', GetMessage( "SEO_META_EDIT_DESCRIPTION" ), $Section['DESCRIPTION'], false );
$tabControl->AddViewField( 'DATE_CREATE_TEXT', GetMessage( "SEO_META_EDIT_DATE_CREATE" ), $Section['DATE_CREATE']);
$tabControl->AddViewField( 'DATE_CHANGE_TEXT', GetMessage( "SEO_META_EDIT_DATE_CHANGE" ), $Section['DATE_CHANGE']);
$tabControl->BeginCustomField( "SECTION_NOTE", GetMessage( 'SEO_META_EDIT_SECTION_NOTE' ));
$tabControl->EndCustomField( "SECTION_NOTE" );
$tabControl->BeginCustomField( "HID", '');
?>
<?= bitrix_sessid_post();?>
<input type="hidden" name="lang" value="<?=LANG?>">
<? if ($ID > 0 && !$bCopy): ?>
    <input type="hidden"
           name="ID"
           value="<?= $ID ?>">
<? endif; ?>
<?
$tabControl->EndCustomField( "HID" );

$arButtonsParams = [
    "disabled" => $readOnly,
    "back_url" => "/bitrix/admin/sotbit.seometa_list.php?lang=" . LANG
];
$tabControl->Buttons( $arButtonsParams );
$tabControl->Show();

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");?>