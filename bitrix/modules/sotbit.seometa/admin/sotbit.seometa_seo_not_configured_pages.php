<?

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Text\Emoji;
use Bitrix\Main\UI\Extension;
use Sotbit\Seometa\Helper\AdminSection\AdminTable;
use Sotbit\Seometa\Orm\SeometaNotConfiguredPagesTable;

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");

Loc::loadMessages(__FILE__);

global $APPLICATION;
$id_module = 'sotbit.seometa';

if (!Loader::includeModule('iblock') || !Loader::includeModule($id_module)) {
    die();
}

echo "<script src='/bitrix/js/sotbit.seometa/core_tree.js'></script>";
echo "<link rel='stylesheet' href='/bitrix/css/sotbit.seometa/catalog_cond.css'>";

Loader::includeModule("fileman");

// For menu
CJSCore::Init([
    "jquery"
]);

$POST_RIGHT = $APPLICATION->GetGroupRight($id_module);
if ($POST_RIGHT == "D") {
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}

$aTabs = [
    [
        "DIV" => "edit1",
        "TAB" => Loc::getMessage("SEO_META_NOT_CONFIGURED_TAB_BEHAVIOR"),
        "ICON" => "main_user_edit",
        "TITLE" => Loc::getMessage("SEO_META_NOT_CONFIGURED_TAB_BEHAVIOR_TITLE")
    ],
    [
        "DIV" => "edit2",
        "TAB" => Loc::getMessage("SEO_META_NOT_CONFIGURED_TAB_META"),
        "ICON" => "main_user_edit",
        "TITLE" => GetMessage("SEO_META_NOT_CONFIGURED_TAB_META_TITLE")
    ],
];

$tabControl = new AdminTable("tabControl", $aTabs);
$SITE_ID = '';
if ($_REQUEST['site_id']) {
    $SITE_ID = $_REQUEST['site_id'];
}

if (!$SITE_ID) {
    $rsSites = CSite::GetList($by = "sort",
        $order = "desc",
        []);
    while ($arSite = $rsSites->Fetch()) {
        $SitesAll[$arSite['LID']] = $arSite;
        if ($arSite['DEF'] == 'Y') {
            $SITE_ID = $arSite['LID'];
        }
    }
}
$arFields = [];
if ($SITE_ID) {
    $arFields = SeometaNotConfiguredPagesTable::getBySiteID($SITE_ID) ?: [];
}

if ($_REQUEST && is_array($_REQUEST)) {
    foreach ($_REQUEST as $key => $value) {
        $arFields[$key] = $value;
    }
}

if ($arFields['ID']) {
    $ID = $arFields['ID'];
}

$arDefaultFields = SeometaNotConfiguredPagesTable::getDefaultParams();
if (!isset($bVarsFromForm)) {
    $bVarsFromForm = false;
}

$tabControl->SetFieldsValues(
    $bVarsFromForm,
    $arFields,
    $arDefaultFields
);


$message = null;

