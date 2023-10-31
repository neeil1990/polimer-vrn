<?

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Text\Emoji;
use Bitrix\Main\Type;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\UI\FileInput;
use Sotbit\Seometa\Orm\ConditionTable;
use Sotbit\Seometa\Helper\AdminSection\AdminTable;
use Sotbit\Seometa\Orm\OpengraphTable;
use Sotbit\Seometa\Orm\SeometaUrlTable;
use Sotbit\Seometa\Orm\SitemapSectionTable;
use Sotbit\Seometa\Orm\TwitterCardTable;


require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");

Loc::loadMessages(__FILE__);

const MIN_SEO_TITLE = 50;
const MAX_SEO_TITLE = 70;

const MIN_SEO_KEY = 120;
const MAX_SEO_KEY = 150;

const MIN_SEO_DESCR = 130;
const MAX_SEO_DESCR = 180;

global $APPLICATION;

$id_module = 'sotbit.seometa';

if (!Loader::includeModule('iblock') || !Loader::includeModule($id_module)) {
    die();
}

Loader::includeModule("fileman");

// For menu
CJSCore::Init(array(
    "jquery"
));

if ($_REQUEST['action'] !== "get_section_list") {
    $APPLICATION->SetTitle(!empty($ID) ? GetMessage("SEO_META_EDIT_EDIT") . $ID : GetMessage("SEO_META_EDIT_ADD"));
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
}

if ($_REQUEST['action'] == 'delete' && $ID > 0) {
    $result = ConditionTable::delete($ID);
    SeometaUrlTable::deleteByOptions($ID, false, 'all');
    if ($result->isSuccess()) {
        LocalRedirect('/bitrix/admin/sotbit.seometa_list.php?lang=' . LANGUAGE_ID);
    }
}

$POST_RIGHT = $APPLICATION->GetGroupRight($id_module);
if ($POST_RIGHT == "D") {
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}

$aTabs = [
    [
        "DIV" => "edit1",
        "TAB" => GetMessage("SEO_META_EDIT_TAB_CONDITION"),
        "ICON" => "main_user_edit",
        "TITLE" => GetMessage("SEO_META_EDIT_TAB_CONDITION_TITLE")
    ],
    [
        "DIV" => "edit2",
        "TAB" => GetMessage("SEO_META_EDIT_TAB_META"),
        "ICON" => "main_user_edit",
        "TITLE" => GetMessage("SEO_META_EDIT_TAB_META_TITLE")
    ],
    [
        "DIV" => "edit3",
        "TAB" => GetMessage("SEO_META_EDIT_TAB_URL"),
        "ICON" => "main_user_edit",
        "TITLE" => GetMessage("SEO_META_EDIT_TAB_URL")
    ],
    [
        "DIV" => "edit4",
        "TAB" => GetMessage("SEO_META_EDIT_TAB_OG_TW"),
        "ICON" => "main_user_edit",
        "TITLE" => GetMessage("SEO_META_EDIT_TAB_OG_TW")
    ],
    [
        "DIV" => "edit5",
        "TAB" => GetMessage("SEO_META_EDIT_TAB_VIDEO"),
        "ICON" => "main_user_edit",
        "TITLE" => GetMessage("SEO_META_EDIT_TAB_VIDEO")
    ]
];

$tabControl = new AdminTable("tabControl", $aTabs);
$ID = intval($ID);
if ($ID > 0) {
    $conditionRes = ConditionTable::getById($ID);
    $condition = $conditionRes->fetch();
}

$arParamsName = [
    'NAME',
    'ACTIVE',
    'SEARCH',
    'SORT',
    'CATEGORY_ID',
    'SITES',
    'TYPE_OF_INFOBLOCK',
    'INFOBLOCK',
    'SECTIONS',
    'RULE',
    'META',
    'NO_INDEX',
    'STRONG',
    'PRIORITY',
    'CHANGEFREQ',
    'FILTER_TYPE',
    'TAG',
    'HIDE_IN_SECTION',
    'CONDITION_TAG',
    'STRICT_RELINKING',
    'GENERATE_AJAX',
];

foreach($arParamsName as $paramName) {
    if(!empty($_REQUEST[$paramName])) {
        $condition[$paramName == 'section' ? 'CATEGORY_ID' : $paramName] = $_REQUEST[$paramName];
    }
}

$arDefaultFields = [];
if (!empty($_REQUEST['generate_errors'])){
    CAdminMessage::ShowMessage([
        "MESSAGE" => Loc::getMessage('SEO_META_GENERATE_ERROR'),
    ]);
}

if($_REQUEST['generate_chpu']){
    $_REQUEST['action'] = 'generate_chpu';
}

//<editor-fold desc="ACTION">
if (!empty($_REQUEST['action'])) {

    $errors = [];
    if ($_REQUEST['action'] == "copy" && !empty($ID)) {
        $conditionRes = ConditionTable::getById($ID);
        $condition = $conditionRes->fetch();
        $arFields = [
            "ACTIVE" => $condition['ACTIVE'],
            "SEARCH" => $condition['SEARCH'],
            "STRONG" => $condition['STRONG'],
            "NAME" => $condition['NAME'],
            "CATEGORY_ID" => $condition['CATEGORY_ID'],
            "SORT" => $condition['SORT'],
            "SITES" => $condition['SITES'],
            "TYPE_OF_INFOBLOCK" => $condition['TYPE_OF_INFOBLOCK'],
            "INFOBLOCK" => $condition['INFOBLOCK'],
            "DATE_CHANGE" => new Type\DateTime(date('Y-m-d H:i:s'),
                'Y-m-d H:i:s'),
            "SECTIONS" => $condition['SECTIONS'],
            "RULE" => $condition['RULE'],
            "META" => $condition['META'],
            "FILTER_TYPE" => $condition['FILTER_TYPE'],
            "NO_INDEX" => $condition['NO_INDEX'],
            "TAG" => $condition['TAG'],
            "CONDITION_TAG" => $condition['CONDITION_TAG'],
            "PRIORITY" => $condition['PRIORITY'],
            "CHANGEFREQ" => $condition['CHANGEFREQ']
        ];

        $result = ConditionTable::add($arFields);
        if ($result->isSuccess()) {
            $ID = $result->getId();
            LocalRedirect("/bitrix/admin/sotbit.seometa_edit.php?ID=" . $ID . "&lang=" . LANG);
        }else{
            $errors[] = $result->getErrorMessages();
        }
    } elseif ($_REQUEST['action'] == "generate_chpu") {
        $sectionId = false;
        $isProgress = false;
        $isError = false;

        if (!empty($_REQUEST['currentSection'])) {
            $sectionId = array($_REQUEST['currentSection']);
        }

        if (!empty($_REQUEST['isProgress'])) {
            $isProgress = true;
        }

        if (isset($_REQUEST['isError']) && $_REQUEST['isError'] !== 'false') {
            $isError = true;
        }

        if (!empty($ID)) {
            $chpu = ConditionTable::generateUrlForCondition($ID,
                $sectionId,
                $isProgress,
                $isError
            );
            if(!$chpu){
               echo CAdminMessage::ShowMessage([
                    "MESSAGE" => Loc::getMessage('SEO_META_GENERATE_ERROR'),
                ]);
            }
        }
    } elseif ($_REQUEST['action'] == "get_section_list") {
        $sectionList = ConditionTable::getSectionList($ID);
        echo json_encode($sectionList);
        die;
    } elseif ($_REQUEST['action'] == "delete_last_chpu") {
        if (!empty($_REQUEST['currentSection']) && !empty($_REQUEST['ID'])) {
            SeometaUrlTable::deleteByOptions($_REQUEST['ID'],
                $_REQUEST['currentSection']);
        }
        die;
    }
}
//</editor-fold>

