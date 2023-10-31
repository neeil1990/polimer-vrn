<?
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/prolog.php");

if (!$USER->IsAdmin())
    $APPLICATION->AuthForm();

IncludeModuleLangFile(__FILE__);

if (function_exists('mb_internal_encoding'))
    mb_internal_encoding('ISO-8859-1');

$APPLICATION->SetTitle(GetMessage("BITRIX_XSCAN_HTACCESS"));
require($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/include/prolog_admin_after.php");
\Bitrix\Main\UI\Extension::load(["ui.layout-form", "ui.buttons", "ui.dialogs.messagebox", "ui.progressbar", "ui.alerts", "sidepanel"]);

?>

<form method="post">
    <?= bitrix_sessid_post() ?>
    <div class="ui-form-row-inline">
        <div class="ui-form-row ui-form-row-line">
            <div class="ui-form-content">
                <button type="submit" id="start_button" name="rescan" class="ui-btn ui-btn-primary"><?= GetMessage("BITRIX_XSCAN_RESCAN") ?></button>
                <button type="submit" id="renew" name="renew" class="ui-btn ui-btn-primary"><?= GetMessage("BITRIX_XSCAN_RENEW") ?></button>
            </div>
        </div>
    </div>
</form>

<?php

$grid_options = new Bitrix\Main\Grid\Options('xscan_htaccess');
$nav_params = $grid_options->GetNavParams();

$nav = new \Bitrix\Main\UI\PageNavigation("xscan_htaccess");
$nav->allowAllRecords(false)
    ->setPageSize($nav_params['nPageSize']);

if (isset($_GET['clear_nav']) && $_GET['clear_nav'] == 'Y'){
    $nav->setCurrentPage(1);
} elseif (isset($_GET['grid_action']) && $_GET['grid_action'] === 'more' && $_GET['grid_id'] === $grid_options->getId()) {
    $nav->setCurrentPage($_GET['report_list']);
} elseif (isset($_GET['grid_action']) && $_GET['grid_action'] === 'pagination') {
    $nav->initFromUri();
} elseif (isset($_SESSION['xscan_htacess_page'])) {
    $nav->setCurrentPage($_SESSION['xscan_htacess_page']);
}

$_SESSION['xscan_htacess_page'] = $nav->getCurrentPage();

function search_htaccess($path, &$result)
{
    if (is_dir($path)) // dir
    {
        $p = realpath($path);

        if (is_link($path)) {
            $d = dirname($path);
            if (strpos($p, $d) !== false || strpos($d, $p) !== false) { // если симлинк ведет на папку внутри структуры сайта или на папку выше
                return;
            }
        }

        $dir = opendir($path);
        while ($item = readdir($dir)) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            search_htaccess($path . '/' . $item, $result);
        }
        closedir($dir);
    } elseif (basename($path) === '.htaccess')
    {
        $result[] = $path;
    }
}


$path = rtrim($_SERVER['DOCUMENT_ROOT'], '/');
$files = [];
$cache = \Bitrix\Main\Data\Cache::createInstance();

$root_ht = <<<HTACCESS
Options -Indexes 
ErrorDocument 404 /404.php

<IfModule mod_php.c>
php_flag session.use_trans_sid off
</IfModule>

<IfModule mod_php7.c>
php_flag session.use_trans_sid off
</IfModule>

<IfModule mod_rewrite.c>
Options +FollowSymLinks
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-l
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !/bitrix/urlrewrite.php$
RewriteRule ^(.*)$ /bitrix/urlrewrite.php [L]
RewriteRule .* - [E=REMOTE_USER:%{HTTP:Authorization}]
</IfModule>

<IfModule mod_dir.c>
DirectoryIndex index.php index.html
</IfModule>

<IfModule mod_expires.c>
ExpiresActive on
ExpiresByType image/jpeg "access plus 3 days"
ExpiresByType image/gif "access plus 3 day"
ExpiresByType image/png "access plus 3 day"
ExpiresByType text/css "access plus 3 day"
ExpiresByType application/javascript "access plus 3 day"  
</IfModule>
HTACCESS;

$upload_ht = <<<HTACCESS
<IfModule mod_mime.c>
<Files ~ \.(php|php3|php4|php5|php6|php7|phtm|phtml|pl|asp|aspx|cgi|dll|exe|shtm|shtml|fcg|fcgi|fpl|asmx|pht|py|psp|rb|var)>
    SetHandler text/plain
    ForceType text/plain
</Files>
</IfModule>
<IfModule mod_php5.c>
php_flag engine off
</IfModule>
HTACCESS;

$deny_ht = "Deny from All";

