<?

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Sotbit\Seometa\Orm\ConditionTable;
use Sotbit\Seometa\Orm\SeometaStatisticsTable;
use Bitrix\Main\Text\Emoji;

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");
$id_module = 'sotbit.seometa';
Loader::includeModule($id_module);

IncludeModuleLangFile(__FILE__);

$POST_RIGHT = $APPLICATION->GetGroupRight("sotbit.seometa");
if ($POST_RIGHT == "D")
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
Loc::loadMessages(__FILE__);

$sTableID = "b_sotbit_seometa_statistics";
$oSort = new CAdminSorting($sTableID, "ID", "desc");
$lAdmin = new CAdminList($sTableID, $oSort);

function CheckFilter()
{
    global $FilterArr, $lAdmin;
    foreach ($FilterArr as $f) global $$f;
    if ($_REQUEST['del_filter'] == 'Y')
        return false;
    return count($lAdmin->arFilterErrors) == 0;
}

$FilterArr = [
    "find",
    "find_id",
];

$parentID = 0;
$lAdmin->InitFilter($FilterArr);
$arFilter = [];

if (CheckFilter()) {
    if ($find != '' && $find_type = "id") {
        $arFilter['ID'] = $find;
    } elseif ($find_id != '') {
        $arFilter['ID'] = $find_id;
    }

    if ($find_cond_id) {
        $arFilter['CONDITION_ID'] = $find_cond_id;
    }

    if ($find_site_id) {
        $arFilter['SITE_ID'] = $find_site_id;
    }

    if ($find_url) {
        $arFilter['URL'] = "%$find_url%";
    }
    if ($find_in_sitemap) {
        $arFilter['IN_SITEMAP'] = $find_in_sitemap;
    }
    if ($find_no_index) {
        $arFilter['NO_INDEX'] = $find_no_index;
    }
    if ($find_robot_type) {
        $arFilter['ROBOTS_INFO'] = "%$find_robot_type%";
    }
    if($find_page_status){
        $arFilter['PAGE_STATUS'] = $find_page_status;
    }

    if ($find_time1 != "" && $DB->IsDate($find_time1)) {
        $arFilter['>=DATE_CREATE'] = new \Bitrix\Main\Type\DateTime($find_time1 . ' 00:00:00');
    }
    if ($find_time2 != "" && $DB->IsDate($find_time2)) {
        $arFilter['<=DATE_CREATE'] = new \Bitrix\Main\Type\DateTime($find_time2 . ' 23:59:59');
    }

    if ($find_date_check1 != "" && $DB->IsDate($find_date_check1)) {
        $arFilter['>=LAST_DATE_CHECK'] = new \Bitrix\Main\Type\DateTime($find_date_check1 . ' 00:00:00');
    }
    if ($find_date_check2 != "" && $DB->IsDate($find_date_check2)) {
        $arFilter['<=LAST_DATE_CHECK'] = new \Bitrix\Main\Type\DateTime($find_date_check2 . ' 23:59:59');
    }

    if (empty($arFilter['ID'])) unset($arFilter['ID']);
    if (empty($arFilter['CONDITION_ID'])) unset($arFilter['CONDITION_ID']);
    if (empty($arFilter['<=DATE_CREATE'])) unset($arFilter['<=DATE_CREATE']);
    if (empty($arFilter['>=DATE_CREATE'])) unset($arFilter['>=DATE_CREATE']);
}

if ($lAdmin->EditAction()) {
    foreach ($FIELDS as $ID => $arFields) {
        $TYPE = mb_substr($ID, 0, 1);
        $ID = intval(mb_substr($ID, 1));

        if (!$lAdmin->IsUpdated($ID))
            continue;

        $ID = IntVal($ID);
        if ($ID > 0) {
            if ($TYPE == "P") {
                foreach ($arFields as $key => $value)
                    $arData[$key] = $value;
                $result = SeometaStatisticsTable::update($ID, $arData);
                if (!$result->isSuccess()) {
                    $lAdmin->AddGroupError(GetMessage("SEO_META_SAVE_ERROR") . " " . GetMessage("SEO_META_NO_ZAPIS"), $ID);
                }
            }
        } else {
            $lAdmin->AddGroupError(GetMessage("SEO_META_SAVE_ERROR") . " " . GetMessage("SEO_META_NO_ZAPIS"), $ID);
        }
    }
}