//<editor-fold desc="POST">
if ($REQUEST_METHOD == "POST" && ($save != "" || $apply != "") && $POST_RIGHT == "W" && check_bitrix_sessid()) {
    $bVarsFromForm = true;

    if (is_array($ELEMENT_FILE)) {
        $arELEMENT_FILE = \CIBlock::makeFileArray($ELEMENT_FILE,
            ${"ELEMENT_FILE_del"} === "Y",
            $_REQUEST['ELEMENT_FILE_descr']);
        $fid = CFile::SaveFile($arELEMENT_FILE,
            "seometa");
    } elseif (is_numeric($ELEMENT_FILE)) {
        $fid = $ELEMENT_FILE;
    } else {
        $arELEMENT_FILE = \CIBlock::makeFileArray($ELEMENT_FILE);
        $fid = CFile::SaveFile($arELEMENT_FILE,
            "seometa");
    }

    $META_TEMPLATE['ELEMENT_TOP_DESC'] = $ELEMENT_TOP_DESC;
    $META_TEMPLATE['ELEMENT_BOTTOM_DESC'] = $ELEMENT_BOTTOM_DESC;
    $META_TEMPLATE['ELEMENT_ADD_DESC'] = $ELEMENT_ADD_DESC;
    $META_TEMPLATE['ELEMENT_TOP_DESC_TYPE'] = $ELEMENT_TOP_DESC_TYPE;
    $META_TEMPLATE['ELEMENT_BOTTOM_DESC_TYPE'] = $ELEMENT_BOTTOM_DESC_TYPE;
    $META_TEMPLATE['ELEMENT_ADD_DESC_TYPE'] = $ELEMENT_ADD_DESC_TYPE;
    $META_TEMPLATE['ELEMENT_FILE'] = $fid;

    $META_TEMPLATE['TEMPLATE_NEW_URL'] = trim($META_TEMPLATE['TEMPLATE_NEW_URL']);
    $META_TEMPLATE['SPACE_REPLACEMENT'] = !empty($META_TEMPLATE['SPACE_REPLACEMENT']) ? trim($META_TEMPLATE['SPACE_REPLACEMENT']) : '-';

    if (isset($ELEMENT_FILE_del) && $ELEMENT_FILE_del == 'Y') {
        unset($META_TEMPLATE['ELEMENT_FILE']);
    }

    foreach ($META_TEMPLATE as $key => $value) {
        if ($key != 'ELEMENT_FILE' && class_exists('\Bitrix\Main\Text\Emoji')) {
            $META_TEMPLATE[$key] = Emoji::encode($value);
        }
    }

    $CONDITIONS = '';
    $obCond3 = new SMCondTree();
    $boolCond = $obCond3->Init(BT_COND_MODE_PARSE,
        BT_COND_BUILD_CATALOG,
        []
    );

    $CONDITIONS = $obCond3->Parse($rule);
    if (!isset($SITES)) {
        $SITES = [];
    }

    $arFields = [
        "ACTIVE" => $ACTIVE == "Y" ?: "N",
        "SEARCH" => $SEARCH == "Y" ?: "N",
        "STRONG" => $STRONG == "Y" ?: "N",
        "NAME" => $NAME,
        "CATEGORY_ID" => $CATEGORY_ID,
        "SORT" => $SORT,
        "SITES" => !empty($SITES) ? serialize($SITES) : '',
        "TYPE_OF_INFOBLOCK" => $TYPE_OF_INFOBLOCK,
        "INFOBLOCK" => $INFOBLOCK,
        "DATE_CHANGE" => new Type\DateTime(date('Y-m-d H:i:s'),
            'Y-m-d H:i:s'),
        "SECTIONS" => serialize($SECTIONS),
        "RULE" => serialize($CONDITIONS),
        "META" => serialize($META_TEMPLATE),
        "NO_INDEX" => $NO_INDEX == "Y" ?: "N",
        "TAG" => $TAG,
        "HIDE_IN_SECTION" => $HIDE_IN_SECTION == "Y" ?: "N",
        "CONDITION_TAG" => serialize($CONDITION_TAG),
        "STRICT_RELINKING" => $STRICT_RELINKING == "Y" ?: "N",
        "PRIORITY" => $PRIORITY,
        "CHANGEFREQ" => $CHANGEFREQ,
        "FILTER_TYPE" => $FILTER_TYPE,
        "GENERATE_AJAX" => $GENERATE_AJAX == "Y" ?: "N"
    ];

    if (!empty($ID)) {
        $result = ConditionTable::update($ID,
            $arFields);
        if (!$result->isSuccess()) {
            $errors[] = $result->getErrorMessages();
        }
    } else {
        $result = ConditionTable::add($arFields);
        if ($result->isSuccess()) {
            $ID = $result->getId();
        } else {
            $errors[] = $result->getErrorMessages();
        }
    }

    //OG and TW save/update settings
    if(!empty($ID) && !$errors) {
        $ogSettings = array_filter(
            $_REQUEST,
            function($key) {
                return strpos($key, 'OG_FIELD_') !== false && $key != 'OG_FIELD_ACTIVE';
            },
            ARRAY_FILTER_USE_KEY
        );

        if(is_array($ogSettings['OG_FIELD_IMAGE']) || $ogSettings['OG_FIELD_IMAGE_del'] == 'Y') {
            $ogSettings['OG_FIELD_IMAGE'] = OpengraphTable::saveFile(['OG_FIELD_IMAGE' => $ogSettings['OG_FIELD_IMAGE']]);
        }

        $arOGFields = [
            'CONDITION_ID' => $ID,
            'ACTIVE' => $_REQUEST['OG_FIELD_ACTIVE'] == 'Y' ? 'Y' : 'N',
            'SETTINGS' => serialize($ogSettings)
        ];

        $twSettings = array_filter(
            $_REQUEST,
            function($key) {
                return strpos($key, 'TW_FIELD_') !== false && $key != 'TW_FIELD_ACTIVE';
            },
            ARRAY_FILTER_USE_KEY
        );

        if(is_array($twSettings['TW_FIELD_IMAGE']) || $twSettings['TW_FIELD_IMAGE_del'] == 'Y') {
            $twSettings['TW_FIELD_IMAGE'] = OpengraphTable::saveFile(['TW_FIELD_IMAGE' => $twSettings['TW_FIELD_IMAGE']]);
        }

        $arTWFields = [
            'CONDITION_ID' => $ID,
            'ACTIVE' => $_REQUEST['TW_FIELD_ACTIVE'] == 'Y' ? 'Y' : 'N',
            'SETTINGS' => serialize($twSettings)
        ];

        if($openGraph = OpengraphTable::getByConditionID($ID)) {
            $result = OpengraphTable::update($openGraph['ID'], $arOGFields);
        } else {
            $result = OpengraphTable::add($arOGFields);
        }

        if(!$result->isSuccess()) {
            $errors[] = $result->getErrorMessages();
        }

        if($TwitterCard = TwitterCardTable::getByConditionID($ID)) {
            $result = TwitterCardTable::update($TwitterCard['ID'], $arTWFields);
        } else {
            $result = TwitterCardTable::add($arTWFields);
        }

        if(!$result->isSuccess()) {
            $errors[] = $result->getErrorMessages();
        }
    }

    if(empty($errors)){
        if ($apply != "") {
            LocalRedirect("/bitrix/admin/sotbit.seometa_edit.php?ID=" . $ID . "&mess=ok&lang=" . LANG . "&" . $tabControl->ActiveTabParam());
        } else {
            if (!empty($CATEGORY_ID)) {
                LocalRedirect("/bitrix/admin/sotbit.seometa_list.php?lang=" . LANG . '&parent=' . $CATEGORY_ID);
            } else {
                if (
                    !empty($_GET['INFOBLOCK'])
                    && !empty($_GET['SECTIONS'])
                    && !empty($_GET['TYPE_OF_INFOBLOCK'])
                ) {
                    LocalRedirect("/bitrix/admin/iblock_section_edit.php?IBLOCK_ID=" . $condition['INFOBLOCK'] . "&type=" . $condition['TYPE_OF_INFOBLOCK'] . "&ID=" . $condition['SECTIONS'][0] . "&find_section_section=0&lang=" . LANG);
                } else {
                    LocalRedirect("/bitrix/admin/sotbit.seometa_list.php?lang=" . LANG);
                }
            }
        }
    }else{
        if ($errors) {
            CAdminMessage::ShowMessage([
                "MESSAGE" => $errors[0][0]
            ]);
        }
    }
}
//</editor-fold>

if (!empty($ID)) {
    $ogFields = OpengraphTable::getByConditionID($ID);
    $twFields = TwitterCardTable::getByConditionID($ID);

    $ogDefaultParams = OpengraphTable::getDefaultParams();
    $twDefaultParams = TwitterCardTable::getDefaultParams();

    $arFields = array_merge($ogFields, $twFields);
    $arDefaultFields = array_merge($ogDefaultParams, $twDefaultParams);
}

$tabControl->SetFieldsValues(
    $bVarsFromForm,
    $arFields,
    $arDefaultFields
);



$arrSubmenu = [
    [
        "TEXT" => GetMessage("SEO_META_EDIT_LIST"),
        "TITLE" => GetMessage("SEO_META_EDIT_LIST_TITLE"),
        "LINK" => "sotbit.seometa_list.php?lang=" . LANG,
        "ICON" => "btn_list"
    ]
];

$condition['SECTIONS'] = is_array($condition['SECTIONS']) ? $condition['SECTIONS'] : unserialize($condition['SECTIONS']);

