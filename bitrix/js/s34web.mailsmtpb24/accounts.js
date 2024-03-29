/**
 * Created: 14.03.2023, 15:38
 * Author : Dmitry Antiptsev <cto@34web.ru>
 * Company: 34web Studio
 */

BX.namespace('BX.mailSMTPB24Accounts');

(function () {
    'use strict';

    let authSelector;
    let loginBlockNode, passwordBlockNode;
    let loginNode, passwordNode;
    let fieldNodes;

    BX.mailSMTPB24Accounts = {
        init: function () {
            let instance = this;
            instance.initDom(); // get DOM elements
            instance.setEvents();
        },
        initDom: function () {
            loginBlockNode = BX('tr_LOGIN');
            passwordBlockNode = BX('tr_PASSWORD');
            authSelector = BX('AUTH_ACCOUNT_SMTP');
            loginNode = BX('AUTH_LOGIN_SMTP');
            passwordNode = BX('AUTH_PASSWORD_SMTP');
            fieldNodes = document.querySelectorAll('#smtp_accounts_edit_form input, #smtp_accounts_edit_form select');

            if (!!authSelector && authSelector.value === 'C') {
                if (!!loginBlockNode) {
                    BX.show(loginBlockNode);
                }
                if(!!passwordBlockNode)
                {
                    BX.show(passwordBlockNode);
                }
            } else {
                if (!!loginBlockNode) {
                    BX.hide(loginBlockNode);
                }
                if(!!passwordBlockNode)
                {
                    BX.hide(passwordBlockNode);
                }
            }
        },
        setEvents: function () {
            // change auth type selector
            if(!!authSelector) {
                BX.bind(authSelector, 'change', function (event) {
                    if (typeof event.target !== 'undefined' && event.target.value === 'C') {
                        if (!!loginBlockNode) {
                            BX.show(loginBlockNode);
                        }
                        if (!!passwordBlockNode) {
                            BX.show(passwordBlockNode);
                        }
                    } else {
                        if (!!loginNode) {
                            loginNode.value = '';
                        }
                        if (!!passwordNode) {
                            passwordNode.value = '';
                        }
                        if (!!loginBlockNode) {
                            BX.hide(loginBlockNode);
                        }
                        if (!!passwordBlockNode) {
                            BX.hide(passwordBlockNode);
                        }
                    }
                });
            }
            // set empty password on change
            if(!!fieldNodes && !!passwordNode)
            {
                for (let key in fieldNodes) {
                    if (fieldNodes.hasOwnProperty(key)) {
                        if(fieldNodes[key] !== passwordNode)
                        {
                            if(fieldNodes[key].tagName === 'SELECT') {
                                BX.bind(fieldNodes[key], 'change', function () {
                                    passwordNode.value = '';
                                });
                            }
                            if(fieldNodes[key].tagName === 'INPUT') {
                                BX.bind(fieldNodes[key], 'keydown', function () {
                                    passwordNode.value = '';
                                });
                            }
                        }
                    }
                }
            }
        }
    }
})();