// POST
if ($REQUEST_METHOD == "POST" && ($save != "" || $apply != "") && $POST_RIGHT == "W" && check_bitrix_sessid()) {
    $bVarsFromForm = true;
    $metaInfoSettings = [];
    if ($arFields) {
        foreach ($arFields as $key => $item) {
            if (stripos($key, 'ELEMENT_') !== false && stripos($key, '_TYPE') === false ) {
                if (class_exists('\Bitrix\Main\Text\Emoji')) {
                    $item = Emoji::encode($item);
                }

                $metaInfoSettings[$key] = $item;
            }

            if (strpos($key, 'MAIN_') !== false) {
                $mainSettings[$key] = $item;
            }
        }

        if (!isset($_REQUEST['ACTIVE']) || $_REQUEST['ACTIVE'] != 'Y') {
            $arFields['ACTIVE'] = 'N';
        }
    }

    $arFields = [
        "ACTIVE" => $arFields['ACTIVE'] ?: "N",
        "SITE_ID" => $arFields['site_id'],
        "BEHAVIOR_FILTERED_PAGES" => $arFields['BEHAVIOR_FILTERED_PAGES'] ?: 'no_index',
        "BEHAVIOR_PAGINATION_PAGES" => $arFields['BEHAVIOR_PAGINATION_PAGES'] ?: 'no_index',
        'METAINFO_SETTINGS' => serialize($metaInfoSettings),
        'MAIN_SETTINGS' => serialize($mainSettings)
    ];

    $res = true;
    if ($ID > 0) {
        $result = SeometaNotConfiguredPagesTable::update($ID, $arFields);
        if (!$result->isSuccess()) {
            $errors = $result->getErrorMessages();
            $res = false;
        }
    } else {
        $result = SeometaNotConfiguredPagesTable::add($arFields);
        if ($result->isSuccess()) {
            $ID = $result->getId();
            $res = true;
        } else {
            $errors = $result->getErrorMessages();
        }
    }

    if ($res && empty($errors)) {
        if ($apply != "") {
            LocalRedirect("/bitrix/admin/sotbit.seometa_seo_not_configured_pages.php?ID=" . $ID . "&mess=ok&site_id=" . $SITE_ID . "&lang=" . LANG . "&" . $tabControl->ActiveTabParam());
        }
    }
}

$APPLICATION->SetTitle(Loc::getMessage('SEO_META_NOT_CONFIGURED_TITLE'));
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");

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

//<editor-fold desc="InitParams">
if (!empty($errors) && is_array($errors)) {
    CAdminMessage::ShowMessage(array(
        "MESSAGE" => $errors[0]
    ));
}

if ($_REQUEST["mess"] == "ok" && $ID > 0) {
    CAdminMessage::ShowMessage(array(
        "MESSAGE" => Loc::getMessage("SEO_META_NOT_CONFIGURED_SAVED"),
        "TYPE" => "OK"
    ));
}

// Menu for meta
$PropMenu = CCSeoMeta::PropMenu($arFields['MAIN_INFOBLOCK']);
$PropMenuTemplate = CCSeoMeta::PropMenuTemplate($arFields['MAIN_INFOBLOCK']);
$arIBlockTypeSel = [];
$SitesAll = [];
$rsSites = CSite::GetList($by = "sort",
    $order = "desc",
    []);
while ($arSite = $rsSites->Fetch()) {
    $SitesAll[$arSite['LID']] = $arSite;
}

$arIBlockType = CIBlockParameters::GetIBlockTypes();
foreach ($arIBlockType as $code => $val) {
    $arIBlockTypeSel["REFERENCE_ID"][] = $code;
    $arIBlockTypeSel["REFERENCE"][] = $val;
}

if ($arFields["MAIN_TYPE_OF_INFOBLOCK"]) {
    $catalogs = [];
    $rsIBlocks = CIBlockElement::GetList([],
        [],
        false,
        false,
        [
            'IBLOCK_ID'
        ]);
    while ($arIBlock = $rsIBlocks->Fetch()) {
        array_push($catalogs, $arIBlock['IBLOCK_ID']);
    }

    $rsIBlock = CIBlock::GetList(
        [
            "sort" => "asc"
        ],
        [
            "TYPE" => $arFields["MAIN_TYPE_OF_INFOBLOCK"],
            "ACTIVE" => "Y"
        ]);
    while ($arr = $rsIBlock->Fetch()) {
        if (in_array($arr["ID"], $catalogs)) {
            $arIBlockSel["REFERENCE_ID"][] = $arr["ID"];
            $arIBlockSel["REFERENCE"][] = "[" . $arr["ID"] . "] " . $arr["NAME"];
        }
    }
}