if (!empty($_REQUEST['section'])) {
    $arrSubmenu[] = [
        "TEXT" => GetMessage("SEO_META_EDIT_LIST_CAT"),
        "TITLE" => GetMessage("SEO_META_EDIT_LIST_CAT_TILTE"),
        "LINK" => "sotbit.seometa_list.php?parent=" . $_REQUEST['section'] . "&lang=" . LANG,
    ];
}

if (!empty($condition['INFOBLOCK']) && !empty($condition['SECTIONS']) && !empty($condition['TYPE_OF_INFOBLOCK'])) {
    $arrSubmenu[] = [
        "TEXT" => GetMessage("SEO_META_EDIT_SECTION_BACK"),
        "TITLE" => GetMessage("SEO_META_EDIT_SECTION_BACK_TITLE"),
        "LINK" => "iblock_section_edit.php?IBLOCK_ID=" . $condition['INFOBLOCK'] . "&type=" . $condition['TYPE_OF_INFOBLOCK'] . "&ID=" . $condition['SECTIONS'][0] . "&find_section_section=0&lang=" . LANG,
    ];
}

$aMenu[] = [
    "TEXT" => GetMessage("SEO_META_EDIT_BACK"),
    "TITLE" => GetMessage("SEO_META_EDIT_BACK_TITLE"),
    "ICON" => "btn_list",
    'MENU' => $arrSubmenu,
];

if (!empty($ID)) {
    $aMenu[] = [
        "SEPARATOR" => "Y"
    ];

    $aMenu[] = [
        "TEXT" => GetMessage("SEO_META_EDIT_ADD"),
        "TITLE" => GetMessage("SEO_META_EDIT_ADD_TITLE"),
        "LINK" => "sotbit.seometa_edit.php?lang=" . LANG,
        "ICON" => "btn_new"
    ];

    $aMenu[] = [
        "TEXT" => GetMessage("SEO_META_EDIT_COPY"),
        "TITLE" => GetMessage("SEO_META_EDIT_COPY_TITLE"),
        "LINK" => "sotbit.seometa_edit.php?action=copy&ID=" . $ID . "&lang=" . LANG . "&" . bitrix_sessid_get() . "';",
        "ICON" => "btn_new"
    ];

    $aMenu[] = [
        "TEXT" => GetMessage("SEO_META_EDIT_DEL"),
        "TITLE" => GetMessage("SEO_META_EDIT_DEL_TITLE"),
        "LINK" => "javascript:if(confirm('" . GetMessage("SEO_META_EDIT_DEL_CONF") . "'))window.location='sotbit.seometa_edit.php?ID=" . $ID . "&action=delete&lang=" . LANG . "&" . bitrix_sessid_get() . "';",
        "ICON" => "btn_delete"
    ];
}

$context = new CAdminContextMenu($aMenu);
$context->Show();

if ($_REQUEST["mess"] == "ok" && !empty($ID)) {
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
    'filter' => ['ACTIVE' => 'Y'],
    'order' => ['SORT' => 'ASC']
]);

while ($AllSection = $RsAllSections->Fetch()) {
    $AllSections['REFERENCE_ID'][] = $AllSection['ID'];
    $AllSections['REFERENCE'][] = $AllSection['NAME'];
}

// Meta
$Meta = [];
if (isset($condition["META"])) {
    $Meta = unserialize($condition["META"]);
}

$arIBlockTypeSel = [];
$SitesAll = [];
$rsSites = CSite::GetList($by = "sort",
    $order = "desc",
    []
);

while ($arSite = $rsSites->Fetch()) {
    $SitesAll[$arSite['LID']] = $arSite;
}

$arIBlockType = CIBlockParameters::GetIBlockTypes();
if(is_array($arIBlockType)) {
    foreach ($arIBlockType as $code => $val) {
        $arIBlockTypeSel["REFERENCE_ID"][] = $code;
        $arIBlockTypeSel["REFERENCE"][] = $val;
    }
}

$sm = new CSeoMeta();

if ($condition["TYPE_OF_INFOBLOCK"]) {
    $arIBlockSel = $sm->getIBlocks($condition["TYPE_OF_INFOBLOCK"]);
    $infoBlockFirst = $arIBlockSel["REFERENCE_ID"][0];
    if(isset($_POST['refresh_iblock_type'])) {
        $condition["INFOBLOCK"] = $infoBlockFirst;
    }
}

if ($condition["INFOBLOCK"]) {
    $sectionLinc = $sm->getSections($condition["INFOBLOCK"]);
}

// Menu for meta
$PropMenu = CCSeoMeta::PropMenu($condition["INFOBLOCK"]);
$PropMenuTemplate = CCSeoMeta::PropMenuTemplate($condition["INFOBLOCK"]);

$FilterType = [
    'default' => GetMessage('SEO_META_FILTERS_default'),
    'bitrix_chpu' => GetMessage('SEO_META_FILTERS_bitrix_chpu'),
    'bitrix_not_chpu' => GetMessage('SEO_META_FILTERS_bitrix_not_chpu'),
    'misshop_chpu' => GetMessage('SEO_META_FILTERS_misshop_chpu'),
    'combox_chpu' => GetMessage('SEO_META_FILTERS_combox_chpu'),
//	'combox_not_chpu' => GetMessage('SEO_META_FILTERS_combox_not_chpu')
];

$arrIndex = range(0, 1, 0.1);
$Priority = array_combine($arrIndex, $arrIndex);

$ChangeFreq = [
    'always' => GetMessage("SEO_META_EDIT_CHANGEFREQ_ALWAYS"),
    'hourly' => GetMessage("SEO_META_EDIT_CHANGEFREQ_HOURLY"),
    'daily' => GetMessage("SEO_META_EDIT_CHANGEFREQ_DAILY"),
    'weekly' => GetMessage("SEO_META_EDIT_CHANGEFREQ_WEEKLY"),
    'monthly' => GetMessage("SEO_META_EDIT_CHANGEFREQ_MONTHLY"),
    'yearly' => GetMessage("SEO_META_EDIT_CHANGEFREQ_YEARLY"),
    'never' => GetMessage("SEO_META_EDIT_CHANGEFREQ_NEVER")
];

$tabControl->Begin(["FORM_ACTION" => $APPLICATION->GetCurPageParam()]);

//<editor-fold desc="Condition">
$tabControl->BeginNextFormTab();

if($ID !== 0){
    $tabControl->AddViewField('ID',
        GetMessage("SEO_META_EDIT_ID"),
        $ID
    );
}

$tabControl->AddCheckBoxField("ACTIVE",
    GetMessage("SEO_META_EDIT_ACT"),
    false,
    "Y",
    ($condition['ACTIVE'] == "Y" || !isset($condition['ACTIVE']))
);

$tabControl->BeginCustomField("SEARCH",
    GetMessage('SEO_META_EDIT_SEARCH')
);
?>
<tr id="SEARCH">
    <td width="40%"
        valign="top"><?= $tabControl->GetCustomLabelHTML(); ?></td>
    <td width="60%">
        <input type="checkbox"
               name="SEARCH"
               value="Y" <?= (!isset($condition['SEARCH']) || $condition['SEARCH'] == "Y") ? 'checked="checked"' : ''; ?>
               id="designed_checkbox_SEARCH"
               class="adm-designed-checkbox">
        <label class="adm-designed-checkbox-label"
               for="designed_checkbox_SEARCH"
               title=""></label>
        <div class="adm-info-message-wrap">
            <div class="adm-info-message">
                <?= GetMessage('SEO_META_EDIT_SEARCH_NOTE'); ?>
            </div>
        </div>
    </td>
</tr>
<?
$tabControl->EndCustomField("SEARCH");

$tabControl->AddCheckBoxField("NO_INDEX",
    GetMessage("SEO_META_EDIT_INDEX"),
    false,
    "Y",
    ($condition['NO_INDEX'] == "Y")
);

$tabControl->AddCheckBoxField("STRONG",
    GetMessage("SEO_META_EDIT_STRONG"),
    false,
    "Y",
    (!isset($condition['STRONG']) || $condition['STRONG'] == "Y")
);

$tabControl->BeginCustomField("STRONG_NOTE", "" );
?>
<tr>
    <td></td>
    <td>
        <?= BeginNote(); ?>
        <?= GetMessage("SEO_META_EDIT_STRONG_NOTE") ?>
        <?= EndNote(); ?>
    </td>
</tr>
<?
$tabControl->EndCustomField("STRONG_NOTE");

