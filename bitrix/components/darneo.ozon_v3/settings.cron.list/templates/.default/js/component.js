(function () {
    'use strict'

    BX.namespace('BX.Ozon.SettingsCronList.Vue')

    BX.Ozon.SettingsCronList.Vue = {
        init: function (parameters) {
            this.ajaxUrl = parameters.ajaxUrl || ''
            this.signedParams = parameters.signedParams
            this.data = parameters.data || {}

            this.initStore()
            this.initComponent()
        },
        initStore: function () {
            this.store = BX.Vuex.store({
                state: {
                    data: this.data,
                },
                actions: {
                    change(store, payload) {
                        store.commit('changeData', payload)
                    }
                },
                mutations: {
                    changeData(state, params) {
                        if (params.data) {
                            state.data = params.data
                        }
                    }
                }
            })
        },

        initComponent: function () {
            BX.BitrixVue.createApp({
                el: '#vue-settings-cron',
                store: this.store,
                data: BX.delegate(function () {
                    return {
                        signedParams: this.signedParams,
                        isReinstallStart: false
                    }
                }, this),
                computed:
                        BX.Vuex.mapState({
                            data: state => state.data
                        }),
                watch: {
                    'data': function () {
                        this.isReinstallStart = false
                    },
                },
                methods: {
                    actionUpdate: function (code, value) {
                        let data = this.getDataAjax()
                        data['action'] = 'update'
                        data['code'] = code
                        data['value'] = value
                        data['signedParamsString'] = this.signedParams
                        BX.Ozon.SettingsCronList.Vue.sendRequest(data)
                    },
                    actionReinstall: function () {
                        let data = this.getDataAjax()
                        data['action'] = 'reinstall'
                        data['signedParamsString'] = this.signedParams
                        this.isReinstallStart = true
                        BX.Ozon.SettingsCronList.Vue.sendRequest(data)
                    },
                    getDataAjax: function () {
                        let data = {}
                        data['signedParamsString'] = this.signedParams
                        return data
                    },
                },
                template: `
                    <div>
                        <ozon-cron-install
                            v-bind:isReinstallStart='isReinstallStart'
                            v-on:actionReinstall='actionReinstall'
                        />
                        <div class='card'>
                            <div class='card-body' v-if='!isReinstallStart'>
                                <ozon-cron-list
                                    v-bind:data='data'
                                    v-on:actionUpdate='actionUpdate'
                                />
                            </div>
                        </div>
                    </div>
                `
            })
        },

        sendRequest: function (data) {
            BX.ajax({
                method: 'POST',
                dataType: 'json',
                url: this.ajaxUrl,
                data: data,
                async: true,
                onsuccess: result => {
                    if (result.STATUS === 'SUCCESS') {
                        if (result.DATA_VUE) {
                            this.store.commit('changeData', {
                                data: result.DATA_VUE
                            })
                        }
                        if (result.MESSAGE) {
                            this.notify(result.MESSAGE)
                        }
                    }
                    if (result.STATUS === 'ERROR') {
                        this.showPopup('Error', result.MESSAGE)
                    }
                },
                onfailure: result => {

                },
            })
        },

        notify: function (text) {
            BX.UI.Notification.Center.notify({
                content: text
            })
        },

        showPopup: function (title, content) {
            BX.UI.Dialogs.MessageBox.show(
                    {
                        title: title,
                        message: content,
                        modal: true,
                        buttons: BX.UI.Dialogs.MessageBoxButtons.OK,
                        onOk: function (messageBox) {
                            messageBox.close()
                        },
                    }
            )
        },
    }
})()