if ($arID = $lAdmin->GroupAction()) {
    if ($_REQUEST['action_target'] == 'selected') {
        $rsData = SeometaStatisticsTable::getList([
            'select' => ['*'],
            'filter' => $arFilter,
            'order' => [$by => $order],
        ]);
        while ($arRes = $rsData->Fetch()) {
            $arRes["T"] = "S";
            $arRes['ID'] = "P" . $arRes['ID'];
            $arID[] = $arRes['ID'];
        }
    }

    foreach ($arID as $ID) {
        $TYPE = mb_substr($ID, 0, 1);
        $ID = intval(mb_substr($ID, 1));

        if (mb_strlen($ID) <= 0)
            continue;
        $ID = IntVal($ID);

        switch ($_REQUEST['action']) {
            case "delete":
                if ($TYPE == "P") {
                    $result = SeometaStatisticsTable::delete($ID);
                    if (!$result->isSuccess()) {
                        $lAdmin->AddGroupError(GetMessage("SEO_META_DEL_ERROR") . " " . GetMessage("SEO_META_NO_ZAPIS"), $ID);
                    }
                }
                break;
        }
    }
}

$rsData = SeometaStatisticsTable::getList([
    'select' => ['*'],
    'filter' => $arFilter,
    'order' => [$by => $order],
]);
$arResult = [];
while ($arRes = $rsData->Fetch()) {
    $arRes["T"] = "P";
    $arResult[] = $arRes;
}
$rs = new CDBResult;
$rs->InitFromArray($arResult);
$rsData = new CAdminResult($rs, $sTableID);
$rsData->NavStart();
$lAdmin->NavText($rsData->GetNavPrint(GetMessage("SEO_META_NAV")));
$lAdmin->AddHeaders([
    ["id" => "ID",
        "content" => GetMessage("SEO_META_TABLE_ID"),
        "sort" => "ID",
        "align" => "right",
        "default" => true,
    ],
    ["id" => "URL",
        "content" => GetMessage("SEO_META_TABLE_URL"),
        "sort" => "URL",
        "default" => true,
    ],
    ["id" => "DATE_CREATE",
        "content" => GetMessage("SEO_META_TABLE_DATE_CREATE"),
        "sort" => "DATE_CREATE",
        "default" => true,
    ],
    ["id" => "SITE_ID",
        "content" => GetMessage("SEO_META_TABLE_SITE_ID"),
        "sort" => "SITE_ID",
        "default" => true,
    ],
    ["id" => "META_TITLE",
        "content" => GetMessage("SEO_META_TABLE_META_TITLE"),
        "default" => true,
    ],
    ["id" => "META_KEYWORDS",
        "content" => GetMessage("SEO_META_TABLE_META_KEYWORDS"),
        "default" => true,
    ],
    ["id" => "META_DESCRIPTION",
        "content" => GetMessage("SEO_META_TABLE_META_DESCRIPTION"),
        "default" => true,
    ],
    ["id" => "IN_SITEMAP",
        "content" => GetMessage("SEO_META_TABLE_IN_SITEMAP"),
        "sort" => "IN_SITEMAP",
        "default" => true,
    ],
    ["id" => "NO_INDEX",
        "content" => GetMessage("SEO_META_TABLE_NO_INDEX"),
        "sort" => "NO_INDEX",
        "default" => true,
    ],
    ["id" => "CONDITION_NAME",
        "content" => GetMessage("SEO_META_TABLE_CONDITION_NAME"),
        "sort" => "CONDITION_ID",
        "default" => true,
    ],
    ["id" => "ROBOTS_INFO_GOOGLE",
        "content" => GetMessage("SEO_META_TABLE_ROBOTS_INFO_GOOGLE"),
        "default" => true,
    ],
    ["id" => "ROBOTS_INFO_YANDEX",
        "content" => GetMessage("SEO_META_TABLE_ROBOTS_INFO_YANDEX"),
        "default" => true,
    ],
    ["id" => "PAGE_STATUS",
        "content" => GetMessage("SEO_META_TABLE_PAGE_STATUS"),
        "default" => true,
    ],
    ["id" => "LAST_DATE_CHECK",
        "content" => GetMessage("SEO_META_TABLE_LAST_DATE_CHECK"),
        "sort" => "LAST_DATE_CHECK",
        "default" => true,
    ],
    ["id" => "SORT",
        "content" => GetMessage("SEO_META_TABLE_SORT"),
        "sort" => "SORT",
        "default" => true,
    ],
]);

