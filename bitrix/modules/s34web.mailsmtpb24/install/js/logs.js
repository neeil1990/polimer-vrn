/**
 * Created: 22.10.2021, 14:49
 * Author : Dmitry Antiptsev <cto@34web.ru>
 * Company: 34web Studio
 */

BX.namespace('BX.mailSMTPB24Logs');

(function () {
    'use strict';

    let deleteAllLogsBtn, resultBlock;
    let ajaxPath;

    BX.mailSMTPB24Logs = {
        init: function (parameters) {
            let instance = this;
            instance.params = parameters.params || {};
            instance.initParams(); // get params
            instance.initDom(); // get DOM elements
            instance.setEvents();
        },
        initParams: function () {
            let instance = this;
            if (typeof instance.params.ajaxPath !== 'undefined' && instance.params.ajaxPath.length > 0) {
                ajaxPath = instance.params.ajaxPath;
            }
        },
        initDom: function () {
            deleteAllLogsBtn = BX('SMTPMAIL_DELETE_ALL_LOGS');
            resultBlock = BX('SMTPMAIL_DELETE_LOGS_RESULT');
        },
        setEvents: function () {
            let instance = this;
            if (!!deleteAllLogsBtn) {
                BX.bind(deleteAllLogsBtn, 'click', BX.delegate(this.deleteAllLogs, this));
            }
            instance.setDeleteButtonEvents();
        },
        deleteAllLogs: function ()
        {
            let confirmDelete = confirm(BX.message('S34WEB_MAILSMTPB24_DELETE_ALL_LOGS_CONFIRM'));
            if (confirmDelete) {
                let flagStop = false;
                if (typeof ajaxPath === 'undefined' || ajaxPath.length <= 0) {
                    flagStop = true;
                    console.log(BX.message('S34WEB_MAILSMTPB24_ERROR_AJAX_PATH'));
                }
                if (!flagStop) {
                    deleteAllLogsBtn.disabled = true;
                    if (!!resultBlock) {
                        BX.adjust(resultBlock, {html: ''});
                    }
                    BX.ajax({
                        url: ajaxPath,
                        data: {'action': 'deleteAllLogs'},
                        method: 'POST',
                        timeout: 20,
                        async: true,
                        dataType: 'json',
                        cache: false,
                        onsuccess: function (data) {
                            if (typeof data['status'] !== 'undefined') {
                                if (data['status'] === 'success') {
                                    window.location.reload();
                                }
                                if (typeof data['error'] !== 'undefined' && data['error'].length > 0 && !!resultBlock) {
                                    BX.adjust(resultBlock, {html: data['error']});
                                }
                            } else {
                                BX.adjust(resultBlock, {html: BX.message('S34WEB_MAILSMTPB24_ERROR_REQUEST')});
                            }
                            deleteAllLogsBtn.disabled = false;
                        },
                        onfailure: function () {
                            if (!!resultBlock) {
                                BX.adjust(resultBlock, {html: BX.message('S34WEB_MAILSMTPB24_ERROR_REQUEST')});
                            }
                            deleteAllLogsBtn.disabled = false;
                        }
                    });
                }
            }
        },
        setDeleteButtonEvents: function () {
            let buttonsDeleteLogs = document.querySelectorAll('.smtp_mail_log_table .delete-log-block');
            if (!!buttonsDeleteLogs) {
                for (let key in buttonsDeleteLogs) {
                    if (buttonsDeleteLogs.hasOwnProperty(key)) {
                        if (!!buttonsDeleteLogs[key].getAttribute('data-file')) {
                            let logFileName = buttonsDeleteLogs[key].getAttribute('data-file');
                            if (logFileName.length > 0) {
                                BX.bind(buttonsDeleteLogs[key], 'click', function () {
                                    let confirmDelete = confirm(BX.message('S34WEB_MAILSMTPB24_DELETE_LOG_CONFIRM'));
                                    if (confirmDelete) {
                                        let flagStop = false;
                                        if (typeof ajaxPath === 'undefined' || ajaxPath.length <= 0) {
                                            flagStop = true;
                                            console.log(BX.message('S34WEB_MAILSMTPB24_ERROR_AJAX_PATH'));
                                        }
                                        if (!flagStop) {
                                            if (!!resultBlock) {
                                                BX.adjust(resultBlock, {html: ''});
                                            }
                                            buttonsDeleteLogs[key].disabled = true;
                                            let requestParams = {'file': logFileName};
                                            BX.ajax({
                                                url: ajaxPath,
                                                data: {'action': 'deleteLog', 'params': requestParams},
                                                method: 'POST',
                                                timeout: 20,
                                                async: true,
                                                dataType: 'json',
                                                cache: false,
                                                onsuccess: function (data) {
                                                    if (typeof data['status'] !== 'undefined') {
                                                        if (data['status'] === 'success') {
                                                            window.location.reload();
                                                        }
                                                        if (typeof data['error'] !== 'undefined' &&
                                                            data['error'].length > 0) {
                                                            BX.adjust(resultBlock, {html: data['error']});
                                                        }
                                                    }
                                                    buttonsDeleteLogs[key].disabled = false;
                                                },
                                                onfailure: function () {
                                                    if (!!resultBlock) {
                                                        BX.adjust(resultBlock, {
                                                            html: BX.message('S34WEB_MAILSMTPB24_ERROR_REQUEST')
                                                        });
                                                    }
                                                    buttonsDeleteLogs[key].disabled = false;
                                                }
                                            });
                                        }
                                    }
                                });
                            } else {
                                if (!!resultBlock) {
                                    BX.adjust(resultBlock, {html: BX.message('S34WEB_MAILSMTPB24_ERROR_LOG_FILE_NAME')});
                                }
                            }
                        }
                    }
                }
            }
        }
    }
})();