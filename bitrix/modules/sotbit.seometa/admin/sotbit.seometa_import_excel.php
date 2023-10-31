<?

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\FileInput;
use Sotbit\Seometa\Helper\SitemapRuntime;
use Sotbit\Seometa\Helper\ImportExport\ImportHelper;

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");

Loc::loadMessages(__FILE__);

global $APPLICATION;

$id_module = 'sotbit.seometa';
CJSCore::Init(array("jquery"));
if (!Loader::includeModule('iblock') || !Loader::includeModule($id_module)) {
    die();
}

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

$POST_RIGHT = $APPLICATION->GetGroupRight($id_module);
if ($POST_RIGHT == "D") {
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();

$entity = $request->get('entity');
$APPLICATION->SetTitle($entity === 'cond' ? Loc::getMessage($id_module . "_COND_TITLE") : Loc::getMessage($id_module . "_CHPU_TITLE"));
$fileFromGetReq = $request->get('file');

if ($request->isPost() && $request->get('apply') && $POST_RIGHT == "W" && check_bitrix_sessid()) {

    if ($fileFromGetReq && $request->get('ELEMENT_FILE_UPLOAD_del') === 'Y') {
        CFile::Delete($fileFromGetReq);
    } elseif (!empty($request->get('ELEMENT_FILE_UPLOAD'))) {
        if (!is_array($request->get('ELEMENT_FILE_UPLOAD')) && is_numeric($request->get('ELEMENT_FILE_UPLOAD'))) {
            $fileRes = $request->get('ELEMENT_FILE_UPLOAD');
        } else {
            $file = $request->get('ELEMENT_FILE_UPLOAD');
            if (!is_array($file)) {
                $file = CFile::MakeFileArray($file);
            } else {
                $file['tmp_name'] = CTempFile::GetAbsoluteRoot() . $request->get('ELEMENT_FILE_UPLOAD')['tmp_name'];
            }

            $fileRes = CFile::SaveFile($file, 'sotbit.seometa/import');
        }
    }
    if ($fileRes != $fileFromGetReq) {
        CFile::Delete($fileFromGetReq);
    }
    if ($fileRes && $fileRes != $fileFromGetReq) {
        LocalRedirect("/bitrix/admin/sotbit.seometa_import_excel.php?lang=" . LANG . '&file=' . $fileRes . ($entity === 'cond' ? '&entity=' . $entity : ''));
    }
}

if ($fileFromGetReq) {
    list($arColumn, $totalCount) = ImportHelper::getColumn($fileFromGetReq);
    $redirectUrl = '/bitrix/admin/sotbit.seometa_parse_result.php?file=' . $fileFromGetReq;
    $redirectUrl .= $entity === 'cond' ? '&entity=cond' : '';
}

$aTabs = [
    [
        "DIV" => "edit1",
        "TAB" => Loc::getMessage($id_module . '_IMPORT_EXCEL_TITLE'),
        "ICON" => "main_user_edit",
        "TITLE" => Loc::getMessage($id_module . '_IMPORT_EXCEL_TITLE')
    ],
];

if($entity === 'cond'){
    $aMenu[] = [
        "TEXT" => Loc::GetMessage($id_module . '_COND_HEADER_TITLE'),
        "TITLE" => Loc::GetMessage($id_module . '_COND_HEADER_TITLE'),
        "LINK"=>"javascript:exportExampCond();",
        "ICON" => "btn_download",
    ];
}else{
    $aMenu[] = [
        "TEXT" => Loc::GetMessage($id_module . '_CHPU_HEADER_TITLE'),
        "TITLE" => Loc::GetMessage($id_module . '_CHPU_HEADER_TITLE'),
        "LINK"=>"javascript:exportExampCHPU();",
        "ICON" => "btn_download",
    ];
}


$context = new CAdminContextMenu($aMenu);
$context->Show();

$tabControl = new CAdminForm("tabControl", $aTabs);

$tabControl->Begin([
    "FORM_ACTION" => $APPLICATION->GetCurPageParam()
]);

$tabControl->BeginNextFormTab();
$tabControl->BeginCustomField("ELEMENT_FILE_UPLOAD", Loc::getMessage($id_module . '_EXCEL_FILE'));
?>
    <tr class="heading">
        <td colspan="2"><?= Loc::getMessage($id_module . '_MAIN_IMPORT_SETTINGS') ?></td>
    </tr>
    <tr>
        <td colspan="2">
            <div style="text-align: center" class="adm-info-message-wrap">
                <div class="adm-info-message">
                    <?= Loc::getMessage($id_module . '_WARNING') ?>
                </div>
            </div>
        </td>
    </tr>
    <tr class="adm-detail-file-row">
        <td><?= $tabControl->GetCustomLabelHTML(); ?></td>
        <td><?= FileInput::createInstance([
                "name" => "ELEMENT_FILE_UPLOAD",
                "description" => true,
                "upload" => true,
                "allowUpload" => "A",
                "allowUploadExt" => 'xlsx, xls',
                "fileDialog" => true,
                "cloud" => true,
                "delete" => true,
                "maxCount" => 1
            ])->show($fileFromGetReq ?: $request->get('ELEMENT_FILE_UPLOAD')) ?>
        </td>
    </tr>
<? $tabControl->EndCustomField("ELEMENT_FILE_UPLOAD");
if (!$arColumn && $fileFromGetReq) {
    CAdminMessage::ShowMessage([
        "MESSAGE" => Loc::getMessage($id_module . '_EMPTY_EXCEL_FILE'),
    ]);
}

$dataRow = $request->get('DATA_ROW');

if ($dataRow && (!is_numeric($dataRow) || intval($dataRow) != $dataRow)) {
    CAdminMessage::ShowMessage([
        "MESSAGE" => Loc::getMessage($id_module . '_ERROR_DATA_ROW'),
    ]);
    $dataRow = 3;
}
$tabControl->BeginCustomField("HID", '');
echo bitrix_sessid_post();
?>
    <input type="hidden" name="lang" value="<?= LANG ?>">
<? if ($arColumn) { ?>
    <tr class="heading">
        <td colspan="2"><?= Loc::getMessage($id_module . '_ADDITIONAL_IMPORT_SETTINGS') ?></td>
    </tr>
    <tr style="display: none; text-align: center" class="warning-for-req-fields">
        <td colspan="2">
            <?
            CAdminMessage::ShowMessage([
                "MESSAGE" => Loc::getMessage($id_module . '_WARNING_EMPTY_REQ_FIELDS'),
            ]);
            ?>
        </td>
    </tr>
<? } ?>
<?
$tabControl->EndCustomField("HID");
if ($arColumn) {
    if ($entity === 'cond') {
        $tabControl->AddCheckBoxField("GENERATE_CHPU",
            Loc::GetMessage($id_module . '_GENERATE_CHPU'),
            false,
            'Y',
            (bool)$request->get('GENERATE_CHPU')
        );
    }

    $tabControl->AddEditField("DATA_ROW",
        Loc::GetMessage($id_module . '_DATA_ROW'),
        false,
        [
            "size" => 50,
            "maxlength" => 255
        ],
        $dataRow ?: 3
    );

    $tabControl->BeginCustomField('CATEGORY_ID', Loc::getMessage($id_module . '_CATEGORY_ID'));
    $catValues = ImportHelper::categoryForSelect($entity);
    ?>
    <tr id="CATEGORY_ID">
        <td width="40%"><?= $tabControl->GetCustomLabelHTML() ?></td>
        <td width="60%">
            <?= SelectBoxFromArray('CATEGORY_ID',
                $catValues,
                $request->get('CATEGORY_ID') !== null ? $request->get('CATEGORY_ID') : 0,
                '',
                'style="width: 350px"',
                false,
                'tabControl_form');
            ?>
        </td>
    </tr>
    <?
    $tabControl->EndCustomField('CATEGORY_ID');

    $arrFields = $entity === 'cond' ? ImportHelper::getConditionFields() : ImportHelper::getCHPUFields();
    $selectValue = ImportHelper::columnForSelect($arColumn);
    $chooseValue = array_flip(array_combine($selectValue['REFERENCE_ID'], $selectValue['REFERENCE']));
    foreach ($arrFields as $key => $field) {
        if ($entity) {
            $require = $field === 'NAME' || $field === 'SORT' || $field === 'SITES';
        } else {
            $require = $field === 'NAME' || $field === 'REAL_URL';
        }
        $tabControl->BeginCustomField($field, $field, $require);
        ?>
        <tr id="<?= $field ?>">
            <td width="40%"><?= $tabControl->GetCustomLabelHTML() ?></td>
            <td width="60%">
                <?= SelectBoxFromArray($field,
                    $selectValue,
                    $request->get($field) !== null ? $request->get($field) : $chooseValue[$field],
                    '',
                    'style="width: 350px"',
                    false,
                    'tabControl_form');
                ?>
            </td>
        </tr>
        <?
        $tabControl->EndCustomField($field);
    }
}

$additional_html = '<input type="submit" style="margin-right: 10px" class="adm-btn-apply" name="apply" value="' . Loc::getMessage("MAIN_APPLY") . '" />';

$additional_html .= $arColumn ? '<input type="submit" class="adm-btn-apply" name="import" value="' . Loc::getMessage($id_module . "_IMPORT") . '" />' : '';

$tabControl->Buttons(false, $additional_html);
$tabControl->Show(); ?>
    <script>
        const tabControl_form = document.querySelector("#tabControl_form");
        const crossDisable = document.querySelector('.adm-fileinput-item-panel-btn.adm-btn-del');
        <?php if($request->get('file')){ ?>
        const file = <?= $request->get('file') ?>;
        <?php } ?>
        const btnExample = document.querySelector('.adm-detail-toolbar-right');
        btnExample.style.float = 'left';
        btnExample.style.textAlign = 'left';

        const importButton = document.querySelector('input[name="import"]');

        if(crossDisable){
            crossDisable.addEventListener('click', function (){
                importButton.disabled = true;
            });
        }

        const selectName = document.querySelector('select[name="NAME"]')?.value;
        const selectSites = document.querySelector('select[name="SITES"]')?.value;
        const selectSort = document.querySelector('select[name="SORT"]')?.value;
        const selectRealUrl = document.querySelector('select[name="REAL_URL"]')?.value;

        if(importButton){
            if (selectName == 0 || (selectSites && selectSites == 0) || (selectSort && selectSort == 0) || (selectRealUrl && selectRealUrl == 0)) {
                importButton.disabled = true;
            } else {
                importButton.disabled = false;
            }

            if(importButton.disabled === true){
                let errorNoty = document.querySelector('.warning-for-req-fields');
                errorNoty.style.display = 'table-row';
            }
        }

        tabControl_form.addEventListener('click', function (e) {
            if (e.target.closest('input[name="import"]')) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.set('file', file);
                <?php if($entity) { ?>
                importCond(formData);
                <?php } else { ?>
                importCHPU(formData);
                <?php } ?>
            }

            <?php if($entity) { ?>
            checkForCond(selectName, selectSites, selectSort, e.target);
            <?php } else { ?>
            checkForCHPU(selectName, selectRealUrl, e.target);
            <?php } ?>
        });

        function checkForCond(selectName, selectSites, selectSort, target) {
            if (target.closest('select[name="NAME"]')) {
                if (selectName != target.value) {
                    importButton.disabled = true;
                } else if (selectName == 0 && target.value == 0) {
                    importButton.disabled = true;
                } else if (selectName == target.value) {
                    importButton.disabled = false;
                }
            }

            if (target.closest('select[name="SITES"]')) {
                if (selectSites != target.value) {
                    importButton.disabled = true;
                } else if (selectSites == 0 && target.value == 0) {
                    importButton.disabled = true;
                } else if (selectSites == target.value) {
                    importButton.disabled = false;
                }
            }

            if (target.closest('select[name="SORT"]')) {
                if (selectSort != target.value) {
                    importButton.disabled = true;
                } else if (selectSort == 0 && target.value == 0) {
                    importButton.disabled = true;
                } else if (selectSort == target.value) {
                    importButton.disabled = false;
                }
            }
        }

        function checkForCHPU(selectName, selectRealUrl, target){
            if (target.closest('select[name="NAME"]')) {
                if (selectName != target.value) {
                    importButton.disabled = true;
                } else if (selectName == 0 && target.value == 0) {
                    importButton.disabled = true;
                } else if (selectName == target.value) {
                    importButton.disabled = false;
                }
            }

            if (target.closest('select[name="REAL_URL"]')) {
                if (selectRealUrl != target.value) {
                    importButton.disabled = true;
                } else if (selectRealUrl == 0 && target.value == 0) {
                    importButton.disabled = true;
                } else if (selectRealUrl == target.value) {
                    importButton.disabled = false;
                }
            }
        }

        function exportExampCond(){
            const importCond = BX.ajax.runAction('sotbit:seometa.ExcelExportImport.exportExampCond', {
            }).then(response => {
                let link = document.createElement('a');
                link.href = response.data.PATH;
                link.download = response.data.NAME;
                link.click();
                deleteFile('seometa_condition_example');
            }, error => {
                console.error(error);
            });
        }

        function exportExampCHPU(){
            const importCond = BX.ajax.runAction('sotbit:seometa.ExcelExportImport.exportExampCHPU', {
            }).then(response => {
                let link = document.createElement('a');
                link.href = response.data.PATH;
                link.download = response.data.NAME;
                link.click();
                deleteFile('seometa_chpu_example');
            }, error => {
                console.error(error);
            });
        }

        function deleteFile(sheetName){
            const deleteFile = BX.ajax.runAction('sotbit:seometa.ExcelExportImport.deleteFile', {
                data: {sheetName}
            }).then(response => {
            }, error => {
                console.error(error);
            });
        }

        function importCond(formData, offset = 0, limit = 50, currentCount =  0, totalCount = <?= $totalCount ?? 0 ?>, firstCheck = true) {
            formData.set('offset', offset);
            formData.set('limit', limit);
            formData.set('currentCount', currentCount);
            formData.set('totalCount', totalCount);
            formData.set('firstCheck', firstCheck);
            const node = BX('seochpu_import');
            if (node.style.display === 'block') {
                const nodeProgress = BX('sitemap_progress');
                const nodeProgressStart = BX('sitemap_progress_start');
                nodeProgress.innerHTML = nodeProgressStart.innerHTML;
            } else {
                node.style.display = 'block';
            }
            const importCond = BX.ajax.runAction('sotbit:seometa.ExcelExportImport.importCondition', {
                data: formData
            }).then(response => {
                let count = response.data.COUNT;
                if (count > 0) {
                    const nodeProgressStart = BX('sitemap_progress_start');
                    nodeProgressStart.innerHTML = response.data.PROGRESSBAR;
                    this.importCond(formData, response.data.OFFSET, 50, response.data.COUNT, response.data.TOTAL_COUNT, false);
                } else {
                    const nodeProgress = BX('sitemap_progress');
                    nodeProgress.innerHTML = response.data.PROGRESSBAR;
                    if(!response.data.ERROR){
                        setTimeout(function () {
                            window.location.href = "<?= $redirectUrl ?>";
                        }, 300);
                    }
                }
            }, error => {
                console.error(error);
            });
        }

        function importCHPU(formData, offset = 0, limit = 100, currentCount = 0, totalCount = <?= $totalCount ?? 0 ?>, firstCheck = true) {
            formData.set('offset', offset);
            formData.set('limit', limit);
            formData.set('currentCount', currentCount);
            formData.set('totalCount', totalCount);
            formData.set('firstCheck', firstCheck);
            const node = BX('seochpu_import');
            if (node.style.display === 'block') {
                const nodeProgress = BX('sitemap_progress');
                const nodeProgressStart = BX('sitemap_progress_start');
                nodeProgress.innerHTML = nodeProgressStart.innerHTML;
            } else {
                node.style.display = 'block';
            }
            const importCHPU = BX.ajax.runAction('sotbit:seometa.ExcelExportImport.importCHPU', {
                data: formData
            }).then(response => {
                let count = response.data.COUNT;
                if (count > 0) {
                    const nodeProgressStart = BX('sitemap_progress_start');
                    nodeProgressStart.innerHTML = response.data.PROGRESSBAR;
                    this.importCHPU(formData, response.data.OFFSET, 100, response.data.COUNT, response.data.TOTAL_COUNT, false);
                } else {
                    const nodeProgress = BX('sitemap_progress');
                    nodeProgress.innerHTML = response.data.PROGRESSBAR;
                    if(!response.data.ERROR){
                        setTimeout(function () {
                            window.location.href = "<?= $redirectUrl ?>";
                        }, 300);
                    }
                }
            }, error => {
                console.error(error);
            });
        }
    </script>
<?php
$entityMess = $entity ? Loc::getMessage("SEO_META_COND_IMPORT_RUN_TITLE") : Loc::getMessage("SEO_META_CHPU_IMPORT_RUN_TITLE");
?>
    <div id="seochpu_import" style="display: none;">
        <div id="sitemap_progress">
            <?= SitemapRuntime::showProgress(Loc::getMessage('SEO_META_CHPU_RUN_INIT'), $entityMess, 0) ?>
        </div>
        <div id="sitemap_progress_start" style="display: none">
            <?= SitemapRuntime::showProgress(Loc::getMessage('SEO_META_CHPU_RUN_INIT'), $entityMess, 0) ?>
        </div>
    </div>
<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php"); ?>