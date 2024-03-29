/**
 * Created: 01.04.2021, 17:58
 * Author : Dmitry Antiptsev <cto@34web.ru>
 * Company: 34web Studio
 */

BX.namespace('BX.mailSMTPB24.mailConfig');

(function () {
    'use strict';

    let smtpConfigCancelBtn, smtpConfigSaveBtn, smtpConfigDisconnectBtn, smtpConfigAdvancedBlock;
    let paramsRequest, mailConfigMailID, mailConfigSMTPMailID, mailConfigSMTPAddress, mailConfigSMTPPort,
        mailConfigSecure, mailConfigAuthType, mailConfigLogin, mailConfigPassword;
    let mailConfigErrors;

    BX.mailSMTPB24.mailConfig = {
        init: function (parameters) {
            let instance = this;
            instance.params = parameters.params || {};
            instance.initDom();
            instance.setEvents();
            instance.checkFields();
            return this;
        },
        initDom: function () {
            let instance = this;
            smtpConfigCancelBtn = BX('mailConfigCancelBtn');
            smtpConfigAdvancedBlock = BX('mailConfigAdvancedBlock');
            smtpConfigSaveBtn = BX('mailConfigSaveBtn');
            smtpConfigDisconnectBtn = BX('mailConfigDisconnectBtn');

            mailConfigMailID = BX('mailConfigMailID');
            mailConfigSMTPMailID = BX('mailConfigSMTPMailID');
            mailConfigSMTPAddress = BX('mailConfigSMTPAddress');
            mailConfigSMTPPort = BX('mailConfigSMTPPort');
            mailConfigSecure = BX('mailConfigSecure');
            mailConfigAuthType = BX('mailConfigAuthType');
            mailConfigLogin = BX('mailConfigLogin');
            mailConfigPassword = BX('mailConfigPassword');

            mailConfigErrors = BX('mailConfigErrors');
        },
        setEvents: function () {
            // close form config
            if (!!smtpConfigCancelBtn) {
                BX.bind(smtpConfigCancelBtn, 'click', BX.delegate(this.closeSMTPForm, this));
            }
            // save form config
            if (!!smtpConfigSaveBtn) {
                BX.bind(smtpConfigSaveBtn, 'click', BX.delegate(this.sendSMTPForm, this));
            }
            // disconnect form config
            if(!!smtpConfigDisconnectBtn)
            {
                BX.bind(smtpConfigDisconnectBtn, 'click', BX.delegate(this.disconnectSMTPForm, this));
            }
            // open custom login & password
            if (!!mailConfigAuthType) {
                BX.bind(mailConfigAuthType, 'change', BX.delegate(this.setCustomAuth, this));
            }
        },
        setCustomAuth: function () {
            if (!!smtpConfigAdvancedBlock) {
                if (mailConfigAuthType.value == 'C') {
                    BX.addClass(smtpConfigAdvancedBlock, 'show');
                    if(!!mailConfigLogin)
                    {
                        mailConfigLogin.value = '';
                    }
                    if(!!mailConfigPassword)
                    {
                        mailConfigPassword.value = '';
                        BX.unbindAll(mailConfigPassword);
                    }
                } else {
                    BX.removeClass(smtpConfigAdvancedBlock, 'show');
                }
            }
        },
        checkFields: function () {

            let fieldsList = [
                'mailConfigSMTPAddress',
                'mailConfigSMTPPort',
                'mailConfigLogin',
                'mailConfigPassword'
            ];
            let eventsList = ['change', 'keyup'];

            // events hide errors
            for (let key in fieldsList) {
                if (!fieldsList.hasOwnProperty(key) || !BX(fieldsList[key])) continue;

                for (let keyEvent in eventsList) {
                    if (!eventsList.hasOwnProperty(keyEvent)) continue;

                    BX.bind(BX(fieldsList[key]), eventsList[keyEvent], function () {
                        let parentItem;
                        if (fieldsList[key] == 'mailConfigSMTPPort') {
                            parentItem = BX(fieldsList[key]).parentNode.parentNode;
                        } else {
                            parentItem = BX(fieldsList[key]).parentNode;
                        }
                        if (!!parentItem) {
                            BX.removeClass(parentItem, 'smtpmail-form-item-error');
                            let formItemsErrors = BX.findChild(parentItem,
                                {class: 'smtpmail-config-form-error'}, true, false);
                            if (!!formItemsErrors) {
                                BX.adjust(formItemsErrors, {html: ''});
                                BX.hide(formItemsErrors);
                            }
                        }
                    });
                }
            }
            // empty password input
            if (!!mailConfigPassword && !!mailConfigSMTPMailID && mailConfigSMTPMailID.value !='') {
                BX.bind(mailConfigPassword, 'focus', function () {
                    if (mailConfigPassword.value == '************') {
                        mailConfigPassword.value = '';
                    }
                });
                BX.bind(mailConfigPassword, 'blur', function () {
                    if (mailConfigPassword.value == '') {
                        mailConfigPassword.value = '************';
                    }
                });
            }
        },
        closeSMTPForm: function () {
            let slider = top.BX.SidePanel.Instance.getSliderByWindow(window);
            if (!!slider) {
                slider.setCacheable(false);
                slider.close();
            } else {
                window.location.href = '/mail/';
            }
        },
        getRequestParams: function () {
            paramsRequest = {};

            if (!!mailConfigMailID) {
                paramsRequest['mailConfigMailID'] = mailConfigMailID.value;
            }
            if (!!mailConfigSMTPMailID) {
                paramsRequest['mailConfigSMTPMailID'] = mailConfigSMTPMailID.value;
            }
            if (!!mailConfigSMTPAddress) {
                paramsRequest['mailConfigSMTPAddress'] = mailConfigSMTPAddress.value;
            }
            if (!!mailConfigSMTPPort) {
                paramsRequest['mailConfigSMTPPort'] = mailConfigSMTPPort.value;
            }
            if (!!mailConfigSecure) {
                if (mailConfigSecure.checked) {
                    paramsRequest['mailConfigSecure'] = mailConfigSecure.value;
                }
            }
            if (!!mailConfigAuthType) {
                paramsRequest['mailConfigAuthType'] = mailConfigAuthType.value;
            }
            if (!!mailConfigLogin) {
                paramsRequest['mailConfigLogin'] = mailConfigLogin.value;
            }
            if (!!mailConfigPassword) {
                paramsRequest['mailConfigPassword'] = mailConfigPassword.value;
            }
            paramsRequest['saveSMTPConfig'] = 'Y';
        },
        getDeleteParams: function () {
            paramsRequest = {};
            if (!!mailConfigSMTPMailID) {
                paramsRequest['mailConfigSMTPMailID'] = mailConfigSMTPMailID.value;
            }
            paramsRequest['deleteSMTPConfig'] = 'Y';
        },
        sendSMTPForm: function () {
            let instance = this;
            // get form params for request
            instance.getRequestParams();
            //check path for request
            if (!!instance.params.componentPath && instance.params.componentPath.length > 0) {
                if (!!paramsRequest) {
                    // save button animation
                    if (!!smtpConfigSaveBtn) {
                        BX.addClass(smtpConfigSaveBtn, 'ui-btn-wait');
                        smtpConfigSaveBtn.disabled = true;
                    }

                    if (!!mailConfigErrors) {
                        BX.adjust(mailConfigErrors, {
                            html: ''
                        });
                        BX.hide(mailConfigErrors);
                    }

                    // errors operations
                    let formItemBlocks = BX.findChild(document,
                        {class: 'smtpmail-config-form-item'}, true, true);

                    if (!!formItemBlocks) {
                        for (let key in formItemBlocks) {
                            if (formItemBlocks.hasOwnProperty(key)) {
                                if (!!formItemBlocks[key]) {
                                    BX.removeClass(formItemBlocks[key], 'smtpmail-form-item-error');
                                }
                            }
                        }
                    }

                    let formItemsErrors = BX.findChild(document,
                        {class: 'smtpmail-config-form-error'}, true, true);
                    if (!!formItemsErrors) {
                        for (let key in formItemsErrors) {
                            if (formItemsErrors.hasOwnProperty(key)) {
                                if (!!formItemsErrors[key]) {
                                    BX.hide(formItemsErrors[key]);
                                }
                            }
                        }
                    }

                    if (!!smtpConfigAdvancedBlock) {
                        if (!!mailConfigAuthType) {
                            if (mailConfigAuthType.value != 'C') {
                                BX.removeClass(smtpConfigAdvancedBlock, 'show');
                            }
                        } else {
                            BX.removeClass(smtpConfigAdvancedBlock, 'show');
                        }
                    }

                    // ajax request
                    BX.ajax({
                        url: instance.params.componentPath + '/ajax.php',
                        data: paramsRequest,
                        method: 'POST',
                        timeout: 30,
                        async: true,
                        dataType: 'json',
                        cache: false,
                        onsuccess: function (data) {
                            if (!!data['status']) {
                                if (data['status'] == 'error') {
                                    if (!!data['error'] && !!mailConfigErrors) {
                                        BX.adjust(mailConfigErrors, {
                                            html: data['error']
                                        });
                                        BX.show(mailConfigErrors);
                                    }
                                    if (!!data['errors']) {
                                        for (let key in data['errors']) {
                                            if (data['errors'].hasOwnProperty(key)) {
                                                let errorBlock = '';
                                                switch (key) {
                                                    case 'SERVER':
                                                        errorBlock = mailConfigSMTPAddress.getAttribute('id');
                                                        break;
                                                    case 'PORT':
                                                        errorBlock = mailConfigSMTPPort.getAttribute('id');
                                                        break;
                                                    case 'LOGIN':
                                                        errorBlock = mailConfigLogin.getAttribute('id');
                                                        break;
                                                    case 'PASSWORD':
                                                        errorBlock = mailConfigPassword.getAttribute('id');
                                                        break;
                                                }
                                                if (errorBlock.length > 0) {
                                                    let parentErrorBlock;
                                                    if (key == 'PORT') {
                                                        parentErrorBlock =
                                                            document.getElementById(errorBlock).parentNode.parentNode;
                                                    } else {
                                                        parentErrorBlock =
                                                            document.getElementById(errorBlock).parentNode;
                                                    }
                                                    if (!!parentErrorBlock) {
                                                        BX.addClass(parentErrorBlock, 'smtpmail-form-item-error');
                                                        let errorTextBlock = BX.findChild(parentErrorBlock,
                                                            {class: 'smtpmail-config-form-error'}, true, false);
                                                        if (!!errorTextBlock) {
                                                            BX.adjust(errorTextBlock, {html: data['errors'][key]});
                                                            BX.show(errorTextBlock);
                                                        }
                                                    }
                                                }
                                                if (key == 'LOGIN' || key == 'PASSWORD') {
                                                    BX.addClass(smtpConfigAdvancedBlock, 'show');
                                                }
                                            }
                                        }
                                    }
                                }
                                if (data['status'] == 'done') {
                                    if (!!data['smtp_mail_id'] && !!mailConfigSMTPMailID) {
                                        mailConfigSMTPMailID.value = data['smtp_mail_id'];
                                    }
                                    instance.closeSMTPForm();
                                }
                            }
                            if (!!smtpConfigSaveBtn) {
                                smtpConfigSaveBtn.disabled = false;
                                BX.removeClass(smtpConfigSaveBtn, 'ui-btn-wait');
                            }
                        },
                        onfailure: function () {
                            if (!!mailConfigErrors) {
                                BX.adjust(mailConfigErrors, {
                                    html: BX.message('MAIL_SMTP_B24_CONFIG_ERROR_AJAX')
                                });
                                BX.show(mailConfigErrors);
                            }
                            if (!!smtpConfigSaveBtn) {
                                smtpConfigSaveBtn.disabled = false;
                                BX.removeClass(smtpConfigSaveBtn, 'ui-btn-wait');
                            }
                        }
                    });
                } else {
                    if (!!mailConfigErrors) {
                        BX.adjust(mailConfigErrors, {
                            html: BX.message('MAIL_SMTP_B24_CONFIG_ERROR_SEND_PARAMS')
                        });
                        BX.show(mailConfigErrors);
                    }
                    if (!!smtpConfigSaveBtn) {
                        smtpConfigSaveBtn.disabled = false;
                        BX.removeClass(smtpConfigSaveBtn, 'ui-btn-wait');
                    }
                }
            } else {
                console.log(BX.message('MAIL_SMTP_B24_CONFIG_ERROR_COMPONENT_PATH'));
            }
        },
        disconnectSMTPForm: function () {
            let instance = this;
            // get form params for delete
            instance.getDeleteParams();
            //check path for request
            if (!!instance.params.componentPath && instance.params.componentPath.length > 0) {
                // disconnect button animation
                if (!!smtpConfigDisconnectBtn) {
                    BX.addClass(smtpConfigDisconnectBtn, 'ui-btn-wait');
                    smtpConfigDisconnectBtn.disabled = true;

                    // ajax request
                    BX.ajax({
                        url: instance.params.componentPath + '/ajax.php',
                        data: paramsRequest,
                        method: 'POST',
                        timeout: 30,
                        async: true,
                        dataType: 'json',
                        cache: false,
                        onsuccess: function (data) {
                            instance.closeSMTPForm();
                        },
                        onfailure: function () {
                            if (!!mailConfigErrors) {
                                BX.adjust(mailConfigErrors, {
                                    html: BX.message('MAIL_SMTP_B24_CONFIG_ERROR_AJAX')
                                });
                                BX.show(mailConfigErrors);
                            }
                            if (!!smtpConfigDisconnectBtn) {
                                smtpConfigDisconnectBtn.disabled = false;
                                BX.removeClass(smtpConfigDisconnectBtn, 'ui-btn-wait');
                            }
                        }
                    });
                }
            } else {
                console.log(BX.message('MAIL_SMTP_B24_CONFIG_ERROR_COMPONENT_PATH'));
            }
        }
    };
})();