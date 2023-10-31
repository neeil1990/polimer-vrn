<?

use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;

require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

IncludeModuleLangFile( __FILE__ );

$moduleId = "sotbit.seometa";

if ($APPLICATION->GetGroupRight("sotbit.seometa") < "R") {
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}

Loc::loadMessages(__FILE__);

require_once ($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin.php');
require_once ($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'.$moduleId.'/classes/general/CModuleOptions.php');
require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$moduleId."/include.php");

if($REQUEST_METHOD == "POST" && mb_strlen( $RestoreDefaults ) > 0 && check_bitrix_sessid())
{
	COption::RemoveOption( $moduleId );
	$z = CGroup::GetList( $v1 = "id", $v2 = "asc", [
			"ACTIVE" => "Y",
			"ADMIN" => "N"
	] );
	while( $zr = $z->Fetch() )
		$APPLICATION->DelGroupRight( $moduleId, [
				$zr["ID"]
		] );
	if((mb_strlen( $Apply )>0)||(mb_strlen( $RestoreDefaults )>0))
		LocalRedirect( $APPLICATION->GetCurPage()."?lang=".LANGUAGE_ID."&mid=".urlencode( $mid )."&tabControl_active_tab=".urlencode( $_REQUEST["tabControl_active_tab"] )."&back_url_settings=".urlencode( $_REQUEST["back_url_settings"] ) );
	else
		LocalRedirect( $_REQUEST["back_url_settings"] );
}

$FilterType = [
	"REFERENCE" => [
		GetMessage($moduleId.'_FILTER_TYPE_bitrix_chpu'),
		GetMessage($moduleId.'_FILTER_TYPE_bitrix_not_chpu'),
		GetMessage($moduleId.'_FILTER_TYPE_misshop_chpu'),
		GetMessage($moduleId.'_FILTER_TYPE_combox_chpu'),
//		GetMessage($module_id.'_FILTER_TYPE_combox_not_chpu')
	],
    "REFERENCE_ID" => [
		"bitrix_chpu",
		"bitrix_not_chpu",
		"misshop_chpu",
		"combox_chpu",
//		"combox_not_chpu"
	]
];

if(\Bitrix\Main\Loader::includeModule("currency")) {
    $arCurrencyTypes = [];

    $lcur = CCurrency::GetList(($by="name"), ($order="asc"), $lang);
    while($lcur_res = $lcur->Fetch()) {
        $arCurrencyTypes['REFERENCE'][] = "[" . $lcur_res['CURRENCY'] . "] " . $lcur_res['FULL_NAME'];
        $arCurrencyTypes['REFERENCE_ID'][] = $lcur_res['CURRENCY'];

        $baseCurrency = ($lcur_res['BASE'] == 'Y' ? $lcur_res['CURRENCY'] : '');
    }
}

$arTabs = [
	[
		'DIV' => 'edit1',
		'TAB' => GetMessage( $moduleId.'_edit1' ),
		'ICON' => '',
		'TITLE' => GetMessage( $moduleId.'_edit1' ),
		'SORT' => '10'
	],
];

$arGroups = [
	'GROUP_SETTINGS' => [
		'TITLE' => GetMessage( $moduleId.'_GROUP_SETTINGS' ),
		'TAB' => 0
	],
    'GROUP_SETTINGS_FOR_STAT' =>[
        'TITLE' => GetMessage($moduleId.'_GROUP_SETTINGS_FOR_STAT'),
        'TAB' => 0,
    ],
    'GROUP_SETTINGS_FOR_PROG' => [
        'TITLE' => GetMessage( $moduleId.'_GROUP_SETTINGS_FOR_PROG' ),
        'TAB' => 0
    ],
];

$site_id = $_REQUEST['site'];

$arOptions = [
	'FILTER_TYPE' => [
		'GROUP' => 'GROUP_SETTINGS',
		'TITLE' => GetMessage( $moduleId.'_FILTER_TYPE' ),
		'TYPE' => 'SELECT',
		'VALUES' => $FilterType,
		'DEFAULT' => 'bitrix_chpu',
		'REFRESH' => 'N',
		'SORT' => '1',
		'NOTES_ENUM' => GetMessage( $moduleId.'_FILTER_TYPE_NOTE' ),
	],
	'FILTER_SEF' => [
		'GROUP' => 'GROUP_SETTINGS',
		'TITLE' => GetMessage( $moduleId.'_FILTER_SEF' ),
		'TYPE' => 'STRING',
		'DEFAULT' => '',
		'REFRESH' => 'N',
		'SORT' => '3',
		'NOTES_ENUM' => GetMessage( $moduleId.'_FILTER_SEF_NOTE' ),
	],
    'PRODUCT_AVAILABLE_FOR_COND' => [
        'GROUP' => 'GROUP_SETTINGS',
        'TITLE' => GetMessage( $moduleId.'_PRODUCT_AVAILABLE_FOR_COND'),
        'TYPE' => 'CHECKBOX',
        'SORT' => '4',
        'REFRESH' => 'N',
        'DEFAULT' => 'N',
        'NOTES' => GetMessage( $moduleId.'_PRODUCT_AVAILABLE_FOR_COND_NOTE' ),
    ],
	'NO_INDEX_'.$site_id => [
		'GROUP' => 'GROUP_SETTINGS',
		'TITLE' => GetMessage( $moduleId.'_NO_INDEX' ),
		'TYPE' => 'CHECKBOX',
		'REFRESH' => 'N',
		'SORT' => '5',
		'DEFAULT' => 'Y',
		'NOTES' => GetMessage( $moduleId.'_NO_INDEX_NOTE' ),
	],
	'PAGENAV_'.$site_id => [
		'GROUP' => 'GROUP_SETTINGS',
		'TITLE' => GetMessage( $moduleId.'_PAGENAV' ),
		'TYPE' => 'TEXT',
		'REFRESH' => 'N',
		'SORT' => '15',
		'COLS' => 40,
		'ROWS' => 1,
		'DEFAULT' => "",
		'NOTES' => GetMessage( $moduleId.'_PAGENAV_NOTE' ),
    ],
    'PAGINATION_TEXT_'.$site_id => [
		'GROUP' => 'GROUP_SETTINGS',
		'TITLE' => GetMessage($moduleId . '_PAGINATION_TEXT'),
		'TYPE' => 'TEXT',
		'REFRESH' => 'N',
		'SORT' => '20',
		'COLS' => 40,
		'ROWS' => 1,
		'DEFAULT' => "",
		'NOTES' => GetMessage($moduleId . '_PAGINATION_TEXT_NOTE'),
    ],
	'USE_CANONICAL_'.$site_id => [
		'GROUP' => 'GROUP_SETTINGS',
		'TITLE' => GetMessage( $moduleId.'_USE_CANONICAL' ),
		'TYPE' => 'CHECKBOX',
		'REFRESH' => 'N',
		'SORT' => '25',
		'DEFAULT' => 'Y',
	],
	'RETURN_AJAX_'.$site_id => [
		'GROUP' => 'GROUP_SETTINGS',
		'TITLE' => GetMessage( $moduleId.'_RETURN_AJAX' ),
		'TYPE' => 'CHECKBOX',
		'REFRESH' => 'N',
		'SORT' => '25',
		'DEFAULT' => 'N',
		'NOTES' => GetMessage( $moduleId.'_RETURN_AJAX_NOTE' ),
	],
	'MANAGED_CACHE_ON' => [
		'GROUP' => 'GROUP_SETTINGS',
		'TITLE' => GetMessage( $moduleId.'_MANAGED_CACHE_ON' ),
		'TYPE' => 'CHECKBOX',
		'REFRESH' => 'N',
		'SORT' => '25',
		'DEFAULT' => 'N',
	],
	'IS_SET_ACTIVE' => [
		'GROUP' => 'GROUP_SETTINGS',
		'TITLE' => GetMessage( $moduleId.'_IS_SET_ACTIVE' ),
		'TYPE' => 'CHECKBOX',
		'REFRESH' => 'N',
		'SORT' => '25',
		'DEFAULT' => 'N',
	],
    'INC_STATISTIC' => [
        'GROUP' => 'GROUP_SETTINGS_FOR_STAT',
        'TITLE' => GetMessage( $moduleId.'_INC_STATISTIC' ),
        'TYPE' => 'CHECKBOX',
        'REFRESH' => 'N',
        'SORT' => '35',
        'DEFAULT' => 'N',
        'NOTES' => GetMessage( $moduleId.'_INC_STATISTIC_NOTE' )
    ],
    'PERIOD_STATISTIC' => [
        'GROUP' => 'GROUP_SETTINGS_FOR_STAT',
        'TITLE' => GetMessage( $moduleId.'_PERIOD_STATISTIC' ),
        'TYPE' => 'STRING',
        'REFRESH' => 'N',
        'SORT' => '35',
        'DEFAULT' => '86400',
        'NOTES' => GetMessage( $moduleId.'_PERIOD_STATISTIC_NOTE' )
    ],
    'AGENT_PERIOD_STATISTIC' => [
        'GROUP' => 'GROUP_SETTINGS_FOR_STAT',
        'TITLE' => GetMessage( $moduleId.'_AGENT_PERIOD_STATISTIC' ),
        'TYPE' => 'CHECKBOX',
        'REFRESH' => 'N',
        'DEFAULT' => 'N',
        'SORT' => '36',
    ],
    'AGENT_LIMIT_STATISTIC' => [
        'GROUP' => 'GROUP_SETTINGS_FOR_STAT',
        'TITLE' => GetMessage( $moduleId.'_AGENT_LIMIT_STATISTIC' ),
        'TYPE' => 'STRING',
        'REFRESH' => 'N',
        'DEFAULT' => '100',
        'SORT' => '36',
        'NOTES' => GetMessage( $moduleId.'_AGENT_LIMIT_STATISTIC_NOTES' )
    ],
    'AGENT_EXEC' => [
        'GROUP' => 'GROUP_SETTINGS_FOR_STAT',
        'TITLE' => GetMessage( $moduleId.'_AGENT_EXEC' ),
        'TYPE' => 'CALENDAR',
        'REFRESH' => 'N',
        'DEFAULT' => '',
        'SORT' => '36',
    ],
    'AGENT_INTERVAL' => [
        'GROUP' => 'GROUP_SETTINGS_FOR_STAT',
        'TITLE' => GetMessage( $moduleId.'_AGENT_INTERVAL' ),
        'TYPE' => 'STRING',
        'REFRESH' => 'N',
        'DEFAULT' => '86400',
        'SORT' => '36',
        'NOTES' => GetMessage( $moduleId.'_AGENT_INTERVAL_NOTE' )
    ],
    'FILTER_EXCEPTION_SETTINGS' => [
        'GROUP' => 'GROUP_SETTINGS_FOR_PROG',
        'TITLE' => GetMessage( $moduleId.'_FILTER_EXCEPTION_SETTINGS' ),
        'TYPE' => 'TEXT',
        'REFRESH' => 'N',
        'SORT' => '150',
        'COLS' => 40,
        'ROWS' => 1,
        'DEFAULT' => "",
        'NOTES' => GetMessage( $moduleId.'_FILTER_EXCEPTION_SETTINGS_NOTE' ),
    ],
    'PARAMS_EXCEPTION_SETTINGS' => [
        'GROUP' => 'GROUP_SETTINGS_FOR_PROG',
        'TITLE' => GetMessage( $moduleId.'_PARAMS_EXCEPTION_SETTINGS' ),
        'TYPE' => 'TEXT',
        'REFRESH' => 'N',
        'SORT' => '150',
        'COLS' => 40,
        'ROWS' => 1,
        'DEFAULT' => "",
        'NOTES' => GetMessage( $moduleId.'_PARAMS_EXCEPTION_SETTINGS_NOTE' ),
    ],
    'SEOMETA_SITEMAP_FILE_SIZE' => [
        'GROUP' => 'GROUP_SETTINGS_FOR_PROG',
        'TITLE' => GetMessage( $moduleId.'_SITEMAP_FILE_SIZE' ),
        'TYPE' => 'STRING',
        'REFRESH' => 'N',
        'SORT' => '150',
        'COLS' => 40,
        'ROWS' => 1,
        'DEFAULT' => "50",
        'NOTES' => GetMessage( $moduleId.'_SITEMAP_FILE_SIZE_NOTE' ),
    ],
    'SEOMETA_SITEMAP_COUNT_LINKS' => [
        'GROUP' => 'GROUP_SETTINGS_FOR_PROG',
        'TITLE' => GetMessage( $moduleId.'_SITEMAP_COUNT_LINKS' ),
        'TYPE' => 'STRING',
        'REFRESH' => 'N',
        'SORT' => '150',
        'COLS' => 40,
        'ROWS' => 1,
        'DEFAULT' => "50000",
        'NOTES' => GetMessage( $moduleId.'_SITEMAP_COUNT_LINKS_NOTE' ),
    ],
    'SEOMETA_SITEMAP_COUNT_LINKS_FOR_OPERATION' => [
        'GROUP' => 'GROUP_SETTINGS_FOR_PROG',
        'TITLE' => GetMessage( $moduleId.'_SITEMAP_COUNT_LINKS_FOR_OPERATION' ),
        'TYPE' => 'STRING',
        'REFRESH' => 'N',
        'SORT' => '150',
        'COLS' => 40,
        'ROWS' => 1,
        'DEFAULT' => "10000",
        'NOTES' => GetMessage( $moduleId.'_SITEMAP_COUNT_LINKS_FOR_OPERATION_NOTE' ),
    ],
];

if(\Bitrix\Main\Loader::includeModule("currency")) {
    $arOptions['CURRENCY_TYPE'] = [
        'GROUP' => 'GROUP_SETTINGS',
        'TITLE' => GetMessage( $moduleId.'_CURRENCY_TYPE' ),
        'TYPE' => 'SELECT',
        'VALUES' => $arCurrencyTypes,
        'DEFAULT' => $baseCurrency,
        'REFRESH' => 'N',
        'SORT' => '30',
        'NOTES_ENUM' => GetMessage( $moduleId.'_CURRENCY_TYPE_NOTE' ),
    ];
}

if( CCSeoMeta::ReturnDemo() == 2){
    ?>
    <div class="adm-info-message-wrap adm-info-message-red">
        <div class="adm-info-message">
            <div class="adm-info-message-title"><?=Loc::getMessage("SEO_META_DEMO")?></div>
            <div class="adm-info-message-icon"></div>
        </div>
    </div>
    <?
}
if( CCSeoMeta::ReturnDemo() == 3 || CCSeoMeta::ReturnDemo() == 0)
{
    ?>
    <div class="adm-info-message-wrap adm-info-message-red">
        <div class="adm-info-message">
            <div class="adm-info-message-title"><?=Loc::getMessage("SEO_META_DEMO_END")?></div>
            <div class="adm-info-message-icon"></div>
        </div>
    </div>
    <?
    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
    return '';
}

$RIGHT = $APPLICATION->GetGroupRight( $moduleId );
if($RIGHT != "D")
{
    if(isset($_POST['SEOMETA_SITEMAP_FILE_SIZE']) && (intval($_POST['SEOMETA_SITEMAP_FILE_SIZE']) > 50 || intval($_POST['SEOMETA_SITEMAP_FILE_SIZE']) == 0)) {
        $_POST['SEOMETA_SITEMAP_FILE_SIZE'] = 50;
        $_REQUEST['SEOMETA_SITEMAP_FILE_SIZE'] = 50;
    }
    if(isset($_POST['SEOMETA_SITEMAP_COUNT_LINKS']) && (intval($_POST['SEOMETA_SITEMAP_COUNT_LINKS']) > 50000 || intval($_POST['SEOMETA_SITEMAP_COUNT_LINKS']) == 0)) {
        $_POST['SEOMETA_SITEMAP_COUNT_LINKS'] = 50000;
        $_REQUEST['SEOMETA_SITEMAP_COUNT_LINKS'] = 50000;
    }
    if(isset($_POST['PERIOD_STATISTIC']) && (intval($_POST['PERIOD_STATISTIC']) <= 0)) {
        $_POST['PERIOD_STATISTIC'] = 86400;
        $_REQUEST['PERIOD_STATISTIC'] = 86400;
    }
    if(isset($_POST['AGENT_LIMIT_STATISTIC']) && (intval($_POST['AGENT_LIMIT_STATISTIC']) <= 0)) {
        $_POST['AGENT_LIMIT_STATISTIC'] = 10;
        $_REQUEST['AGENT_LIMIT_STATISTIC'] = 10;
    }elseif (isset($_POST['AGENT_LIMIT_STATISTIC']) && (intval($_POST['AGENT_LIMIT_STATISTIC']) > 0)){
        $_POST['AGENT_LIMIT_STATISTIC'] = (int) $_POST['AGENT_LIMIT_STATISTIC'];
        $_REQUEST['AGENT_LIMIT_STATISTIC'] = (int) $_REQUEST['AGENT_LIMIT_STATISTIC'];
    }
    if($_POST){
        $nameAgentActualStat = "\Sotbit\Seometa\Agent::actualizedSeoMetaStatAgent('$site_id', 0);";
        $arAgentActualStat = CAgent::GetList([], ["NAME"=>$nameAgentActualStat])->Fetch();
        $createAgent = $_POST['AGENT_PERIOD_STATISTIC'] ?: 'N';
        $agentExec = $_POST['AGENT_EXEC'] ?: Option::get("sotbit.seometa",'AGENT_EXEC','', $site_id);
        if(!$agentExec){
            $currentDate = new \Bitrix\Main\Type\DateTime();
            $_POST['AGENT_EXEC'] = $currentDate;
            $_REQUEST['AGENT_EXEC'] = $currentDate;
            $agentExec = $currentDate;
        }
        $agentInterval = $_POST['AGENT_INTERVAL'] ?: Option::get("sotbit.seometa",'AGENT_INTERVAL','86400', $site_id);
        if(!$agentInterval){
            $_POST['AGENT_INTERVAL'] = '86400';
            $_REQUEST['AGENT_INTERVAL'] = '86400';
            $agentInterval = '86400';
        }
        if($arAgentActualStat){
            CAgent::Update($arAgentActualStat['ID'], [
                "AGENT_INTERVAL" => $agentInterval,
                "ACTIVE" => $createAgent,
            ]);
        }elseif($createAgent === 'Y'){
            CAgent::AddAgent(
                $nameAgentActualStat,
                'sotbit.seometa',
                "Y",
                $agentInterval,
                "",
                "Y",
                $agentExec
            );
        }
    }

	$showRightsTab = false;
	$opt = new CModuleOptions( $moduleId, $arTabs, $arGroups, $arOptions, $showRightsTab );
	$opt->ShowHTML();
}
$APPLICATION->SetTitle( GetMessage( $moduleId.'_TITLE' ) );

?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");?>