$tabControl->AddEditField("NAME",
    GetMessage("SEO_META_EDIT_NAME"),
    true,
    [
        "size" => 44,
        "maxlength" => 255
    ],
    htmlspecialcharsbx($condition['NAME'])
);

$tabControl->AddEditField("SORT",
    GetMessage("SEO_META_EDIT_SORT"),
    true,
    [
        "size" => 6,
        "maxlength" => 255
    ],
    htmlspecialcharsbx(!empty($condition['SORT']) ? $condition['SORT'] : 100)
);

$tabControl->BeginCustomField("SORT_NOTE", "" );
?>
<tr>
    <td></td>
    <td>
        <?= BeginNote(); ?>
        <?= GetMessage("SEO_META_EDIT_SORT_NOTE"); ?>
        <?= EndNote(); ?>
    </td>
</tr>
<?
$tabControl->EndCustomField("SORT_NOTE");

$tabControl->AddViewField('DATE_CHANGE_TEXT',
    GetMessage("SEO_META_EDIT_DATE_CHANGE"),
    $condition['DATE_CHANGE']
);

$tabControl->BeginCustomField("CATEGORY_ID",
    GetMessage('SEO_META_EDIT_CATEGORY_ID')
);
?>
<tr id="CATEGORY_ID">
    <td width="40%"><?= $tabControl->GetCustomLabelHTML(); ?></td>
    <td width="60%">
        <?= SelectBoxFromArray('CATEGORY_ID',
            $AllSections,
            $condition['CATEGORY_ID'] ?? $_REQUEST['section'],
            '',
            false,
            '',
            'style="min-width:350px"'); ?>
    </td>
</tr>
<?
$tabControl->EndCustomField("CATEGORY_ID");

$tabControl->BeginCustomField("SITES",
    GetMessage('SEO_META_EDIT_SITES'),
    true
);
if(!empty($condition['SITES']) && !is_array($condition['SITES'])){
    $sitesCheck = unserialize($condition['SITES']);
}elseif(!empty($condition['SITES']) && is_array($condition['SITES'])){
    $sitesCheck = $condition['SITES'];
}else{
    $sitesCheck = key($SitesAll);
}
?>
<tr id="tr_SITES">
    <td width="40%"><?= $tabControl->GetCustomLabelHTML(); ?></td>
    <td>
        <?= CLang::SelectBoxMulti("SITES", $sitesCheck); ?>
    </td>
</tr>
<?
$tabControl->EndCustomField("SITES");

$tabControl->BeginCustomField("TYPE_OF_INFOBLOCK",
    GetMessage('SEO_META_EDIT_TYPE_OF_INFOBLOCK')
);
?>
<tr id="tr_TYPE_OF_INFOBLOCK">
    <td width="40%"><?= $tabControl->GetCustomLabelHTML(); ?></td>
    <td width="60%">
        <?
        echo SelectBoxFromArray('TYPE_OF_INFOBLOCK',
            $arIBlockTypeSel,
            $condition['TYPE_OF_INFOBLOCK'],
            '',
            'style="min-width: 350px; margin-right: 7px;"',
            false,
            '');
        echo '<input type="submit" name="refresh_iblock_type" value="OK" />';
        ?>
    </td>
</tr>
<?
$tabControl->EndCustomField("TYPE_OF_INFOBLOCK");

$tabControl->BeginCustomField("INFOBLOCK",
    GetMessage('SEO_META_EDIT_INFOBLOCK')
);
?>
<tr id="tr_INFOBLOCK">
    <td width="40%"><?= $tabControl->GetCustomLabelHTML(); ?></td>
    <td width="60%">
        <?
        echo SelectBoxFromArray('INFOBLOCK',
            $arIBlockSel,
            $condition['INFOBLOCK'],
            '',
            'style="min-width: 350px; margin-right: 7px;"',
            false,
            '');
        echo '<input type="submit" name="refresh_infoblock_id" value="OK" />';
        ?>
    </td>
</tr>
<?
$tabControl->EndCustomField("INFOBLOCK");

$tabControl->BeginCustomField("SECTIONS",
    GetMessage('SEO_META_EDIT_SECTIONS')
);
?>
<tr id="SECTIONS">
    <td width="40%"><?= $tabControl->GetCustomLabelHTML(); ?></td>
    <td width="100%" class="adm-input-wrap adm-detail-content-cell-r">
        <label for="SETIONS_SEARCH" style="margin-bottom: 20px;"><?=Loc::getMessage('SEO_META_EDIT_SECTIONS_FILTER_LABEL')?></label>
        <input name="SECTIONS_SEARCH" oninput="sectionFilter(this)" type="text" value="" placeholder="<?=Loc::getMessage('SEO_META_EDIT_SECTIONS_FILTER_PLACEHOLDER')?>" class="adm-input" style="margin-bottom: 10px;">
        <?= SelectBoxMFromArray('SECTIONS[]',
            $sectionLinc ?: array(),
            is_array($condition['SECTIONS']) ? $condition['SECTIONS'] : unserialize($condition['SECTIONS']),
            '',
            false,
            '10',
            'style="min-width: 350px;width: 100%;"'); ?>
    </td>
    <script>
        function sectionFilter(input) {
            let searchValue = input.value;
            let options = input.parentElement.querySelector('select[name^=SECTIONS]').options;

            if(options !== undefined) {
                for (let i = 0; i <= options.length; i++) {
                    if (options[i] !== undefined) {
                        if (options[i].innerText.toLowerCase().indexOf(searchValue.toLowerCase()) === -1) {
                            options[i].style.display = 'none';
                        } else {
                            options[i].style.display = 'block';
                        }
                    }
                }
            }
        }
    </script>
</tr>
<? $tabControl->EndCustomField("SECTIONS");

$tabControl->BeginCustomField("CONDITIONS",
    GetMessage('SEO_META_EDIT_SECTIONS_COND') . ":"
);
?>
<tr id="tr_CONDITIONS">
    <td width="40%"><?= $tabControl->GetCustomLabelHTML(); ?></td>
    <td width="60%">
        <div id="tree"
             style="position: relative; z-index: 1;"></div>
        <?
        if (!is_array($condition['RULE'])) {
            if (CheckSerializedData($condition['RULE'])) {
                $condition['RULE'] = unserialize($condition['RULE']);
            } else {
                $condition['RULE'] = '';
            }
        }

        $arTreeDescr = [
            'js' => '/bitrix/js/sotbit.seometa/core_tree.js',
            'css' => '/bitrix/css/sotbit.seometa/catalog_cond.css',
            'lang' => '/bitrix/modules/sotbit.seometa/lang/' . LANGUAGE_ID . '/js_core_tree.php',
            'rel' => [
                'core',
                'date',
                'window'
            ]
        ];

        CJSCore::RegisterExt('sotbit_condtree', $arTreeDescr);

        $obCond = new SMCondTree();
        $boolCond = $obCond->Init(BT_COND_MODE_DEFAULT,
            BT_COND_BUILD_CATALOG,
            [
                'FORM_NAME' => 'tabControl_form',
                'CONT_ID' => 'tree',
                'JS_NAME' => 'JSCond'
            ]
        );

        if (!$boolCond && $ex = $APPLICATION->GetException()) {
            echo $ex->GetString() . "<br>";
        } else {
            if (Loader::includeModule('catalog')) {
                $product_block = CCatalog::GetList([],
                    ['IBLOCK_ID' => $condition['INFOBLOCK']],
                    false,
                    false,
                    ['OFFERS_IBLOCK_ID']
                )->fetch();
            } else {
                $product_block = CIBlockElement::GetList([],
                    ['IBLOCK_ID' => $condition['INFOBLOCK']],
                    false,
                    false,
                    ['OFFERS_IBLOCK_ID']
                )->fetch();
            }

            $obCond->Show($condition['RULE'],
                [
                    $condition['INFOBLOCK'],
                    $product_block['OFFERS_IBLOCK_ID']
                ]
            );
        }
        ?>
    </td>
</tr>

<script>
    BX.ready(function () {
        $('body').on('click', 'a[id*="add_link"]', function (e) {
            e.preventDefault();
            $(this).next('select').show();
            $(this).hide();
        });
    });
</script>
<?
Asset::getInstance()->addString('<style>span.condition-alert{display: none !important;}</style>',
    true
);

$tabControl->EndCustomField("CONDITIONS");

$tabControl->AddDropDownField("FILTER_TYPE",
    GetMessage('SEO_META_EDIT_FILTER_TYPE'),
    false,
    $FilterType,
    $condition['FILTER_TYPE']
);
$tabControl->AddDropDownField("PRIORITY",
    GetMessage('SEO_META_EDIT_PRIORITY'),
    true,
    $Priority,
    $condition['PRIORITY']
);

