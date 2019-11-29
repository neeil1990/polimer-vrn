<?
if (!check_bitrix_sessid())
    return;

IncludeModuleLangFile(__FILE__);
$moduleId = 'sotbit.seometa';
?>

<form id="seometa_form" action="<?echo $APPLICATION->GetCurPage(); ?>">
    <?echo bitrix_sessid_post(); ?>
    <input type="hidden" name="lang" value="<?echo LANG ?>">
    <input type="hidden" name="unstep" value="3">
    <input type="hidden" name="id" value="<?=$moduleId?>">
    <input type="hidden" name="uninstall" value="Y">
    <div class="data-collection-wrapper">
        <div class="wizard-input-form-block">
            <label for="dataCollectionSave" class="wizard-input-title"><?=GetMessage($moduleId."_unstep_checkbox")?></label>
            <input type="checkbox" name="save"  id="dataCollectionSave">
            <br>
            <div class="adm-info-message-wrap">
                <div class="adm-info-message">
                    <?=GetMessage($moduleId."_unstep_save_msg")?>
                </div>
            </div>
        </div>
    </div>
    <br>
    <input type="button" name="" onclick="form.submit()" value="<?echo GetMessage($moduleId."_unstep_next"); ?>">
<form>