while ($arRes = $rsData->NavNext(true, "f_")):
    $row =& $lAdmin->AddRow($f_T . $f_ID, $arRes);
    $row->AddInputField("SORT", ["size" => 20]);
    if ($arRes['CONDITION_ID'] != null)
        $name = ConditionTable::getById($arRes['CONDITION_ID'])->fetch();
    else
        $name = null;
    if ($name)
        $name = '<a href="/bitrix/admin/sotbit.seometa_edit.php?ID=' . $name['ID'] . '&lang=' . LANG . '" target="_blank">#' . $name['ID'] . ' ' . $name['NAME'] . '</a>';
    else $name = '';
    $row->AddViewField('CONDITION_NAME', $name);

    if($metaTitle = unserialize($arRes['META_TITLE'])){
        $title = $metaTitle;
    }else{
        $title = null;
    }
    if($metaKeyWords = unserialize($arRes['META_KEYWORDS'])){
        $keyWords = $metaKeyWords;
    }else{
        $keyWords = null;
    }
    if($metaDescription = unserialize($arRes['META_DESCRIPTION'])){
        $description = $metaDescription;
    }else{
        $description = null;
    }


    $title = $title['COINCIDENCE'] === 'Y' ? Loc::getMessage("SEO_META_POST_COINCIDENCE", ['#CONTENT#'=>Emoji::decode($title['CONTENT'])])
                                           : Loc::getMessage("SEO_META_POST_NO_COINCIDENCE", ['#CONTENT#'=>Emoji::decode($title['CONTENT'])]);
    $keyWords = $keyWords['COINCIDENCE'] === 'Y' ? Loc::getMessage("SEO_META_POST_COINCIDENCE", ['#CONTENT#'=>Emoji::decode($keyWords['CONTENT'])])
                                                 : Loc::getMessage("SEO_META_POST_NO_COINCIDENCE", ['#CONTENT#'=>Emoji::decode($keyWords['CONTENT'])]);
    $description = $description['COINCIDENCE'] === 'Y' ? Loc::getMessage("SEO_META_POST_COINCIDENCE", ['#CONTENT#'=>Emoji::decode($description['CONTENT'])])
                                                       : Loc::getMessage("SEO_META_POST_NO_COINCIDENCE", ['#CONTENT#'=>Emoji::decode($description['CONTENT'])]);

    $row->AddViewField('META_TITLE', $title);
    $row->AddViewField('META_KEYWORDS', $keyWords);
    $row->AddViewField('META_DESCRIPTION', $description);

    $row->AddViewField('IN_SITEMAP', $arRes['IN_SITEMAP'] == 'Y' ? Loc::getMessage("SEO_META_POST_YES") : Loc::getMessage("SEO_META_POST_NO"));
    $row->AddViewField('NO_INDEX', $arRes['NO_INDEX'] == 'Y' ? Loc::getMessage("SEO_META_POST_YES") : Loc::getMessage("SEO_META_POST_NO"));

    if ($unserialRobots = unserialize($arRes['ROBOTS_INFO'])) {
        $googleBot = $unserialRobots['GoogleBot'];
        $yandexBot = $unserialRobots['YandexBot'];
    }
    $googleBot['CHECK'] = $googleBot['CHECK'] === 'Y' ? Loc::getMessage("SEO_META_BOTS_YES", ["#TIME#"=>$googleBot['TIME_CHECK']])
                                                      : Loc::getMessage("SEO_META_POST_NO");
    $yandexBot['CHECK'] = $yandexBot['CHECK'] === 'Y' ? Loc::getMessage("SEO_META_BOTS_YES", ["#TIME#"=>$yandexBot['TIME_CHECK']])
                                                      : Loc::getMessage("SEO_META_POST_NO");
    $row->AddViewField('ROBOTS_INFO_GOOGLE', $googleBot['CHECK']);
    $row->AddViewField('ROBOTS_INFO_YANDEX', $yandexBot['CHECK']);

    if (($arRes['PAGE_STATUS'] >= 200 && $arRes['PAGE_STATUS'] <= 299)) {
        $pageStatus = Loc::getMessage("SEO_META_TABLE_PAGE_STATUS_OK", ["#STATUS#"=>$arRes['PAGE_STATUS']]);
    } elseif (($arRes['PAGE_STATUS'] >= 400 && $arRes['PAGE_STATUS'] <= 599)) {
        $pageStatus = Loc::getMessage("SEO_META_TABLE_PAGE_STATUS_ERROR", ["#STATUS#"=>$arRes['PAGE_STATUS']]);
    }elseif (($arRes['PAGE_STATUS'] >= 300 && $arRes['PAGE_STATUS'] <= 399)){
        $pageStatus = Loc::getMessage("SEO_META_TABLE_PAGE_STATUS_REDIRECT", ["#STATUS#"=>$arRes['PAGE_STATUS']]);
    }
    $row->AddViewField('PAGE_STATUS', $pageStatus);

    $arActions = [];

    if ($POST_RIGHT >= "W")
        $arActions[] = [
            "ICON" => "delete",
            "TEXT" => GetMessage("SEO_META_DEL"),
            "ACTION" => "if(confirm('" . GetMessage('SEO_META_DEL_CONF') . "')) " . $lAdmin->ActionDoGroup($f_T . $f_ID, "delete")
        ];

    $arActions[] = ["SEPARATOR" => true];
    if (is_set($arActions[count($arActions) - 1], "SEPARATOR"))
        unset($arActions[count($arActions) - 1]);

    $row->AddActions($arActions);