if (isset($_REQUEST['rescan'])  && check_bitrix_sessid()){
    $_SESSION['xscan_htacess_page'] = 1;
    $cache->cleanDir('xscan');
    LocalRedirect($_SERVER['REQUEST_URI']);
    die();

}
elseif (isset($_REQUEST['renew']) && check_bitrix_sessid()){
    $_SESSION['xscan_htacess_page'] = 1;
    search_htaccess($path, $files);
    foreach($files as $value){
        unlink($value);
    }

    file_put_contents($path . '/.htaccess', $root_ht);
    file_put_contents($path . '/upload/.htaccess', $upload_ht);
    file_put_contents($path . '/bitrix/modules/.htaccess', $deny_ht);
    file_put_contents($path . '/bitrix/php_interface/.htaccess', $deny_ht);
    file_put_contents($path . '/bitrix/updates/.htaccess', $deny_ht);

    $cache->cleanDir('xscan');

    LocalRedirect($_SERVER['REQUEST_URI']);
    die();

}


if ($cache->initCache(600, 'xscan_htaccess', 'xscan')) {
    $files = $cache->getVars();
} else {
    search_htaccess($path, $files);

    $cache->startDataCache();
    $cache->endDataCache($files);
}

$list = [];
$scaner = new CBitrixXscanFork();
$bad = 0;

foreach ($files as $num => $file) {

    $stat = stat($file);
    $res = $scaner->checkFile($file);
    $bad = $res ? $res : $bad;
    $result = $res ? 'bad' : 'ok';

    $list[] = [
        'data' => [
            'ID' => $num + 1,
            'FILE_NAME' => '<a href="/bitrix/admin/bitrix.xscan_worker_fork.php?action=showfile&file=' . urlencode($file) . '&hta=Y">' . htmlspecialcharsbx($file) . '</a>',
            'FILE_SIZE' => CBitrixXscanFork::HumanSize(filesize($file)),
            'FILE_MODIFY' => ConvertTimeStamp($stat['mtime'], "FULL"),
            'FILE_CREATE' => ConvertTimeStamp($stat['ctime'], "FULL"),
            'STATUS' => $result
        ]
    ];
}

if ($bad){
    echo CBitrixXscanFork::ShowMsg(GetMessage("BITRIX_XSCAN_HT_ALERT"), 'red');
}
else{
    echo CBitrixXscanFork::ShowMsg(GetMessage("BITRIX_XSCAN_HT_OK"), 'green');

}

$nav->setRecordCount(count($files));
$list = array_slice($list, $nav->getOffset(), $nav->getlimit());

$APPLICATION->IncludeComponent(
    'bitrix:main.ui.grid',
    '',
    [
        'GRID_ID' => 'xscan_htaccess',
        'COLUMNS' => [
            ['id' => 'ID', 'name' => '#', 'default' => true],
            ['id' => 'FILE_NAME', 'name' => GetMessage("BITRIX_XSCAN_NAME"), 'default' => true],
            ['id' => 'FILE_SIZE', 'name' => GetMessage("BITRIX_XSCAN_SIZE"), 'default' => true],
            ['id' => 'FILE_MODIFY', 'name' => GetMessage("BITRIX_XSCAN_M_DATE"), 'default' => true],
            ['id' => 'FILE_CREATE', 'name' => GetMessage("BITRIX_XSCAN_C_DATE"), 'default' => true],
            ['id' => 'STATUS', 'name' => GetMessage("BITRIX_XSCAN_STATUS"), 'default' => true],

        ],
        'ROWS' => $list,
        'TOTAL_ROWS_COUNT' => count($files),
        'SHOW_ROW_CHECKBOXES' => false,
        'SHOW_GRID_SETTINGS_MENU' => true,
        'SHOW_TOTAL_COUNTER' => true,
        'ALLOW_COLUMNS_RESIZE' => true,
        'ALLOW_HORIZONTAL_SCROLL' => true,
        'ALLOW_SORT' => true,

        'AJAX_MODE' => 'Y',
        'AJAX_ID' => \CAjax::GetComponentID('bitrix:main.ui.grid', '', ''),
        'AJAX_OPTION_JUMP' => 'N',
        'AJAX_OPTION_STYLE' => 'N',
        'AJAX_OPTION_HISTORY' => 'N',

        'NAV_OBJECT' => $nav,
        'CURRENT_PAGE' => $nav->getCurrentPage(),
        'NAV_PARAM_NAME' => $nav->getId(),
        'SHOW_NAVIGATION_PANEL' => true,
        'SHOW_PAGINATION' => true,
        'SHOW_MORE_BUTTON' => false,
        'ENABLE_NEXT_PAGE' => true,

        'SHOW_PAGESIZE' => true,
        'DEFAULT_PAGE_SIZE' => 20,
        'PAGE_SIZES' => [
            ['NAME' => "5", 'VALUE' => '5'],
            ['NAME' => '10', 'VALUE' => '10'],
            ['NAME' => '20', 'VALUE' => '20'],
            ['NAME' => '50', 'VALUE' => '50'],
            ['NAME' => '100', 'VALUE' => '100']
        ],

    ]
);

?>

    <script>

        BX.SidePanel.Instance.bindAnchors({
            rules:
                [
                    {
                        condition: [
                            ".*action=showfile&file=.*",
                        ],
                        loader: "xscan",

                        options: {
                            animationDuration: 1,
                            cacheable: false
                        }
                    }
                ]
        });


    </script>

<?php


require($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/include/epilog_admin_before.php");
require($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/include/epilog_admin_after.php");
?>