$FilterType = [
    'default' => Loc::getMessage('SEO_META_FILTERS_default'),
    'bitrix_chpu' => Loc::getMessage('SEO_META_FILTERS_bitrix_chpu'),
    'bitrix_not_chpu' => Loc::getMessage('SEO_META_FILTERS_bitrix_not_chpu'),
    'misshop_chpu' => Loc::getMessage('SEO_META_FILTERS_misshop_chpu'),
    'combox_chpu' => Loc::getMessage('SEO_META_FILTERS_combox_chpu'),
//	'combox_not_chpu' => Loc::self::getMessage('SEO_META_FILTERS_combox_not_chpu')
];

$Priority = [
    '0.0' => '0.0',
    '0.1' => '0.1',
    '0.2' => '0.2',
    '0.3' => '0.3',
    '0.4' => '0.4',
    '0.5' => '0.5',
    '0.6' => '0.6',
    '0.7' => '0.7',
    '0.8' => '0.8',
    '0.9' => '0.9',
    '1.0' => '1.0'
];

$ChangeFreq = [
    'always' => Loc::getMessage("SEO_META_EDIT_CHANGEFREQ_ALWAYS"),
    'hourly' => Loc::getMessage("SEO_META_EDIT_CHANGEFREQ_HOURLY"),
    'daily' => Loc::getMessage("SEO_META_EDIT_CHANGEFREQ_DAILY"),
    'weekly' => Loc::getMessage("SEO_META_EDIT_CHANGEFREQ_WEEKLY"),
    'monthly' => Loc::getMessage("SEO_META_EDIT_CHANGEFREQ_MONTHLY"),
    'yearly' => Loc::getMessage("SEO_META_EDIT_CHANGEFREQ_YEARLY"),
    'never' => Loc::getMessage("SEO_META_EDIT_CHANGEFREQ_NEVER")
];

$behaviorParams = [
    'no_index' => Loc::getMessage('SEO_META_NOT_CONFIGURED_INDEX'),
    'canonical' => Loc::getMessage('SEO_META_NOT_CONFIGURED_ADD_CANONICAL')
];

//</editor-fold>

$tabControl->Begin([
    "FORM_ACTION" => $APPLICATION->GetCurPage() . '?lang=' . LANG . '&site_id=' . $SITE_ID
]);

//<editor-fold desc="Behavior">
$tabControl->BeginNextFormTab();

$tabControl->AddCheckBoxField(
    "ACTIVE",
    Loc::getMessage("SEO_META_NOT_CONFIGURED_ACT"),
    false,
    "Y",
    $arFields['ACTIVE'] == "Y" ? 'checked' : ''
);

$tabControl->AddDropDownField(
    'BEHAVIOR_FILTERED_PAGES',
    Loc::getMessage('SEO_META_NOT_CONFIGURED_BEHAVIOR_FILTERED_PAGES'),
    false,
    $behaviorParams,
    $arFields['BEHAVIOR_FILTERED_PAGES'] ?: false
);

$tabControl->AddDropDownField(
    'BEHAVIOR_PAGINATION_PAGES',
    Loc::getMessage('SEO_META_NOT_CONFIGURED_BEHAVIOR_PAGINATION_PAGES'),
    false,
    $behaviorParams,
    $arFields['BEHAVIOR_PAGINATION_PAGES']
);

$tabControl->BeginCustomField("MAIN_TYPE_OF_INFOBLOCK",
    Loc::getMessage('SEO_META_NOT_CONFIGURED_TYPE_OF_INFOBLOCK')
);
?>
<tr id="tr_MAIN_TYPE_OF_INFOBLOCK">
    <td width="40%"><?= $tabControl->GetCustomLabelHTML(); ?></td>
    <td width="60%">
        <?
        echo SelectBoxFromArray('MAIN_TYPE_OF_INFOBLOCK',
            $arIBlockTypeSel,
            $arFields['MAIN_TYPE_OF_INFOBLOCK'],
            '',
            'style="min-width: 350px; margin-right: 7px;"',
            false,
            '');
        echo '<input type="submit" name="refresh" value="OK" />';
        ?>
    </td>
</tr>
<?
$tabControl->EndCustomField("MAIN_TYPE_OF_INFOBLOCK");