endwhile;

$lAdmin->AddFooter(
    [
        ["title" => GetMessage("SEO_META_LIST_SELECTED"), "value" => $rsData->SelectedRowsCount()],
        ["counter" => true, "title" => GetMessage("SEO_META_LIST_CHECKED"), "value" => "0"],
    ]
);

$lAdmin->AddGroupActionTable([
    "delete" => GetMessage("SEO_META_LIST_DELETE"),
]);

$lAdmin->CheckListMode();

$APPLICATION->SetTitle(GetMessage("SEO_META_TITLE"));

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");

$oFilter = new CAdminFilter(
    $sTableID . "_filter",
    [
        GetMessage("SEO_META_FILTER_CONDITION_ID"),
        GetMessage("SEO_META_FILTER_SITE_ID"),
        GetMessage("SEO_META_FILTER_LINK"),
        GetMessage("SEO_META_IN_SITEMAP"),
        GetMessage("SEO_META_NO_INDEX"),
        GetMessage("SEO_META_ROBOT_TYPE"),
        GetMessage("SEO_META_PAGE_STATUS"),
        GetMessage("SEO_META_TIME"),
        GetMessage("SEO_META_LAST_CHECK"),
    ]
);

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

?>

    <form name="find_form" method="get" action="<? echo $APPLICATION->GetCurPage(); ?>">
        <? $oFilter->Begin(); ?>
        <tr>
            <td><b><?= GetMessage("SEO_META_FIND") ?>:</b></td>
            <td>
                <input type="text" size="25" name="find" value="<? echo htmlspecialchars($find) ?>"
                       title="<?= GetMessage("SEO_META_FIND_TITLE") ?>">
                <?
                $arr = [
                    "reference" => [
                        "ID",
                    ],
                    "reference_id" => [
                        "id",
                    ]
                ];
                echo SelectBoxFromArray("find_type", $arr, $find_type, "", "");
                ?>
            </td>
        </tr>
        <tr>
            <td><?= GetMessage("SEO_META_ID") ?>:</td>
            <td>
                <input type="text" name="find_id" size="47" value="<? echo htmlspecialchars($find_id) ?>">
            </td>
        </tr>
        <tr>
            <td><?= GetMessage("SEO_META_FILTER_CONDITION_ID") ?>:</td>
            <td>
                <input type="text" name="find_cond_id" size="47" value="<? echo htmlspecialchars($find_cond_id) ?>">
            </td>
        </tr>
        <tr>
            <td><?= GetMessage("SEO_META_FILTER_SITE_ID") ?>:</td>
            <td>
                <input type="text" name="find_site_id" size="47" value="<? echo htmlspecialchars($find_site_id) ?>">
            </td>
        </tr>
        <tr>
            <td><?= GetMessage("SEO_META_FILTER_LINK") ?>:</td>
            <td>
                <input type="text" name="find_url" size="47" value="<? echo htmlspecialchars($find_url) ?>">
            </td>
        </tr>
        <tr>
            <td><?= Loc::getMessage("SEO_META_IN_SITEMAP") ?>:</td>
            <td>
                <?
                $arr = [
                    "reference" => [
                        Loc::getMessage("SEO_META_IN_SITEMAP_NO_MATTER"),
                        Loc::getMessage("SEO_META_POST_YES"),
                        Loc::getMessage("SEO_META_POST_NO"),
                    ],
                    "reference_id" => [
                        "",
                        "Y",
                        "N",
                    ]
                ];
                echo SelectBoxFromArray("find_in_sitemap", $arr, $find_in_sitemap, "", "");
                ?>
            </td>
        </tr>
        <tr>
            <td><?= Loc::getMessage("SEO_META_NO_INDEX") ?>:</td>
            <td>
                <?
                $arr = [
                    "reference" => [
                        Loc::getMessage("SEO_META_IN_SITEMAP_NO_MATTER"),
                        Loc::getMessage("SEO_META_POST_YES"),
                        Loc::getMessage("SEO_META_POST_NO"),
                    ],
                    "reference_id" => [
                        "",
                        "Y",
                        "N",
                    ]
                ];
                echo SelectBoxFromArray("find_no_index", $arr, $find_no_index, "", "");
                ?>
            </td>
        </tr>
        <tr>
            <td><?= Loc::getMessage("SEO_META_ROBOT_TYPE") ?>:</td>
            <td>
                <?
                $arr = [
                    "reference" => [
                        Loc::getMessage("SEO_META_IN_SITEMAP_NO_MATTER"),
                        Loc::getMessage("SEO_META_GOOGLE_BOT"),
                        Loc::getMessage("SEO_META_YANDEX_BOT"),
                    ],
                    "reference_id" => [
                        "",
                        "GoogleBot",
                        "YandexBot",
                    ]
                ];
                echo SelectBoxFromArray("find_robot_type", $arr, $find_robot_type, "", "");
                ?>
            </td>
        </tr>
        <tr>
            <td><?= Loc::getMessage("SEO_META_PAGE_STATUS") ?>:</td>
            <td>
                <?
                $arr = [
                    "reference" => [
                        Loc::getMessage("SEO_META_IN_SITEMAP_NO_MATTER"),
                        "200",
                        "404",
                    ],
                    "reference_id" => [
                        "",
                        "200",
                        "404",
                    ]
                ];
                echo SelectBoxFromArray("find_page_status", $arr, $find_page_status, "", "");
                ?>
            </td>
        </tr>
        <tr>
            <td><? echo GetMessage("SEO_META_TIME") . " (" . FORMAT_DATE . "):" ?></td>
            <td><? echo CalendarPeriod("find_time1", $find_time1, "find_time2", $find_time2, "find_form", "Y") ?></td>
        </tr>
        <tr>
            <td><? echo GetMessage("SEO_META_LAST_CHECK") . " (" . FORMAT_DATE . "):" ?></td>
            <td><? echo CalendarPeriod("find_date_check1", $find_date_check1, "find_date_check2", $find_date_check2, "find_form", "Y") ?></td>
        </tr>
        <?
        $oFilter->Buttons(["table_id" => $sTableID, "url" => $APPLICATION->GetCurPage(), "form" => "find_form"]);
        $oFilter->End();
        ?>
    </form>
    <?
    $rsSites = CSite::GetList(
        $by = "sort",
        $order = "desc",
        [
            "ACTIVE" => "Y"
        ]
    );

    while($arSite = $rsSites->Fetch()) {
        if(Option::get("sotbit.seometa",'INC_STATISTIC','N',$arSite['LID']) === 'Y'){
            $activeStat[$arSite['LID']] = Option::get("sotbit.seometa",'INC_STATISTIC','N',$arSite['LID']);
        }
    }
    if(!$activeStat){ ?>
        <div class="adm-info-message-wrap">
            <div class="adm-info-message"><?= GetMessage("SEO_META_NOTE_FOR_WORK") ?></div>
        </div>
   <? } ?>
<?

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

$lAdmin->DisplayList();

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");
?>