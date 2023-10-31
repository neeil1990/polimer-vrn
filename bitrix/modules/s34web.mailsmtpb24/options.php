<?php
/**
 * Created: 11.03.2021, 17:32
 * Author : Dmitry Antiptsev <cto@34web.ru>
 * Company: 34web Studio
 */

/** @global CMain $APPLICATION */

use Bitrix\Main\Localization\Loc,
    Bitrix\Main\Loader,
    Bitrix\Main\Application,
    Bitrix\Main\Web\Uri,
    Bitrix\Main\Config\Option;

// module variables
$moduleID = basename(dirname(__FILE__));
$module_id = $moduleID;
$moduleCode = strtoupper(str_replace('.', '_', $moduleID));
$moduleFullPath = str_replace("\\", "/", dirname(__FILE__));

// module admin pages
$moduleManagementAccounts = strtolower(str_replace('.', '_', $moduleID)) . '_accounts.php';
$moduleMailLogs = strtolower(str_replace('.', '_', $moduleID)) . '_logs.php';

// load lang files
Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/options.php');
Loc::loadMessages(__FILE__);

$flagOptions = true;

// check include module
$moduleStatus = Loader::includeSharewareModule($moduleID);
if ($moduleStatus == Loader::MODULE_NOT_FOUND) {
    CAdminMessage::ShowMessage([
        'TYPE' => 'ERROR',
        'MESSAGE' => Loc::getMessage($moduleCode . '_MODULE_NOT_FOUND_TITLE'),
        'DETAILS' => Loc::getMessage($moduleCode . '_MODULE_NOT_FOUND_TEXT'),
        'HTML' => true
    ]);
    $flagOptions = false;
}
if ($moduleStatus == Loader::MODULE_DEMO_EXPIRED) {
    CAdminMessage::ShowMessage([
        'TYPE' => 'ERROR',
        'MESSAGE' => Loc::getMessage($moduleCode . '_MODULE_DEMO_EXPIRED_TITLE'),
        'DETAILS' => Loc::getMessage($moduleCode . '_MODULE_DEMO_EXPIRED_TEXT'),
        'HTML' => true
    ]);
    $flagOptions = false;
}
if ($moduleStatus == Loader::MODULE_DEMO) {
    CAdminMessage::ShowMessage([
        'TYPE' => 'OK',
        'MESSAGE' => Loc::getMessage($moduleCode . '_MODULE_DEMO_TITLE'),
        'DETAILS' => Loc::getMessage($moduleCode . '_MODULE_DEMO_TEXT'),
        'HTML' => true
    ]);
}
if ($flagOptions) {
    // check module rights
    $MODULE_RIGHT = $APPLICATION->GetGroupRight($moduleID);
    if ($MODULE_RIGHT == 'D') {
        $APPLICATION->AuthForm(Loc::getMessage($moduleCode . '_ACCESS_DENIED'));
    }
    // get sites
    $siteDirs = [];
    $sites = [Loc::getMessage($moduleCode . '_OPTION_' .
        strtoupper('site_id') . '_VALUE_NAME_NONE') => 'none'];
    $resSites = Bitrix\Main\SiteTable::getList([
        'order' => ['SORT' => 'asc'],
        'filter' => ['ACTIVE' => 'Y'],
        'select' => ['LID', 'NAME', 'DIR']
    ]);
    while ($arSite = $resSites->fetch()) {
        $sites[$arSite['NAME'] . ' [ ' . $arSite['LID'] . ' - "' . $arSite['DIR'] . '" ]'] = $arSite['LID'];
        $siteDirs[$arSite['LID']] = $arSite['DIR'];
    }
    // get module options
    $arOptions = [
        'main_settings' =>
            [
                'name' => Loc::getMessage($moduleCode . '_OPTIONS_HEADER_' . strtoupper('main_settings')),
                'options' => [
                    'active_module' => [
                        'name' => Loc::getMessage($moduleCode . '_OPTION_' . strtoupper('active_module')),
                        'type' => 'checkbox',
                        'hint' => true,
                        'default' => 'N',
                        'dataType' => 'bool'
                    ],
                    'site_id' => [
                        'name' => Loc::getMessage($moduleCode . '_OPTION_' . strtoupper('site_id')),
                        'type' => 'select',
                        'hint' => true,
                        'default' => 'none',
                        'dataType' => 'string',
                        'values' => $sites
                    ],
                    'log_sending' => [
                        'name' => Loc::getMessage($moduleCode . '_OPTION_' . strtoupper('log_sending')),
                        'type' => 'checkbox',
                        'hint' => true,
                        'default' => 'N',
                        'dataType' => 'bool',
                    ],
                    'log_level' => [
                        'name' => Loc::getMessage($moduleCode . '_OPTION_' . strtoupper('log_level')),
                        'type' => 'select',
                        'hint' => true,
                        'default' => '2',
                        'dataType' => 'int',
                        'values' => [
                            Loc::getMessage($moduleCode . '_OPTION_' .
                                strtoupper('log_level') . '_VALUE_NAME_1') => '1',
                            Loc::getMessage($moduleCode . '_OPTION_' .
                                strtoupper('log_level') . '_VALUE_NAME_2') => '2',
                            Loc::getMessage($moduleCode . '_OPTION_' .
                                strtoupper('log_level') . '_VALUE_NAME_3') => '3',
                            Loc::getMessage($moduleCode . '_OPTION_' .
                                strtoupper('log_level') . '_VALUE_NAME_4') => '4'
                        ]
                    ],
                    'log_rewrite' => [
                        'name' => Loc::getMessage($moduleCode . '_OPTION_' . strtoupper('log_rewrite')),
                        'type' => 'select',
                        'hint' => true,
                        'default' => '1',
                        'dataType' => 'int',
                        'values' => [
                            Loc::getMessage($moduleCode . '_OPTION_' .
                                strtoupper('log_rewrite') . '_VALUE_NAME_1') => '1',
                            Loc::getMessage($moduleCode . '_OPTION_' .
                                strtoupper('log_rewrite') . '_VALUE_NAME_2') => '2',
                            Loc::getMessage($moduleCode . '_OPTION_' .
                                strtoupper('log_rewrite') . '_VALUE_NAME_3') => '3',
                            Loc::getMessage($moduleCode . '_OPTION_' .
                                strtoupper('log_rewrite') . '_VALUE_NAME_4') => '4'
                        ]
                    ],
                    'log_weight' => [
                        'name' => Loc::getMessage($moduleCode . '_OPTION_' . strtoupper('log_weight')),
                        'type' => 'text',
                        'hint' => true,
                        'default' => '10',
                        'dataType' => 'int'
                    ]
                ]
            ],
        'send_settings' =>
            [
                'name' => Loc::getMessage($moduleCode . '_OPTIONS_HEADER_' . strtoupper('send_settings')),
                'options' => [
                    'smtp_timeout' => [
                        'name' => Loc::getMessage($moduleCode . '_OPTION_' . strtoupper('smtp_timeout')),
                        'type' => 'text',
                        'default' => '10',
                        'dataType' => 'int'
                    ],
                    'selection_priority' => [
                        'name' => Loc::getMessage($moduleCode . '_OPTION_' .
                            strtoupper('selection_priority')),
                        'type' => 'select',
                        'hint' => true,
                        'default' => '1',
                        'dataType' => 'int',
                        'values' => [
                            Loc::getMessage($moduleCode . '_OPTION_' .
                                strtoupper('selection_priority') . '_VALUE_NAME_1') => '1',
                            Loc::getMessage($moduleCode . '_OPTION_' .
                                strtoupper('selection_priority') . '_VALUE_NAME_2') => '2'
                        ]
                    ],
                    'extended_check_connect' => [
                        'name' => Loc::getMessage($moduleCode . '_OPTION_' .
                            strtoupper('extended_check_connect')),
                        'type' => 'checkbox',
                        'hint' => true,
                        'default' => 'N',
                        'dataType' => 'bool',
                    ],
                ]
            ]
    ];
    // set interface tabs
    $aTabs = [
        [
            'DIV' => 'props',
            'TAB' => Loc::getMessage($moduleCode . '_PROPS'),
            'TITLE' => Loc::getMessage($moduleCode . '_PROPS_TITLE')
        ],
    ];
    if (Bitrix\Main\IO\File::isFileExists($_SERVER["DOCUMENT_ROOT"] .
        '/bitrix/modules/main/admin/group_rights.php')) {
        $aTabs[] = [
            'DIV' => 'rights',
            'TAB' => Loc::getMessage($moduleCode . '_RIGHTS'),
            'ICON' => 'perfmon_settings',
            'TITLE' => Loc::getMessage($moduleCode . '_RIGHTS_TITLE')
        ];
    }
    $tabControl = new CAdminTabControl('tabControl', $aTabs);
    // get request for construct redirect url
    $request = Application::getInstance()->getContext()->getRequest();
    $uriString = $request->getRequestedPage();
    $uri = new Uri($uriString);
    $redirect = $uri->getUri();
    $docRoot = Application::getInstance()->getContext()->getServer()->getDocumentRoot();

    global $Update;
    global $RestoreDefaults;

    // check request and set module options
    if ($request->isPost() && strlen($Update . $RestoreDefaults) > 0 && $MODULE_RIGHT == 'W' &&
        check_bitrix_sessid()) {
        if (strlen($RestoreDefaults) > 0) {
            Option::delete($moduleID);
            $by = 'id';
            $order = 'asc';
            $groupRightsList = CGroup::GetList($by, $order,
                ["ACTIVE" => "Y", "ADMIN" => "N"]);
            while ($groupRight = $groupRightsList->Fetch()) {
                $APPLICATION->DelGroupRight($moduleID, [$groupRight["ID"]]);
            }
        } else {
            // set options after save
            foreach ($arOptions as $groupOptions) {
                foreach ($groupOptions['options'] as $optionKey => $optionValue) {
                    $checkOptionValue = $request->getPost($optionKey);
                    if ($optionValue['dataType'] == 'int') {
                        if (!is_numeric($checkOptionValue)) {
                            $checkOptionValue = $optionValue['default'];
                        }
                    } else if ($optionValue['dataType'] == 'bool') {
                        if (!in_array($checkOptionValue, ['Y', 'N'])) {
                            $checkOptionValue = $optionValue['default'];
                        }
                    } else {
                        if (empty($checkOptionValue)) {
                            $checkOptionValue = $optionValue['default'];
                        }
                    }

                    if($optionKey == 'site_id')
                    {
                        if($checkOptionValue == 'none')
                        {
                            Option::set($moduleID, $optionKey, '');
                            Option::set($moduleID, 'site_dir', '');
                        }
                        else
                        {
                            Option::set($moduleID, $optionKey, $checkOptionValue);
                            if(!empty($siteDirs[$checkOptionValue])) {
                                Option::set($moduleID, 'site_dir', $siteDirs[$checkOptionValue]);
                            }
                        }
                    }
                    else {
                        Option::set($moduleID, $optionKey, $checkOptionValue);
                    }
                }
            }
        }
    }
    // construct tabs
    $tabControl->Begin();
    $activeModule = Option::get($moduleID, 'active_module', 'N');
    ?>
    <form method="POST" action="<?= $redirect . '?lang=' .
    urlencode(LANGUAGE_ID). '&mid=' . urlencode($moduleID) . '&mid_menu=1'?>" name="SETTINGS">
        <?= bitrix_sessid_post(); ?>
        <?php $tabControl->BeginNextTab();
        $countHints = 0;
        ?>
        <?php foreach ($arOptions as $groupOptions) {
            ?>
            <tr class="heading">
                <td colspan="2"><b><?= $groupOptions['name'] ?></b></td>
            </tr>
            <?php
            foreach ($groupOptions['options'] as $optionKey => $optionValue) {
                if (isset($optionValue['hint']) && $optionValue['hint'] === true) {
                    $countHints++;
                }
                $getOptionValue = Option::get($moduleID, $optionKey, $optionValue['default']);
                ?>
                <tr>
                    <td style="width: 40%;">
                        <label for="<?= $optionKey ?>"><?= $optionValue['name'] ?><?php
                            if (isset($optionValue['hint']) &&
                                $optionValue['hint'] === true):?> <span class="required"><sup><?=
                                    $countHints ?></sup></span> <?php endif ?>:</label>
                    </td>
                    <td style="width: 60%;">
                        <?php
                        if ($optionValue['type'] == "checkbox") {
                            echo InputType(
                                'checkbox',
                                $optionKey,
                                'Y',
                                $getOptionValue,
                                false,
                                '',
                                '',
                                $optionKey
                            );
                        } else if ($optionValue['type'] == "text") {
                            echo InputType(
                                'text',
                                $optionKey,
                                $getOptionValue,
                                '',
                                false,
                                '',
                                '',
                                $optionKey
                            );
                        } else if ($optionValue['type'] == "select") {
                            echo SelectBoxFromArray(
                                $optionKey,
                                [
                                    'REFERENCE' => array_keys($optionValue['values']),
                                    'REFERENCE_ID' => array_values($optionValue['values'])
                                ],
                                $getOptionValue,
                                ''
                            );
                        }
                        ?>
                    </td>
                </tr>
                <?php
            }
            unset($option);
        }
        unset($groupOptions, $optionGroup);

        if ($activeModule == 'Y') {
            ?>
            <tr class="heading">
                <td colspan="2"><b><?= Loc::getMessage($moduleCode . '_MANAGEMENT_AND_DEBUGGING') ?></b></td>
            </tr>
            <tr>
                <td colspan="2" style="text-align: center;">
                    <input type="button" onclick="window.location.href='/bitrix/admin/<?= $moduleManagementAccounts
                    ?>?lang=<?= urlencode(LANGUAGE_ID) ?>'" value="<?=
                    Loc::getMessage($moduleCode . '_MANAGEMENT_SMTP_ACCOUNTS_BTN') ?>">
                    <br/><br/>
                    <input type="button" onclick="window.location.href='/bitrix/admin/<?= $moduleMailLogs
                    ?>?lang=<?= urlencode(LANGUAGE_ID) ?>'" value="<?=
                    Loc::getMessage($moduleCode . '_MAIL_SENDING_LOGS_BTN') ?>">
                </td>
            </tr>
            <?php
        }
        // next tab (include default rights)
        if (Bitrix\Main\IO\File::isFileExists($_SERVER["DOCUMENT_ROOT"] .
            '/bitrix/modules/main/admin/group_rights.php')) {
            $tabControl->BeginNextTab();
            require_once($_SERVER["DOCUMENT_ROOT"] . '/bitrix/modules/main/admin/group_rights.php');
        }

        // buttons tab
        $tabControl->Buttons(); ?>
        <input type="hidden" name="stepTabControl_active_tab"
               value="<?= htmlspecialcharsbx($request->getPost('stepTabControl_active_tab')) ?>">
        <input type="submit" name="Update" value="<?= Loc::getMessage($moduleCode . '_UPDATE_BTN')
        ?>" class="adm-btn-save">
        <input type="reset" name="reset" value="<?= Loc::getMessage($moduleCode . '_RESET_BTN')
        ?>">
        <input type="submit" name="RestoreDefaults" title="<?= Loc::getMessage($moduleCode .
            '_RESTORE_DEFAULTS') ?>"
               onclick="return confirm('<?= AddSlashes(Loc::getMessage($moduleCode .
                   '_RESTORE_DEFAULTS_WARNING')) ?>')" value="<?= Loc::getMessage($moduleCode .
            '_RESTORE_DEFAULTS') ?>">
        <?php
        // end tab constructor
        $tabControl->End();
        ?>
    </form>
    <?= BeginNote(); ?>
    <div style="width: 100%;">
        <p>
            <span class="required"><sup>1</sup></span>
            <?= Loc::getMessage($moduleCode . '_OPTION_ACTIVE_MODULE_HINT') ?>
        </p>
        <p>
            <span class="required"><sup>2</sup></span>
            <?= Loc::getMessage($moduleCode . '_OPTION_SITE_ID_HINT') ?>
        </p>
        <p>
            <span class="required"><sup>3</sup></span>
            <?= Loc::getMessage($moduleCode . '_OPTION_LOG_SENDING_HINT') ?>
        </p>
        <p>
            <span class="required"><sup>4</sup></span>
            <?= Loc::getMessage($moduleCode . '_OPTION_LOG_LEVEL_HINT') ?>
        </p>
        <p>
            <span class="required"><sup>5</sup></span>
            <?= Loc::getMessage($moduleCode . '_OPTION_LOG_REWRITE_HINT') ?>
        </p>
        <p>
            <span class="required"><sup>6</sup></span>
            <?= Loc::getMessage($moduleCode . '_OPTION_LOG_WEIGHT_HINT') ?>
        </p>
        <p>
            <span class="required"><sup>7</sup></span>
            <?= Loc::getMessage($moduleCode . '_OPTION_SELECTION_PRIORITY_HINT') ?>
        </p>
        <p>
            <span class="required"><sup>8</sup></span>
            <?= Loc::getMessage($moduleCode . '_OPTION_EXTENDED_CHECK_CONNECT_HINT') ?>
        </p>
    </div>
    <?= EndNote(); ?>
    <?php
}
?>