$tabControl->BeginCustomField("MAIN_INFOBLOCK",
    Loc::getMessage('SEO_META_NOT_CONFIGURED_INFOBLOCK')
);
?>
<tr id="MAIN_INFOBLOCK">
    <td width="40%"><?= $tabControl->GetCustomLabelHTML(); ?></td>
    <td width="60%">
        <?
        echo SelectBoxFromArray('MAIN_INFOBLOCK',
            $arIBlockSel,
            $arFields['MAIN_INFOBLOCK'],
            '',
            'style="min-width: 350px; margin-right: 7px;"',
            false,
            '');
        echo '<input type="submit" name="refresh" value="OK" />';
        ?>
    </td>
</tr>
<?
$tabControl->EndCustomField("MAIN_INFOBLOCK");

$tabControl->AddDropDownField("MAIN_FILTER_TYPE",
    Loc::getMessage('SEO_META_NOT_CONFIGURED_FILTER_TYPE'),
    false,
    $FilterType,
    $arFields['MAIN_FILTER_TYPE']);
//</editor-fold>

//<editor-fold desc="MetaInfo">
$tabControl->BeginNextFormTab();

$tabControl->AddFieldGroup(
    'GROUP_ELEMENT',
    Loc::getMessage('SEO_META_NOT_CONFIGURED_META_GROUP'),
    [
        'META_ELEMENT_TITLE',
        'META_ELEMENT_KEYWORDS',
        'META_ELEMENT_DESCRIPTION',
        'META_ELEMENT_PAGE_TITLE',
        'META_ELEMENT_BREADCRUMB_TITLE',
        'META_ELEMENT_TOP_DESC',
        'META_ELEMENT_BOTTOM_DESC',
        'META_ELEMENT_ADD_DESC',
        'META_NOTE'
    ]
);

$tabControl->AddTextField(
    'META_ELEMENT_TITLE',
    Loc::getMessage('SEO_META_NOT_CONFIGURED_META_ELEMENT_TITLE'),
    false,
    [
        'textarea' => [
            'style="width: 90%;"'
        ],
        'propmenu' => 'width="10%"'
    ],
    false,
    $PropMenu
);

$tabControl->AddTextField(
    'META_ELEMENT_KEYWORDS',
    Loc::getMessage('SEO_META_NOT_CONFIGURED_META_ELEMENT_KEYWORDS'),
    false,
    [
        'textarea' => [
            'style="width: 90%;"'
        ],
        'propmenu' => 'width="10%"'
    ],
    false,
    $PropMenu
);

$tabControl->AddTextField(
    'META_ELEMENT_DESCRIPTION',
    Loc::getMessage('SEO_META_NOT_CONFIGURED_META_ELEMENT_DESCRIPTION'),
    false,
    [
        'textarea' => [
            'style="width: 90%;"'
        ],
        'propmenu' => 'width="10%"'
    ],
    false,
    $PropMenu
);

$tabControl->AddTextField(
    'META_ELEMENT_PAGE_TITLE',
    Loc::getMessage('SEO_META_NOT_CONFIGURED_META_ELEMENT_PAGE_TITLE'),
    false,
    [
        'textarea' => [
            'style="width: 90%;"'
        ],
        'propmenu' => 'width="10%"'
    ],
    false,
    $PropMenu
);

$tabControl->AddTextField(
    'META_ELEMENT_BREADCRUMB_TITLE',
    Loc::getMessage('SEO_META_NOT_CONFIGURED_META_ELEMENT_BREADCRUMB_TITLE'),
    false,
    [
        'textarea' => [
            'style="width: 90%;"'
        ],
        'propmenu' => 'width="10%"'
    ],
    false,
    $PropMenu
);

$tabControl->BeginCustomField("META_ELEMENT_TOP_DESC",
    Loc::getMessage('SEO_META_NOT_CONFIGURED_META_ELEMENT_TOP_DESC'));
