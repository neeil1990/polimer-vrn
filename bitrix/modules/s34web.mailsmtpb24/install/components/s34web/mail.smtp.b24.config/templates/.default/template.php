<?php
/**
 * Created: 20.03.2021, 19:25
 * Author : Dmitry Antiptsev <cto@34web.ru>
 * Company: 34web Studio
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

/**
 * Bitrix vars
 *
 * @var string $componentPath
 * @var array $arResult
 */


Bitrix\Main\UI\Extension::load(['ui.buttons', 'ui.hint']);

$messages = Loc::loadLanguageFile(__FILE__);

?>
<div class="smtpmail mail-config-slider">
        <input type="hidden" id="mailConfigMailID" value="<?
        echo (!empty($arResult['MAILBOX']['ID'])) ? $arResult['MAILBOX']['ID'] : '' ?>">
        <input type="hidden" id="mailConfigSMTPMailID" value="<?
    echo (!empty($arResult['SMTP']['ID'])) ? $arResult['SMTP']['ID'] : '' ?>">
        <div class="smtpmail-section-block">
            <div class="smtpmail-title-block"><?= Loc::getMessage('MAIL_SMTP_B24_TITLE_SETTINGS') ?></div>
        </div>
        <div class="smtpmail-section-block">
            <div class="smtpmail-config-mailbox-block">
                <div class="smtpmail-config-mailbox-name"><?
                    if (!empty($arResult['MAILBOX']['NAME']) &&
                        $arResult['MAILBOX']['NAME'] != $arResult['MAILBOX']['EMAIL']) {
                        echo $arResult['MAILBOX']['NAME'] . ' <' . $arResult['MAILBOX']['EMAIL'] . '>';
                    } else {
                        echo $arResult['MAILBOX']['EMAIL'];
                    }
                    ?></div>
            </div>
        </div>
        <div class="smtpmail-section-block">
            <div class="smtpmail-config-form-item">
                <label class="smtpmail-config-form-label" for="mailConfigSMTPAddress"><?=
                    Loc::getMessage('MAIL_SMTP_B24_CONFIG_SMTP_ADDRESS') ?></label>
                <input class="smtpmail-config-form-input" type="text" placeholder="smtp.example.com"
                       id="mailConfigSMTPAddress" value="<?
                echo (!empty($arResult['SMTP']['SERVER'])) ? $arResult['SMTP']['SERVER'] : '' ?>">
                <div class="smtpmail-config-form-error"><?
                    echo (!empty($arResult['FIELDS_ERRORS']['SERVER'])) ? $arResult['FIELDS_ERRORS']['SERVER'] : '' ?></div>
            </div>
            <div class="smtpmail-config-form-item">
                <label class="smtpmail-config-form-label" for="mailConfigSMTPPort"><?=
                    Loc::getMessage('MAIL_SMTP_B24_CONFIG_SMTP_PORT') ?></label>
                <div class="smtpmail-config-form-item-inner">
                    <input class="smtpmail-config-form-input" type="text" placeholder="25"
                           id="mailConfigSMTPPort" value="<?
                    echo (!empty($arResult['SMTP']['PORT'])) ? $arResult['SMTP']['PORT'] : '' ?>">
                    <div class="smtpmail-config-option-email">
                        <input class="smtpmail-config-form-input smtpmail-config-form-input-check" type="checkbox"
                               id="mailConfigSecure" value="Y" <?
                        echo (!empty($arResult['SMTP']['SECURE']) && ($arResult['SMTP']['SECURE'] == 'Y' ||
                                $arResult['SMTP']['SECURE'] == 'S')) ?
                            'checked="checked"' : '' ?>">
                        <label class="smtpmail-config-form-label smtpmail-config-form-label-check"
                               for="mailConfigSecure"><?=
                            Loc::getMessage('MAIL_SMTP_B24_CONFIG_SMTP_USE_SSL') ?></label>
                    </div>
                </div>
                <div class="smtpmail-config-form-error"><?
                    echo (!empty($arResult['FIELDS_ERRORS']['PORT'])) ? $arResult['FIELDS_ERRORS']['PORT'] : '' ?></div>
            </div>
            <div class="smtpmail-config-form-item">
                <label class="smtpmail-config-form-label" for="mailConfigAuthType"><?=
                    Loc::getMessage('MAIL_SMTP_B24_CONFIG_SMTP_AUTH_TYPE') ?></label>
                <select id="mailConfigAuthType" class="smtpmail-config-form-select">
                    <option value="G"<? echo (!empty($arResult['SMTP']['AUTH']) && $arResult['SMTP']['AUTH'] == 'G') ?
                        ' selected="selected"' : '' ?>><?=
                        Loc::getMessage('MAIL_SMTP_B24_CONFIG_SMTP_AUTH_TYPE_IMAP') ?></option>
                    <option value="N"<? echo (!empty($arResult['SMTP']['AUTH']) && $arResult['SMTP']['AUTH'] == 'N') ?
                        ' selected="selected"' : '' ?>><?=
                        Loc::getMessage('MAIL_SMTP_B24_CONFIG_SMTP_AUTH_TYPE_NONE') ?></option>
                    <option value="C"<? echo (!empty($arResult['SMTP']['AUTH']) && $arResult['SMTP']['AUTH'] == 'C') ?
                        ' selected="selected"' : '' ?>><?=
                        Loc::getMessage('MAIL_SMTP_B24_CONFIG_SMTP_AUTH_TYPE_USER') ?></option>
                </select>
            </div>
            <div class="smtpmail-advanced-items<?echo (!empty($arResult['SMTP']['AUTH']) &&
                $arResult['SMTP']['AUTH'] == 'C') ? ' show': ''?>" id="mailConfigAdvancedBlock">
                <div class="smtpmail-advanced-notice-block">
                    <div class="smtpmail-advanced-notice-text">
                        <span><?= Loc::getMessage('MAIL_SMTP_B24_CONFIG_SMTP_ADVANCED_TEXT') ?></span>
                    </div>
                </div>
                <div class="smtpmail-config-form-item">
                    <label class="smtpmail-config-form-label" for="mailConfigLogin"><?=
                        Loc::getMessage('MAIL_SMTP_B24_CONFIG_SMTP_LOGIN') ?></label>
                    <input class="smtpmail-config-form-input" type="text" placeholder=""
                           id="mailConfigLogin" value="<?
                    echo (!empty($arResult['SMTP']['LOGIN'])) ? $arResult['SMTP']['LOGIN'] : '' ?>">
                    <div class="smtpmail-config-form-error"><?
                        echo (!empty($arResult['FIELDS_ERRORS']['LOGIN'])) ? $arResult['FIELDS_ERRORS']['LOGIN'] : '' ?></div>
                </div>
                <div class="smtpmail-config-form-item">
                    <label class="smtpmail-config-form-label" for="mailConfigPassword"><?=
                        Loc::getMessage('MAIL_SMTP_B24_CONFIG_SMTP_PASSWORD') ?></label>
                    <input class="smtpmail-config-form-input" type="password" placeholder=""
                           id="mailConfigPassword" autocomplete="off" value="<?
                    echo (!empty($arResult['SMTP']['PASSWORD'])) ? '************' : '' ?>">
                    <div class="smtpmail-config-form-error"><?
                        echo (!empty($arResult['FIELDS_ERRORS']['PASSWORD'])) ? $arResult['FIELDS_ERRORS']['PASSWORD'] : '' ?></div>
                </div>
            </div>
        </div>
        <div class="smtpmail-section-block-last"></div>

        <div class="smtpmail-config-footer smtpmail-config-footer-fixed">
            <div class="smtpmail-config-form-error-global" id="mailConfigErrors"><?
                if (!empty($arResult['ERRORS'])) {
                    $errors = implode('\r\n', $arResult['ERRORS']);
                    echo $errors;
                }
                ?></div>
            <div class="smtpmail-config-footer-container">
                <button class="ui-btn ui-btn-md ui-btn-success ui-btn-success smtpmail-config-btn-connect"
                        id="mailConfigSaveBtn"><?= Loc::getMessage('MAIL_SMTP_B24_CONFIG_SMTP_BTN_SAVE') ?></button>
                <? if(!empty($arResult['SMTP']['ID'])):?>
                <button class="ui-btn ui-btn-md ui-btn ui-btn-danger mail-connect-btn-disconnect"
                        id="mailConfigDisconnectBtn"><?= Loc::getMessage('MAIL_SMTP_B24_CONFIG_SMTP_BTN_DISCONNECT') ?></button>
                <?endif?>
                <button class="ui-btn ui-btn-md ui-btn-link smtpmail-config-btn-cancel"
                        id="mailConfigCancelBtn"><?= Loc::getMessage('MAIL_SMTP_B24_CONFIG_SMTP_BTN_CANCEL') ?></button>
            </div>
        </div>
</div>
<script>
    BX.message(<?=CUtil::PhpToJSObject($messages)?>);
    BX.ready(BX.mailSMTPB24.mailConfig.init({
        params: <?=CUtil::PhpToJSObject([
            'componentPath' => $componentPath
        ])?>
    }));
</script>