$tabControl->BeginCustomField("PRIORITY_NOTE",
    ""
);
?>
<tr>
    <td></td>
    <td>
        <?= BeginNote(); ?>
        <?= GetMessage("SEO_META_EDIT_PRIORITY_NOTE") ?>
        <?= EndNote(); ?>
    </td>
</tr>
<? $tabControl->EndCustomField("PRIORITY_NOTE");

$tabControl->AddDropDownField("CHANGEFREQ",
    GetMessage('SEO_META_EDIT_CHANGEFREQ'),
    true,
    $ChangeFreq,
    $condition['CHANGEFREQ']
);

$tabControl->BeginCustomField("CHANGEFREQ_NOTE",
    ""
);
?>
<tr>
    <td></td>
    <td>
        <?= BeginNote(); ?>
        <?= GetMessage("SEO_META_EDIT_CHANGEFREQ_NOTE") ?>
        <?= EndNote(); ?>
    </td>
</tr>
<?
$tabControl->EndCustomField("CHANGEFREQ_NOTE");

$tabControl->BeginCustomField("HID",
    ''
);
?>
<?= bitrix_sessid_post(); ?>
<input type="hidden"
       name="lang"
       value="<?= LANG ?>">
<? if (!empty($ID) && !$bCopy): ?>
    <input type="hidden"
           name="ID"
           value="<?= $ID ?>">
<? endif; ?>
<?
$tabControl->EndCustomField("HID");
//</editor-fold>


//<editor-fold desc="Metainfo">
$tabControl->BeginNextFormTab();

$tabControl->BeginCustomField("GROUP_ELEMENT",
    GetMessage('SEO_META_EDIT_GROUP_ELEMENT')
);
?>
<tr class="heading">
    <td colspan="3"><?= $tabControl->GetCustomLabelHTML(); ?></td>
</tr>
<? $tabControl->EndCustomField("GROUP_ELEMENT");?>
<?
if($Meta['ELEMENT_TITLE'] && class_exists('\Bitrix\Main\Text\Emoji')) {
    $Meta['ELEMENT_TITLE'] = Emoji::decode($Meta['ELEMENT_TITLE']);
}
?>
<?$tabControl->BeginCustomField("ELEMENT_TITLE",
    GetMessage('SEO_META_EDIT_ELEMENT_TITLE')
);
?>
<tr class="adm-detail-valign-top">
    <td width="40%"><?= $tabControl->GetCustomLabelHTML(); ?></td>
    <td width="50%"><textarea
                style="width: 90%"
                class="count_symbol"
                name="META_TEMPLATE[ELEMENT_TITLE]"><?= !empty($Meta['ELEMENT_TITLE']) ? $Meta['ELEMENT_TITLE'] : '' ?></textarea>
        <div class="count_symbol_print">
            <?= GetMessage('SEO_META_SYMBOL_COUNT_FROM') . MIN_SEO_TITLE . ' - ' . MAX_SEO_TITLE; ?>
            <span class="meta_title"></span>
            <div class="progressbar"
                 data-min="<?= MIN_SEO_TITLE; ?>"
                 data-max="<?= MAX_SEO_TITLE; ?>"></div>
        </div>
    </td>
    <td width="10%"
        align="left">
        <?= $PropMenu ?>
    </td>
</tr>
<? $tabControl->EndCustomField("ELEMENT_TITLE");

if(!empty($Meta['ELEMENT_KEYWORDS']) && class_exists('\Bitrix\Main\Text\Emoji')) {
    $Meta['ELEMENT_KEYWORDS'] = Emoji::decode($Meta['ELEMENT_KEYWORDS']);
}
?>
<?$tabControl->BeginCustomField("ELEMENT_KEYWORDS", GetMessage('SEO_META_EDIT_ELEMENT_KEYWORDS'));?>
<tr class="adm-detail-valign-top">
    <td width="40%"><?= $tabControl->GetCustomLabelHTML(); ?></td>
    <td width="50%"><textarea
                style="width: 90%"
                class="count_symbol"
                name="META_TEMPLATE[ELEMENT_KEYWORDS]"><?= !empty($Meta['ELEMENT_KEYWORDS']) ? $Meta['ELEMENT_KEYWORDS'] : '' ?></textarea>
        <div class="count_symbol_print">
            <?= GetMessage('SEO_META_SYMBOL_COUNT_FROM') . MIN_SEO_KEY . ' - ' . MAX_SEO_KEY; ?>
            <span class="meta_key"></span>
            <div class="progressbar"
                 data-min="<?= MIN_SEO_KEY; ?>"
                 data-max="<?= MAX_SEO_KEY; ?>"></div>
        </div>
    </td>
    <td width="10%"
        align="left">
        <?= $PropMenu ?>
    </td>
</tr>
<? $tabControl->EndCustomField("ELEMENT_KEYWORDS");

if(!empty($Meta['ELEMENT_DESCRIPTION']) && class_exists('\Bitrix\Main\Text\Emoji')) {
    $Meta['ELEMENT_DESCRIPTION'] = Emoji::decode($Meta['ELEMENT_DESCRIPTION']);
}
?>
<?$tabControl->BeginCustomField("ELEMENT_DESCRIPTION", GetMessage('SEO_META_EDIT_ELEMENT_DESCRIPTION'));?>
<tr class="adm-detail-valign-top">
    <td width="40%"><?= $tabControl->GetCustomLabelHTML(); ?></td>
    <td width="50%"><textarea
                style="width: 90%"
                class="count_symbol"
                name="META_TEMPLATE[ELEMENT_DESCRIPTION]"><?= !empty($Meta['ELEMENT_DESCRIPTION']) ? $Meta['ELEMENT_DESCRIPTION'] : '' ?></textarea>
        <div class="count_symbol_print">
            <?= GetMessage('SEO_META_SYMBOL_COUNT_FROM') . MIN_SEO_DESCR . ' - ' . MAX_SEO_DESCR; ?>
            <span class="meta_descr"></span>
            <div class="progressbar"
                 data-min="<?= MIN_SEO_DESCR; ?>"
                 data-max="<?= MAX_SEO_DESCR; ?>"></div>
        </div>
    </td>
    <td width="10%"
        align="left">
        <?= $PropMenu; ?>
    </td>
</tr>
<? $tabControl->EndCustomField("ELEMENT_DESCRIPTION");

if(!empty($Meta['ELEMENT_PAGE_TITLE']) && class_exists('\Bitrix\Main\Text\Emoji')) {
    $Meta['ELEMENT_PAGE_TITLE'] = Emoji::decode($Meta['ELEMENT_PAGE_TITLE']);
}
?>
<?$tabControl->BeginCustomField("ELEMENT_PAGE_TITLE", GetMessage('SEO_META_EDIT_ELEMENT_PAGE_TITLE'));?>
<tr class="adm-detail-valign-top">
    <td width="40%"><?= $tabControl->GetCustomLabelHTML(); ?></td>
    <td width="50%"><textarea
                style="width: 90%"
                name="META_TEMPLATE[ELEMENT_PAGE_TITLE]"><?= !empty($Meta['ELEMENT_PAGE_TITLE']) ? $Meta['ELEMENT_PAGE_TITLE'] : '' ?></textarea>
    </td>
    <td width="10%"
        align="left">
        <?= $PropMenu; ?>
    </td>
</tr>
<? $tabControl->EndCustomField("ELEMENT_PAGE_TITLE");


if(!empty($Meta['ELEMENT_BREADCRUMB_TITLE']) && class_exists('\Bitrix\Main\Text\Emoji')) {
    $Meta['ELEMENT_BREADCRUMB_TITLE'] = Emoji::decode($Meta['ELEMENT_BREADCRUMB_TITLE']);
}
?>
<?$tabControl->BeginCustomField("ELEMENT_BREADCRUMB_TITLE", GetMessage('SEO_META_EDIT_ELEMENT_BREADCRUMB_TITLE'));?>
<tr class="adm-detail-valign-top">
    <td width="40%"><?= $tabControl->GetCustomLabelHTML(); ?></td>
    <td width="50%"><textarea
                style="width: 90%"
                name="META_TEMPLATE[ELEMENT_BREADCRUMB_TITLE]"><?= !empty($Meta['ELEMENT_BREADCRUMB_TITLE']) ? $Meta['ELEMENT_BREADCRUMB_TITLE'] : '' ?></textarea>
    </td>
    <td width="10%"
        align="left">
        <?= $PropMenu; ?>
    </td>
</tr>
<? $tabControl->EndCustomField("ELEMENT_BREADCRUMB_TITLE");