?>
<tr class="adm-detail-valign-top">
    <td width="40%"><? echo $tabControl->GetCustomLabelHTML(); ?></td>
    <td width="50%">
        <?
        CFileMan::AddHTMLEditorFrame(
            "META_ELEMENT_TOP_DESC",
            $arFields['META_ELEMENT_TOP_DESC'] ?: '',
            "META_ELEMENT_TOP_DESC_TYPE",
            "html",
            [
                'height' => 150,
                'width' => '100%'
            ],
            "N",
            0,
            "",
            "",
            "ru"//??? ????
        );
        ?>
    </td>
    <td width="10%"
        align="left">
        <?= $PropMenu; ?>
    </td>
</tr>
<?
$tabControl->EndCustomField("META_ELEMENT_TOP_DESC");

$tabControl->BeginCustomField("META_ELEMENT_BOTTOM_DESC",
    Loc::getMessage('SEO_META_NOT_CONFIGURED_META_ELEMENT_BOTTOM_DESC'));
?>
<tr class="adm-detail-valign-top">
    <td width="40%"><?= $tabControl->GetCustomLabelHTML(); ?></td>
    <td width="50%">
        <?
        CFileMan::AddHTMLEditorFrame(
            "META_ELEMENT_BOTTOM_DESC",
            $arFields['META_ELEMENT_BOTTOM_DESC'] ?: '',
            "META_ELEMENT_BOTTOM_DESC_TYPE",
            "html",
            [
                'height' => 150,
                'width' => '100%'
            ],
            "N",
            0,
            "",
            "",
            "ru"//??? ????
        );
        ?>
    </td>
    <td width="10%"
        align="left">
        <?= $PropMenu; ?>
    </td>
</tr>
<?
$tabControl->EndCustomField("META_ELEMENT_BOTTOM_DESC");

$tabControl->BeginCustomField("META_ELEMENT_ADD_DESC",
    Loc::getMessage('SEO_META_NOT_CONFIGURED_META_ELEMENT_ADD_DESC'));
?>
<tr class="adm-detail-valign-top">
    <td width="40%"><?= $tabControl->GetCustomLabelHTML(); ?></td>
    <td width="50%">
        <?
        CFileMan::AddHTMLEditorFrame(
            "META_ELEMENT_ADD_DESC",
            $arFields['META_ELEMENT_ADD_DESC'] ?: '',
            "META_ELEMENT_ADD_DESC_TYPE",
            "html",
            [
                'height' => 150,
                'width' => '100%'
            ],
            "N",
            0,
            "",
            "",
            "ru"//??? ????
        );
        ?>
    </td>
    <td width="10%"
        align="left">
        <?= $PropMenu; ?>
    </td>
</tr>
<?
$tabControl->EndCustomField("META_ELEMENT_ADD_DESC");

$tabControl->BeginCustomField("META_META_NOTE","");
Extension::load("ui.hint");
?>
<tr>
    <td colspan="3"
        align="center">
        <div class="adm-info-message-wrap">
            <div class="adm-info-message"
                 style="text-align:left;">
                <?= Loc::getMessage("SEO_META_NOT_CONFIGURED_META_NOTE") ?>
            </div>
        </div>
    </td>
</tr>
<script type="text/javascript">
    BX.ready(function () {
        BX.UI.Hint.init(BX('my-container'));
    })
</script>
<?
$tabControl->EndCustomField("META_META_NOTE");
//</editor-fold>

$tabControl->BeginCustomField("HID",'');
?>
<?= bitrix_sessid_post(); ?>
<input type="hidden"
       name="lang"
       value="<?= LANG ?>">
<? if ($ID > 0): ?>
    <input type="hidden"
           name="ID"
           value="<?= $ID ?>">
<? endif; ?>
<?
$tabControl->EndCustomField("HID");

$arButtonsParams = [
    'btnSave' => false
];

$tabControl->Buttons($arButtonsParams);
$tabControl->Show();

