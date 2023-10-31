/**
 * Created: 19.03.2021, 17:51
 * Author : Dmitry Antiptsev <cto@34web.ru>
 * Company: 34web Studio
 */

BX.namespace('BX.mailSMTPB24MailExt');

(function () {
    'use strict';

    let mailConnectForm, mailConnectSmtpForm, mailConnectSmtpWarning, buttonCheckSmtpBlock, buttonCheckSMTP,
        resultCheckSMTPBlock, resultCheckSMTPBlockText, autoConnectionBlock, autoConnectionBlockText, useSMTPCheck,
        passImap;
    let serviceID, mailboxID, passPlaceholder;
    let ajaxPath, otherServices;

    BX.mailSMTPB24MailExt = {
        init: function (parameters) {
            let instance = this;
            instance.params = parameters.params || {};
            instance.initParams(); // get params
            instance.initDom(); // get DOM elements
            instance.extendSMTPConnectForm(); // extend form
            instance.setEvents();
        },
        initParams: function () {
            let instance = this;
            otherServices = [];
            if (typeof instance.params.ajaxPath !== 'undefined' && instance.params.ajaxPath.length > 0) {
                ajaxPath = instance.params.ajaxPath;
            }
            if (typeof instance.params.otherServices !== 'undefined') {
                if (!instance.isEmptyObject(instance.params.otherServices)) {
                    otherServices = instance.params.otherServices;
                }
            }
            serviceID = 0;
            mailboxID = 0;
            passPlaceholder = '';
        },
        initDom: function () {
            mailConnectForm = BX('mail_connect_form');
            if (!!mailConnectForm) {
                // smtp form
                mailConnectSmtpForm = mailConnectForm.querySelector('#mail_connect_mb_server_smtp_form');
                if (!!mailConnectSmtpForm) {
                    mailConnectSmtpWarning = mailConnectSmtpForm.querySelector('.mail-connect-warning-text');
                }
                // use smtp input
                useSMTPCheck = mailConnectForm.querySelector('[name="fields[use_smtp]"]');
                // imap password
                passImap = mailConnectForm.querySelector('[name="fields[pass_imap]"]');
                passPlaceholder = mailConnectForm.querySelector('[name="fields[pass_placeholder]"]');
                // get service id
                let serviceIDNode = mailConnectForm.querySelector('[name="fields[service_id]"]');
                if (!!serviceIDNode && parseInt(serviceIDNode.value) > 0) {
                    serviceID = parseInt(serviceIDNode.value);
                }
                // get mailbox id
                let mailboxIDNode = mailConnectForm.querySelector('[name="fields[mailbox_id]"]');
                if (!!mailboxIDNode && parseInt(mailboxIDNode.value) > 0) {
                    mailboxID = parseInt(mailboxIDNode.value);
                }
            }
        },
        extendSMTPConnectForm: function () {
            // change warning text
            if (!!mailConnectSmtpWarning) {
                let warningText = mailConnectSmtpWarning.innerHTML;
                BX.adjust(mailConnectSmtpWarning, {html: warningText + ' ' + BX.message('S34WEB_MAILSMTPB24_CONFIG_SMTP_WARNING')});
            }
            // create button check SMTP & result answer
            if (!!mailConnectSmtpForm) {
                // check auto connection
                if (!!useSMTPCheck && useSMTPCheck.checked === true) {
                    if (mailboxID > 0) {
                        autoConnectionBlock = BX.create('div', {attrs: {className: 'mail-auto-check-smtp-block'}});
                        autoConnectionBlockText = BX.create('span', {
                            attrs: {className: 'mail-auto-check-smtp-connect'},
                        });
                        BX.append(autoConnectionBlockText, autoConnectionBlock);
                        BX.insertBefore(autoConnectionBlock, mailConnectSmtpForm);
                        BX.hide(autoConnectionBlock);
                    }
                }
                // button & answer
                if (otherServices.length > 0 && BX.util.in_array(serviceID, otherServices)) {
                    // button block
                    buttonCheckSmtpBlock = BX.create('div', {attrs: {className: 'mail-check-smtp-block'}});
                    buttonCheckSMTP = BX.create('span', {
                        attrs: {className: 'mail-check-smtp-connect'},
                        html: BX.message('S34WEB_MAILSMTPB24_CONFIG_BTN_CHECK')
                    });
                    BX.append(buttonCheckSMTP, buttonCheckSmtpBlock);
                    BX.append(buttonCheckSmtpBlock, mailConnectSmtpForm);
                    // result answer
                    resultCheckSMTPBlock = BX.create('div', {attrs: {className: 'mail-check-smtp-result-block'}});
                    resultCheckSMTPBlockText = BX.create('div', {
                        attrs: {className: 'mail-check-smtp-result-text'},
                    });
                    BX.append(resultCheckSMTPBlockText, resultCheckSMTPBlock);
                    BX.append(resultCheckSMTPBlock, mailConnectSmtpForm);
                }
            }
        },
        setEvents: function () {
            let instance = this;
            // check button click
            if (!!buttonCheckSMTP) {
                BX.bind(buttonCheckSMTP, 'click', BX.delegate(this.checkSMTPConnect, this));
            }
            // show or hide block on use smtp input
            if (!!useSMTPCheck) {
                BX.bind(useSMTPCheck, 'click', function () {
                    if (useSMTPCheck.checked === true) {
                        if (!!autoConnectionBlock) {
                            BX.show(autoConnectionBlock);
                        }
                    } else {
                        if (!!autoConnectionBlock) {
                            BX.hide(autoConnectionBlock);
                        }
                    }
                });
            }
            // get auto connect
            if (!!autoConnectionBlock && mailboxID > 0) {
                instance.checkAutoConnection();
            }
            // set new password on change imap password
            if (mailboxID > 0) {
                if (!!passImap && !!mailConnectForm) {
                    BX.bind(passImap, 'change', function () {
                        let passSmtpField = mailConnectForm.querySelector('[name="fields[pass_smtp]"]');
                        if (!!passSmtpField && (!(passSmtpField.value.length > 0) || !(passSmtpField.value === passPlaceholder))) {
                            passSmtpField.value = this.value;
                        }
                    });
                }
            }
        },
        checkSMTPConnect: function () {
            let instance = this;
            let requestParams = {};
            let smtpFields = {};
            // hide block auto check connection
            if (!!autoConnectionBlock) {
                BX.hide(autoConnectionBlock);
            }
            // get form fields
            if (!!mailConnectForm) {
                // password placeholder
                let smtpPassPlaceholder = mailConnectForm.querySelector('[name="fields[pass_placeholder]"]');
                if (!!smtpPassPlaceholder) {
                    smtpFields['PASSWORD_PLACEHOLDER'] = smtpPassPlaceholder.value;
                }
                // mailbox id
                let smtpMailboxID = mailConnectForm.querySelector('[name="fields[mailbox_id]"]');
                if (!!smtpMailboxID) {
                    smtpFields['MAILBOX_ID'] = smtpMailboxID.value;
                }
                // service id
                let smtpServiceID = mailConnectForm.querySelector('[name="fields[service_id]"]');
                if (!!smtpServiceID) {
                    smtpFields['SERVICE_ID'] = smtpServiceID.value;
                }
                // email
                let smtpEmail = mailConnectForm.querySelector('[name="fields[email]"]');
                if (!!smtpEmail) {
                    smtpFields['EMAIL'] = smtpEmail.value;
                }
                // use smtp
                let smtpUseSmtp = mailConnectForm.querySelector('[name="fields[use_smtp]"]');
                if (!!smtpUseSmtp) {
                    if (smtpUseSmtp.getAttribute('type') === 'checkbox' && smtpUseSmtp.checked === true) {
                        smtpFields['USE_SMTP'] = 'Y';
                    }
                }
                // upload_outgoing
                let smtpUploadOutgoind = mailConnectForm.querySelector('[name="fields[upload_outgoing]"]');
                if (!!smtpUploadOutgoind) {
                    if (smtpUploadOutgoind.getAttribute('type') === 'checkbox' &&
                        smtpUploadOutgoind.checked === true) {
                        smtpFields['UPLOAD_OUTGOING'] = 'Y';
                    }
                }
                // server
                let smtpServer = mailConnectForm.querySelector('[name="fields[server_smtp]"]');
                if (!!smtpServer) {
                    smtpFields['SERVER'] = smtpServer.value;
                }
                // port
                let smtpPort = mailConnectForm.querySelector('[name="fields[port_smtp]"]');
                if (!!smtpPort) {
                    smtpFields['PORT'] = smtpPort.value;
                }
                // secure
                let smtpSecure = mailConnectForm.querySelector('[name="fields[ssl_smtp]"]');
                if (!!smtpSecure) {
                    if (smtpSecure.getAttribute('type') === 'checkbox' &&
                        smtpSecure.checked === true) {
                        smtpFields['SECURE'] = 'S';
                    }
                }
                // login
                let smtpLogin = mailConnectForm.querySelector('[name="fields[login_smtp]"]');
                if (!!smtpLogin) {
                    smtpFields['LOGIN'] = smtpLogin.value;
                }
                // password
                let smtpPassword = mailConnectForm.querySelector('[name="fields[pass_smtp]"]');
                if (!!smtpPassword) {
                    smtpFields['PASSWORD'] = smtpPassword.value;
                }
            }
            if (!instance.isEmptyObject(smtpFields)) {
                requestParams = smtpFields;
            }

            let flagStop = false;

            if (typeof ajaxPath === 'undefined' || ajaxPath.length <= 0) {
                flagStop = true;
                if (!!resultCheckSMTPBlockText) {
                    BX.adjust(resultCheckSMTPBlockText, {
                        style: {
                            background: 'rgb(253,225,229)',
                            color: 'rgb(219,0,22)'
                        },
                        html:
                            BX.message('S34WEB_MAILSMTPB24_CONFIG_SMTP_ERROR_AJAX_PATH')
                    });
                }
            }
            if (!flagStop) {
                if (!!resultCheckSMTPBlockText) {
                    BX.adjust(resultCheckSMTPBlockText, {
                        style: {
                            background: '#dfdfdf',
                            color: '#333'
                        },
                        html:
                            BX.message('S34WEB_MAILSMTPB24_CONFIG_CHECK_WAIT')
                    });
                }
                BX.ajax({
                    url: ajaxPath,
                    data: {'action': 'checkSMTPConnection', 'params': requestParams},
                    method: 'POST',
                    timeout: 20,
                    async: true,
                    dataType: 'json',
                    cache: false,
                    onsuccess: function (data) {
                        if (typeof data['status'] !== 'undefined') {
                            if (data['status'] === 'success') {
                                if (!!resultCheckSMTPBlockText) {
                                    BX.adjust(resultCheckSMTPBlockText, {
                                        style: {
                                            background: 'rgb(210, 249, 95)',
                                            color: 'rgb(51,125,12)'
                                        },
                                        html:
                                            BX.message('S34WEB_MAILSMTPB24_CONFIG_CHECK_SMTP_SUCCESS')
                                    });
                                }
                            } else {
                                if (typeof data['error'] !== 'undefined' && data['error'].length > 0 &&
                                    !!resultCheckSMTPBlockText) {
                                    BX.adjust(resultCheckSMTPBlockText, {
                                        style: {
                                            background: 'rgb(253,225,229)',
                                            color: 'rgb(219,0,22)'
                                        },
                                        html: data['error']
                                    });
                                }
                            }
                        } else {
                            if (!!resultCheckSMTPBlockText) {
                                BX.adjust(resultCheckSMTPBlockText, {
                                    style: {
                                        background: 'rgb(253,225,229)',
                                        color: 'rgb(219,0,22)'
                                    },
                                    html:
                                        BX.message('S34WEB_MAILSMTPB24_ERROR_REQUEST')
                                });
                            }
                        }
                    },
                    onfailure: function () {
                        if (!!resultCheckSMTPBlockText) {
                            BX.adjust(resultCheckSMTPBlockText, {
                                style: {
                                    background: 'rgb(253,225,229)',
                                    color: 'rgb(219,0,22)'
                                },
                                html:
                                    BX.message('S34WEB_MAILSMTPB24_CONFIG_SMTP_ERROR_REQUEST')
                            });
                        }
                    }
                });
            }
        },
        checkAutoConnection: function () {
            let instance = this;
            let requestParams = {};
            let smtpFields = {};
            // check use_smtp flag
            if (!useSMTPCheck) {
                return;
            }
            if (!!useSMTPCheck && useSMTPCheck.checked !== true) {
                return;
            }
            // get form fields
            if (!!mailConnectForm) {
                // password placeholder
                let smtpPassPlaceholder = mailConnectForm.querySelector('[name="fields[pass_placeholder]"]');
                if (!!smtpPassPlaceholder) {
                    smtpFields['PASSWORD_PLACEHOLDER'] = smtpPassPlaceholder.value;
                }
                // mailbox id
                let smtpMailboxID = mailConnectForm.querySelector('[name="fields[mailbox_id]"]');
                if (!!smtpMailboxID) {
                    smtpFields['MAILBOX_ID'] = smtpMailboxID.value;
                }
                // service id
                let smtpServiceID = mailConnectForm.querySelector('[name="fields[service_id]"]');
                if (!!smtpServiceID) {
                    smtpFields['SERVICE_ID'] = smtpServiceID.value;
                }
                // email
                let smtpEmail = mailConnectForm.querySelector('[name="fields[email]"]');
                if (!!smtpEmail) {
                    smtpFields['EMAIL'] = smtpEmail.value;
                }
                // use smtp
                let smtpUseSmtp = mailConnectForm.querySelector('[name="fields[use_smtp]"]');
                if (!!smtpUseSmtp) {
                    if (smtpUseSmtp.getAttribute('type') === 'checkbox' && smtpUseSmtp.checked === true) {
                        smtpFields['USE_SMTP'] = 'Y';
                    }
                }
                // upload_outgoing
                let smtpUploadOutgoind = mailConnectForm.querySelector('[name="fields[upload_outgoing]"]');
                if (!!smtpUploadOutgoind) {
                    if (smtpUploadOutgoind.getAttribute('type') === 'checkbox' &&
                        smtpUploadOutgoind.checked === true) {
                        smtpFields['UPLOAD_OUTGOING'] = 'Y';
                    }
                }
                // server
                let smtpServer = mailConnectForm.querySelector('[name="fields[server_smtp]"]');
                if (!!smtpServer) {
                    smtpFields['SERVER'] = smtpServer.value;
                }
                // port
                let smtpPort = mailConnectForm.querySelector('[name="fields[port_smtp]"]');
                if (!!smtpPort) {
                    smtpFields['PORT'] = smtpPort.value;
                }
                // secure
                let smtpSecure = mailConnectForm.querySelector('[name="fields[ssl_smtp]"]');
                if (!!smtpSecure) {
                    if (smtpSecure.getAttribute('type') === 'checkbox' &&
                        smtpSecure.checked === true) {
                        smtpFields['SECURE'] = 'S';
                    }
                }
                // login
                let smtpLogin = mailConnectForm.querySelector('[name="fields[login_smtp]"]');
                if (!!smtpLogin) {
                    smtpFields['LOGIN'] = smtpLogin.value;
                }
                // password
                let smtpPassword = mailConnectForm.querySelector('[name="fields[pass_smtp]"]');
                if (!!smtpPassword) {
                    smtpFields['PASSWORD'] = smtpPassword.value;
                }
            }
            if (!instance.isEmptyObject(smtpFields)) {
                requestParams = smtpFields;
            }

            let flagStop = false;
            if (typeof ajaxPath === 'undefined' || ajaxPath.length <= 0) {
                flagStop = true;
                if (!!autoConnectionBlockText) {
                    BX.adjust(autoConnectionBlockText, {
                        style: {
                            background: 'rgb(253,225,229)',
                            color: 'rgb(219,0,22)'
                        },
                        html:
                            BX.message('S34WEB_MAILSMTPB24_CONFIG_SMTP_ERROR_AJAX_PATH')
                    });
                }
            }
            if (!flagStop) {
                // show auto connection block;
                BX.show(autoConnectionBlock);
                if (!!autoConnectionBlockText) {
                    BX.adjust(autoConnectionBlockText, {
                        style: {
                            background: '#dfdfdf',
                            color: '#333'
                        },
                        html:
                            BX.message('S34WEB_MAILSMTPB24_CONFIG_CHECK_WAIT')
                    });
                }
                BX.ajax({
                    url: ajaxPath,
                    data: {'action': 'checkSMTPConnection', 'params': requestParams},
                    method: 'POST',
                    timeout: 20,
                    async: true,
                    dataType: 'json',
                    cache: false,
                    onsuccess: function (data) {
                        if (typeof data['status'] !== 'undefined') {
                            if (data['status'] === 'success') {
                                if (!!autoConnectionBlockText) {
                                    BX.adjust(autoConnectionBlockText, {
                                        style: {
                                            background: 'rgb(210, 249, 95)',
                                            color: 'rgb(51,125,12)'
                                        },
                                        html:
                                            BX.message('S34WEB_MAILSMTPB24_CONFIG_AUTO_CHECK_SUCCESS')
                                    });
                                }
                            } else {
                                if (typeof data['error'] !== 'undefined' && data['error'].length > 0 &&
                                    !!autoConnectionBlockText) {
                                    BX.adjust(autoConnectionBlockText, {
                                        style: {
                                            background: 'rgb(253,225,229)',
                                            color: 'rgb(219,0,22)'
                                        },
                                        html: data['error']
                                    });
                                }
                            }
                        } else {
                            if (!!autoConnectionBlockText) {
                                BX.adjust(autoConnectionBlockText, {
                                    style: {
                                        background: 'rgb(253,225,229)',
                                        color: 'rgb(219,0,22)'
                                    },
                                    html:
                                        BX.message('S34WEB_MAILSMTPB24_ERROR_REQUEST')
                                });
                            }
                        }
                    },
                    onfailure: function () {
                        if (!!autoConnectionBlockText) {
                            BX.adjust(autoConnectionBlockText, {
                                style: {
                                    background: 'rgb(253,225,229)',
                                    color: 'rgb(219,0,22)'
                                },
                                html:
                                    BX.message('S34WEB_MAILSMTPB24_CONFIG_SMTP_ERROR_REQUEST')
                            });
                        }
                    }
                });
            }
        },
        isEmptyObject: function (obj) {
            for (let i in obj) {
                if (obj.hasOwnProperty(i)) {
                    return false;
                }
            }
            return true;
        }
    }
})();