if(!empty($Meta['ELEMENT_TOP_DESC']) && class_exists('\Bitrix\Main\Text\Emoji')) {
    $Meta['ELEMENT_TOP_DESC'] = Emoji::decode($Meta['ELEMENT_TOP_DESC']);
}
?>
<?$tabControl->BeginCustomField("ELEMENT_TOP_DESC", GetMessage('SEO_META_EDIT_ELEMENT_TOP_DESC'));?>
<tr class="adm-detail-valign-top">
    <td width="40%"><?= $tabControl->GetCustomLabelHTML(); ?></td>
    <td width="50%">
        <?
        CFileMan::AddHTMLEditorFrame(
            "ELEMENT_TOP_DESC",
            !empty($Meta['ELEMENT_TOP_DESC']) ? $Meta['ELEMENT_TOP_DESC'] : '',
            "ELEMENT_TOP_DESC_TYPE",
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
        )
        ?>
    </td>
    <td width="10%"
        align="left">
        <?= $PropMenu; ?>
    </td>
</tr>
<? $tabControl->EndCustomField("ELEMENT_TOP_DESC");

if(!empty($Meta['ELEMENT_BOTTOM_DESC']) && class_exists('\Bitrix\Main\Text\Emoji')) {
    $Meta['ELEMENT_BOTTOM_DESC'] = Emoji::decode($Meta['ELEMENT_BOTTOM_DESC']);
}
?>
<?$tabControl->BeginCustomField("ELEMENT_BOTTOM_DESC", GetMessage('SEO_META_EDIT_ELEMENT_BOTTOM_DESC'));?>
<tr class="adm-detail-valign-top">
    <td width="40%"><?= $tabControl->GetCustomLabelHTML(); ?></td>
    <td width="50%">
        <?
        CFileMan::AddHTMLEditorFrame(
            "ELEMENT_BOTTOM_DESC",
            !empty($Meta['ELEMENT_BOTTOM_DESC']) ? $Meta['ELEMENT_BOTTOM_DESC'] : '',
            "ELEMENT_BOTTOM_DESC_TYPE",
            "html",
            [
                'height' => 150,
                'width' => '100%'
            ],
            "N",
            0,
            "",
            "",
            "ru"
        );
        ?>
    </td>
    <td width="10%"
        align="left">
        <?= $PropMenu; ?>
    </td>
</tr>
<? $tabControl->EndCustomField("ELEMENT_BOTTOM_DESC");