Asset::getInstance()->addString("
<link rel='stylesheet' href='//code.jquery.com/ui/1.12.0/themes/smoothness/jquery-ui.css'>
<script src='//code.jquery.com/ui/1.12.0/jquery-ui.js'></script>
<script>
$(document).on('click','.sotbit-seo-menu-button',function(){
	var NavMenu=$(this).siblings( '.navmenu-v' );
	if(NavMenu.css('display')=='none')
	{
		$('.navmenu-v').css('display','none');
		NavMenu.css('display','block');
		NavMenu.find('ul').css('right',NavMenu.innerWidth());
	}
	else
	{
		$('.navmenu-v').css('display','none');
		NavMenu.css('display','none');
	}
});

$(document).on('click', '.sotbit-seo-menu-button-custom', function () {
    var navMenu = $(this).siblings('.navmenu-v');

    if (navMenu.css('display') == 'none') {
        $('.navmenu-v').css('display','none');
        navMenu.css('display', 'block');
        navMenu.find('.metainform__item').css('right', navMenu.innerWidth());
    } else {
        $('.navmenu-v').css('display','none');
        navMenu.css('display', 'none');
    }
});

$(document).on('click','.navmenu-v li.with-prop ',function(){
	if($(this).data( 'prop' )!== 'undefined')
	{
		if($(this).closest('tr').find('iframe').length>0)
			{
				$(this).closest('tr').find('iframe').contents().find('body').append($(this).data( 'prop' ));
				$(this).closest('tr').find('textarea').insertAtCaret($(this).data( 'prop' ));
			}
		else
			{
				$(this).closest('tr').find('textarea').insertAtCaret($(this).data( 'prop' ));
				$(this).closest('tr').find('input[name=\"META_TEMPLATE[TEMPLATE_NEW_URL]\"]').insertAtCaret($(this).data( 'prop' ));
				if($(this).closest('tr').find('textarea').length > 0)
					triggerTextarea($(this).closest('tr').find('textarea'));
			}
	}
});

$(document).on('click', '.navmenu-v .metainform__item .with-prop ', function () {
    if ($(this).data('prop') !== 'undefined') {
        if ($(this).closest('tr').find('iframe').length > 0) {
            $(this).closest('tr').find('iframe').contents().find('body').append($(this).data('prop'));
            $(this).closest('tr').find('textarea').insertAtCaret($(this).data('prop'));
        } else {
            $(this).closest('tr').find('textarea').insertAtCaret($(this).data('prop'));
            $(this).closest('tr').find('input[name=\"META_TEMPLATE[TEMPLATE_NEW_URL]\"]').insertAtCaret($(this).data('prop'));
            if ($(this).closest('tr').find('textarea').length > 0)
                triggerTextarea($(this).closest('tr').find('textarea'));
        }

    }
});

//For add in textarea in focus place
jQuery.fn.extend({
	insertAtCaret: function(myValue){
		return this.each(function(i) {
			if (document.selection) {
				// Internet Explorer
				this.focus();
				var sel = document.selection.createRange();
				sel.text = myValue;
				this.focus();
			}
			else if (this.selectionStart || this.selectionStart == '0') {
				//  Firefox and Webkit
				var startPos = this.selectionStart;
				var endPos = this.selectionEnd;
				var scrollTop = this.scrollTop;
				this.value = this.value.substring(0, startPos)+myValue+this.value.substring(endPos,this.value.length);
				this.focus();
				this.selectionStart = startPos + myValue.length;
				this.selectionEnd = startPos + myValue.length;
				this.scrollTop = scrollTop;
			} else {
				this.value += myValue;
				this.focus();
			}
		})
	}
});

//For menu
navHover = function() {
	var lis = document.getElementByClass('navmenu-v').getElementsByTagName('LI');
	for (var i=0; i<lis.length; i++) {
		lis[i].onmouseover=function() {
			this.className+=' iehover';
		}
		lis[i].onmouseout=function() {
			this.className=this.className.replace(new RegExp(' iehover\\b'), '');
		}
	}
}
if (window.attachEvent) window.attachEvent('onload', navHover);
</script>",
    true);
?>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php"); ?>