if(!empty($Meta['ELEMENT_ADD_DESC']) && class_exists('\Bitrix\Main\Text\Emoji')) {
    $Meta['ELEMENT_ADD_DESC'] = Emoji::decode($Meta['ELEMENT_ADD_DESC']);
}
?>
<?$tabControl->BeginCustomField("ELEMENT_ADD_DESC", GetMessage('SEO_META_EDIT_ELEMENT_ADD_DESC'));?>
<tr class="adm-detail-valign-top">
    <td width="40%"><?= $tabControl->GetCustomLabelHTML(); ?></td>
    <td width="50%">
        <?
        CFileMan::AddHTMLEditorFrame(
            "ELEMENT_ADD_DESC",
            !empty($Meta['ELEMENT_ADD_DESC']) ? $Meta['ELEMENT_ADD_DESC'] : '',
            "ELEMENT_ADD_DESC_TYPE",
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
<? $tabControl->EndCustomField("ELEMENT_ADD_DESC");

$tabControl->BeginCustomField("ELEMENT_FILE",
    GetMessage('SEO_META_EDIT_FILE')
);
?>
<tr class="adm-detail-file-row">
    <td><?= $tabControl->GetCustomLabelHTML(); ?></td>
    <td><?= FileInput::createInstance([
            "name" => "ELEMENT_FILE",
            "description" => true,
            "upload" => true,
            "allowUpload" => "I",
            "medialib" => true,
            "fileDialog" => true,
            "cloud" => true,
            "delete" => true,
            "maxCount" => 1
        ])->show($bVarsFromForm ? $_REQUEST["ELEMENT_FILE"] : $Meta['ELEMENT_FILE'],
            $bVarsFromForm) ?>
    </td>
</tr>
<? $tabControl->EndCustomField("ELEMENT_FILE");

$tabControl->BeginCustomField("TAG",
    GetMessage('SEO_META_EDIT_TAG')
);
?>
<tr class="adm-detail-valign-top">
    <td width="40%">
        <?= $tabControl->GetCustomLabelHTML(); ?>
    </td>
    <td width="50%">
        <textarea
                style="width: 90%"
                name="TAG"><?= $condition['TAG'] ?: '' ?></textarea>
    </td>
    <td width="10%"
        align="left">
        <?= $PropMenu; ?>
    </td>
</tr>
<? $tabControl->EndCustomField("TAG");

$tabControl->AddCheckBoxField("HIDE_IN_SECTION",
    GetMessage("SEO_META_EDIT_HIDE_IN_SECTION"),
    false,
    "Y",
    $condition['HIDE_IN_SECTION'] == "Y"
);

$tabControl->AddCheckBoxField("STRICT_RELINKING",
    GetMessage("SEO_META_EDIT_STRICT_RELINKING"),
    false,
    "Y",
    $condition['STRICT_RELINKING'] == "Y"
);

$tabControl->BeginCustomField("STRICT_RELINKING_NOTE",
    ""
);
?>
<tr>
    <td></td>
    <td>
        <?= BeginNote(); ?>
        <?= GetMessage("SEO_META_EDIT_STRICT_RELINKING_NOTE") ?>
        <?= EndNote(); ?>
    </td>
</tr>
<?
$tabControl->EndCustomField("STRICT_RELINKING_NOTE");

$tabControl->BeginCustomField("CONDITION_TAG",
    GetMessage('SEO_META_EDIT_CONDITION_TAG')
);

$conditions = [];
$rs = ConditionTable::getList([
    'select' => [
        'NAME',
        'ID'
    ]
]);

while ($cond = $rs->fetch()) {
    $conditions["REFERENCE_ID"][] = $cond['ID'];
    $conditions["REFERENCE"][] = '[' . $cond['ID'] . '] ' . $cond['NAME'];
}
?>
<tr class="adm-detail-valign-top"
    id="CONDITION_TAG">
    <td width="40%">
        <?= $tabControl->GetCustomLabelHTML(); ?>
    </td>
    <td width="60%">
        <?= SelectBoxMFromArray('CONDITION_TAG' . '[]',
            $conditions,
            is_array($condition['CONDITION_TAG']) ? $condition['CONDITION_TAG'] : unserialize($condition['CONDITION_TAG']),
            '',
            false,
            '',
            'style="min-width:350px"'); ?>
        <div class="adm-info-message-wrap">
            <div class="adm-info-message">
                <?= GetMessage('SEO_META_EDIT_CONDITION_TAG_NOTE'); ?>
            </div>
        </div>
    </td>
</tr>
<? $tabControl->EndCustomField("CONDITION_TAG");

$tabControl->BeginCustomField("META_NOTE", "" );

Extension::load("ui.hint");
?>
<tr>
    <td colspan="3"
        align="center">
        <div class="adm-info-message-wrap">
            <div class="adm-info-message"
                 style="text-align:left;">
                <?= GetMessage("SEO_META_EDIT_META_NOTE") ?>
            </div>
        </div>
    </td>
</tr>

<script type="text/javascript">
    BX.ready(function() {
        BX.UI.Hint.init(BX('morphy_container'));
        BX.UI.Hint.init(BX('prop_list_container'));
    })
</script>
<? $tabControl->EndCustomField("META_NOTE");
//</editor-fold>


//<editor-fold desc="CHPU">
$tabControl->BeginNextFormTab();
const B_ADMIN_SUBCHPU = 1;
const B_ADMIN_SUBCHPU_LIST = false;

$tabControl->BeginCustomField("TEMPLATE_NEW_URL",
    ''
);
?>
<tr>
    <td width="30%">
        <?= GetMessage('SEO_META_EDIT_TEMPLATE_NEW_URL'); ?>
    </td>
    <td width="50%">
        <input type="text"
               name="META_TEMPLATE[TEMPLATE_NEW_URL]"
               maxlength="255"
               size="110"
               value="<?= $Meta['TEMPLATE_NEW_URL'] ?: '' ?>">
    </td>
    <td width="10%"
        align="left">
        <?= $PropMenuTemplate; ?>
    </td>
</tr>
<tr>
    <td></td>
    <td>
        <?= BeginNote(); ?>
        <?= GetMessage("SEO_META_NOTE_TEMPLATE_URL") ?>
        <?= EndNote(); ?>
    </td>
</tr>
<?
$tabControl->EndCustomField("TEMPLATE_NEW_URL");

$tabControl->BeginCustomField("SPACE_REPLACEMENT", '' );
?>
<tr>
    <td width="30%">
        <?= GetMessage('SEO_META_EDIT_SPACE_REPLACEMENT'); ?>
    </td>
    <td width="60%">
        <input type="text"
               name="META_TEMPLATE[SPACE_REPLACEMENT]"
               maxlength="255"
               size="110"
               value="<?= $Meta['SPACE_REPLACEMENT'] ?: '-'; ?>">
    </td>
</tr>
<tr>
    <td></td>
    <td>
        <?= BeginNote(); ?>
        <?= GetMessage("SEO_META_EDIT_NOTE_SPACE_REPLACEMENT") ?>
        <?= EndNote(); ?>
    </td>
</tr>
<?
$tabControl->EndCustomField("SPACE_REPLACEMENT");

$tabControl->BeginCustomField("BUTTON_GENERATE", '' );
?>
<tr>
    <td></td>
    <td>
        <input class="adm-btn" id="generate_button" name="generate_chpu" type="submit" value="<?= GetMessage('SEO_META_GENERATE_CHPU') ?>">
    </td>
</tr>
<?
$tabControl->AddCheckBoxField("GENERATE_AJAX",
    GetMessage("SEO_META_GENERATE_AJAX"),
    false,
    "Y",
    $condition['GENERATE_AJAX'] == "Y"
);
?>
<tr>
    <td></td>
    <td>
        <?= BeginNote(); ?>
        <?= GetMessage("SEO_META_NOTE_GENERATE_CHPU") ?>
        <?= EndNote(); ?>
    </td>
</tr>

<script type="text/javascript">
    BX.ready(function () {
        $('#generate_button').click(function (e) {
            if ($("input[name*='GENERATE_AJAX']").attr("checked") !== "checked") {
                return;
            }
            var sefQuantity = 0,
                sectionArray = [];
            e.preventDefault();
            $.ajax({
                url: 'sotbit.seometa_edit.php?ID=' + <?= CUtil::PhpToJSObject($ID,
                    false,
                    true) ?> + '&action=get_section_list&lang=' + <?= CUtil::PhpToJSObject(LANG,
                    false,
                    true) ?>,
                method: 'POST',
                dataType: 'html',
                timeout: 5000,
                success: function (result) {
                    if (typeof result === 'string') {
                        sectionArray = JSON.parse(result.replace(new RegExp("<.{1,}>", 'g'), ''));
                        if (Array.isArray(sectionArray)) {
                            sefQuantity = sectionArray.length;
                            $('.ui-progressbar-text-after').text('0' + '<?=Loc::getMessage("SEO_META_SEF_FROM")?>' + sefQuantity);
                            $('.ui-progressbar').css('display', 'flex');
                            generateSef(sefQuantity, 0, sectionArray, []);
                        }
                    }
                },
                error: function (result) {
                    $('.ui-progressbar-bar').addClass('ui-progressbar-danger');
                }
            });
        });

        function generateSef(sefQuantity, start, sectionArray, arrErrors, timeOut, errorCount) {
            var step = 1,
                isProgress = false,
                isError = false,
                errorsGenerate = arrErrors;

            if (typeof (timeOut) == 'undefined') {
                timeOut = 5000;
            } else {
                isError = true;
                if (typeof (errorCount) == 'number') {
                    errorCount++;
                } else {
                    errorCount = 0;
                }
            }
            if (start >= sefQuantity) {
                $('.ui-progressbar-bar').css('width', 100 + '%');
                $('.ui-progressbar-bar').addClass('ui-progressbar-success');
                $('.ui-progressbar-text-after').text(sefQuantity + '<?=Loc::getMessage("SEO_META_SEF_FROM")?>' + sefQuantity);
                if(arrErrors.indexOf(false) >= 0){
                    location.assign('sotbit.seometa_edit.php?ID=' + <?= CUtil::PhpToJSObject($ID,
                        false,
                        true) ?> + '&tabControl_active_tab=' + getActiveTab() + '&lang=' + <?= CUtil::PhpToJSObject(LANG,
                        false,
                        true) ?>);
                }else{
                    location.assign('sotbit.seometa_edit.php?ID=' + <?= CUtil::PhpToJSObject($ID,
                        false,
                        true) ?> + '&tabControl_active_tab=' + getActiveTab() + '&lang=' + <?= CUtil::PhpToJSObject(LANG,
                        false,
                        true) ?> + '&generate_errors=y');
                }
                return;
            }

            $.ajax({
                url: 'sotbit.seometa_edit.php?ID=' + <?= CUtil::PhpToJSObject($ID,
                    false,
                    true) ?> + '&action=generate_chpu&lang=' + <?= CUtil::PhpToJSObject(LANG,
                    false,
                    true) ?>,
                method: 'POST',
                dataType: 'html',
                timeout: timeOut,
                data: {
                    currentSection: sectionArray[start],
                    isProgress: start,
                    isError: isError
                },
                success: function (result) {
                    if($(result).find('.adm-info-message-wrap.adm-info-message-red').html()){
                        errorsGenerate[start] = true;
                    }else{
                        errorsGenerate[start] = false;
                    }
                    start++;
                    $('.ui-progressbar-bar').removeClass('ui-progressbar-danger');
                    $('.ui-progressbar-bar').css('width', start * 100 / sefQuantity + '%');
                    $('.ui-progressbar-text-after').text(start + '<?=Loc::getMessage("SEO_META_SEF_FROM")?>' + sefQuantity);
                    generateSef(sefQuantity, start, sectionArray, errorsGenerate);
                },
                error: function (result) {
                    $('.ui-progressbar-bar').addClass('ui-progressbar-danger');
                    if (errorCount >= 1) {
                        $.ajax({
                            url: 'sotbit.seometa_edit.php?ID=' + <?= CUtil::PhpToJSObject($ID,
                                false,
                                true) ?> + '&action=delete_last_chpu&lang=' + <?= CUtil::PhpToJSObject(LANG,
                                false,
                                true) ?>,
                            method: 'POST',
                            dataType: 'html',
                            data: {currentSection: sectionArray[start]},
                            success: function (result) {
                            },
                            error: function (result) {
                                return;
                            }
                        });
                    } else {
                        generateSef(sefQuantity, start, sectionArray, [], timeOut * 1.5, errorCount);
                    }
                }
            });
        }

        function getActiveTab() {
            var activeTabId = '';
            activeTabId = $('.adm-detail-tab-active').attr('id');
            activeTabId = activeTabId.replace('tab_cont_', '');
            return activeTabId;
        }
    })
</script>
<?
$tabControl->EndCustomField("BUTTON_GENERATE");

Extension::load("ui.progressbar");
$tabControl->BeginCustomField("PROGRESSBAR", '' );
?>
<div class="ui-progressbar ui-progressbar-lg"
     style="display: none;">
    <div class="ui-progressbar-text-before"><?= Loc::getMessage("SEO_META_SEF_GENERATION") ?></div>
    <div class="ui-progressbar-track">
        <div class="ui-progressbar-bar"
             style="width: 0%"></div>
    </div>
    <div class="ui-progressbar-text-after"></div>
</div>
<?
$tabControl->EndCustomField("PROGRESSBAR");

$tabControl->BeginCustomField("CHPU_LIST", '' );
?>
<tr id="tr_LISTCHPU">
    <td colspan="2">
        <? require($_SERVER["DOCUMENT_ROOT"] . '/bitrix/modules/sotbit.seometa/admin/templates/sub_chpu.php'); ?>
    </td>
</tr>
<?
$tabControl->EndCustomField('CHPU_LIST');

$tabControl->BeginNextFormTab();
//</editor-fold>


//<editor-fold desc="OG and TW meta data">
//OpenGraph
$tabControl->AddSection(
    'OG_GROUP_ELEMENT',
    Loc::getMessage('SEO_META_EDIT_OG_GROUP_ELEMENT')
);

$tabControl->AddCheckBoxField(
    'OG_FIELD_ACTIVE',
    Loc::getMessage('SEO_META_EDIT_OG_FIELD_ACTIVE'),
    false,
    'Y',
    $arFields['OG_FIELD_ACTIVE'] == 'Y' ? 'checked' : ''
);

$tabControl->AddEditField(
    'OG_FIELD_TYPE',
    Loc::getMessage('SEO_META_EDIT_OG_OBJECT_TYPE'),
    true,
);

$tabControl->AddEditField(
    'OG_FIELD_LOCALE',
    Loc::getMessage('SEO_META_EDIT_OG_LOCALE'),
    false,
);

$tabControl->AddEditField(
    'OG_FIELD_SITE_NAME',
    Loc::getMessage('SEO_META_EDIT_OG_SITE_NAME'),
    false,
);

$tabControl->AddEditField(
    'OG_FIELD_URL',
    Loc::getMessage('SEO_META_EDIT_OG_URL'),
    true,
    [
        'readonly' => true
    ],
    Loc::getMessage('SEO_META_EDIT_OG_URL_NOTE')
);

$tabControl->AddFileField(
    'OG_FIELD_IMAGE',
    Loc::getMessage('SEO_META_EDIT_OG_IMAGE'),
    $arFields['OG_FIELD_IMAGE'] ?: '',
    [],
    true
);

$tabControl->AddEditField(
    'OG_FIELD_TITLE',
    Loc::getMessage('SEO_META_EDIT_OG_TITLE'),
    true,
    false,
    false,
    $PropMenu
);

$tabControl->AddEditField(
    'OG_FIELD_DESCRIPTION',
    Loc::getMessage('SEO_META_EDIT_OG_DESCRIPTION'),
    false,
    false,
    false,
    $PropMenu
);

//TwitterCard
$tabControl->AddSection(
    'TW_GROUP_ELEMENT',
    Loc::getMessage('SEO_META_EDIT_TW_GROUP_ELEMENT')
);

$tabControl->AddCheckBoxField(
    'TW_FIELD_ACTIVE',
    Loc::getMessage('SEO_META_EDIT_TW_FIELD_ACTIVE'),
    false,
    'Y',
    $arFields['TW_FIELD_ACTIVE'] == 'Y' ? 'checked' : ''
);

$tabControl->AddDropDownField(
    'TW_FIELD_CARD',
    Loc::getMessage('SEO_META_EDIT_TW_CARD'),
    true,
    [
        'summary' => 'summary',
        'summary_large_image' => 'summary_large_image'
    ]
);

$tabControl->AddEditField(
    'TW_FIELD_TITLE',
    Loc::getMessage('SEO_META_EDIT_TW_TITLE'),
    false,
    false,
    false,
    $PropMenu
);

$tabControl->AddEditField(
    'TW_FIELD_DESCRIPTION',
    Loc::getMessage('SEO_META_EDIT_TW_DESCRIPTION'),
    false,
    false,
    false,
    $PropMenu
);

$tabControl->AddFileField(
    'TW_FIELD_IMAGE',
    Loc::getMessage('SEO_META_EDIT_TW_IMAGE'),
    $arFields['TW_FIELD_IMAGE'] ?: '',
    [],
    true
);

$tabControl->BeginNextFormTab();
//</editor-fold>


//<editor-fold desc="Video guide">
$tabControl->BeginCustomField("ELEMENT_VIDEO", GetMessage('SEO_META_EDIT_VIDEO_TEXT'));
?>
<tr class="heading">
    <td colspan="2">
        <?= $tabControl->GetCustomLabelHTML(); ?>
    </td>
</tr>
<tr class="adm-detail-valign-top">
    <td colspan="2"
        align="center">
        <iframe width="800"
                height="450"
                src="https://www.youtube.com/embed/videoseries?list=PL2fR59TvIPXfz2rlmgMHMEg7Zshhi7hC3"
                frameborder="0"
                allow="autoplay; encrypted-media"
                allowfullscreen></iframe>
    </td>
</tr>
<? $tabControl->EndCustomField("ELEMENT_VIDEO");
//</editor-fold>

$backUrl = "/bitrix/admin/sotbit.seometa_list.php?lang=" . LANG;
if (
    !empty($_GET['INFOBLOCK'])
    && !empty($_GET['FROM'])
    && !empty($_GET['TYPE_OF_INFOBLOCK'])
    && !empty($_GET['SECT_FROM'])
) {
    $backUrl = "/bitrix/admin/iblock_section_edit.php?IBLOCK_ID=" . $_GET['INFOBLOCK'] . "&type=" . $_GET['TYPE_OF_INFOBLOCK'] . "&ID=" . $_GET['FROM'] . "&find_section_section=" . $_GET['SECT_FROM'] . "&lang=" . LANG;
}

$arButtonsParams = [
    "back_url" => $backUrl,
];

$tabControl->Buttons($arButtonsParams);
$tabControl->Show();

Asset::getInstance()->addString("
    <link rel='stylesheet' href='//code.jquery.com/ui/1.12.0/themes/smoothness/jquery-ui.css'>
    <script src='//code.jquery.com/ui/1.12.0/jquery-ui.js'></script>
    <script>
    
    $(document).ready(function() {
        $('.progressbar').each(function(){
            val = $(this).parent().parent().find('textarea').val().length;
    
            v = (val/$(this).attr('data-max'))*100;
            if(v>100)
                v = 100;
            $(this).progressbar({value: v});
    
            if(val>0 && val<$(this).attr('data-min')) {
                $(this).find('.ui-progressbar-value').addClass('orange-color-bg');
            } else if(val == 0 || val>$(this).attr('data-max')){
                $(this).find('.ui-progressbar-value').addClass('red-color-bg');
            } else {
                $(this).find('.ui-progressbar-value').addClass('green-color-bg');
            }
    
        });
    
        $('.count_symbol_print span').each(function() {
            l = $(this).parent().parent().find('textarea.count_symbol').val().length;
            $(this).html(l);
            if($(this).hasClass('meta_title')){
                limit_min = " . MIN_SEO_TITLE . ";
                limit_max = " . MAX_SEO_TITLE . ";
            }
            if($(this).hasClass('meta_key')){
                limit_min = " . MIN_SEO_KEY . ";
                limit_max = " . MAX_SEO_KEY . ";
            }
            if($(this).hasClass('meta_descr')){
                limit_min = " . MIN_SEO_DESCR . ";
                limit_max = " . MAX_SEO_DESCR . ";
            }
            if(l>0 && l<limit_min){
                $(this).addClass('orange-color');
            } else {
                if(l==0 || l>limit_max){
                    $(this).addClass('red-color');
                }
                else{
                    $(this).addClass('green-color');
                }
            }
        })
    
        $('textarea.count_symbol').keyup(function(){
            triggerTextarea($(this));
        });
    });
    
    function triggerTextarea(t){
        v = t.parent().find('.count_symbol_print span');
        l = t.val().length;
        v.html(l);
    
         if(v.hasClass('meta_title')){
            limit_min = " . MIN_SEO_TITLE . ";
            limit_max = " . MAX_SEO_TITLE . ";
         }
         if(v.hasClass('meta_key')){
            limit_min = " . MIN_SEO_KEY . ";
            limit_max = " . MAX_SEO_KEY . ";
         }
         if(v.hasClass('meta_descr')){
            limit_min = " . MIN_SEO_DESCR . ";
            limit_max = " . MAX_SEO_DESCR . ";
         }
    
         bar = t.parent().find('.progressbar');
         vl = (l/bar.attr('data-max'))*100;
         if(vl>100)
            vl = 100;
         bar.progressbar({value: vl});
    
         if(l>0 && l<limit_min){
            v.removeClass('green-color').removeClass('red-color').addClass('orange-color');
            t.parent().find('.ui-progressbar-value').removeClass('green-color-bg').removeClass('red-color-bg').addClass('orange-color-bg');
         } else {
            if(l==0 || l>limit_max){
                v.removeClass('green-color').removeClass('orange-color').addClass('red-color');
                t.parent().find('.ui-progressbar-value').removeClass('orange-color-bg').removeClass('green-color-bg').addClass('red-color-bg');
            } else {
                v.removeClass('red-color').removeClass('orange-color').addClass('green-color');
                t.parent().find('.ui-progressbar-value').removeClass('orange-color-bg').removeClass('red-color-bg').addClass('green-color-bg');
            }
         }
    
        return true;
    }
    
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
    
    $(document).on('click','.navmenu-v li.with-prop',function(){
        if($(this).data( 'prop' )!== 'undefined')
        {
            if($(this).closest('tr').find('iframe').length>0)
                {
                    $(this).closest('tr').find('iframe').contents().find('body').append($(this).data( 'prop' ));
                    if($(this).closest('tr').find('textarea') !== undefined) {
                        $(this).closest('tr').find('textarea').insertAtCaret($(this).data( 'prop' ));
                    }
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
                $(this).closest('tr').find('input[name^=\"OG_FIELD_\"]').insertAtCaret($(this).data('prop'));
                $(this).closest('tr').find('input[name^=\"TW_FIELD_\"]').insertAtCaret($(this).data('prop'));
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
    true
);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");

//</editor-fold>