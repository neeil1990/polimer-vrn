(function () {
    'use strict'

    BX.namespace('BX.Ozon.SettingsKeyList.Vue')

    BX.Ozon.SettingsKeyList.Vue = {
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
                el: '#vue-settings-key',
                store: this.store,
                data: BX.delegate(function () {
                    return {
                        signedParams: this.signedParams,
                        request: {
                            isUpdateList: false,
                            isUpdateRow: false,
                        },
                    }
                }, this),
                computed:
                        BX.Vuex.mapState({
                            data: state => state.data
                        }),
                watch: {
                    data: function () {
                        this.request.isUpdateList = false
                        this.request.isUpdateRow = false
                    },
                },
                methods: {
                    actionAdd: function (clientId, apiKey, name, isMain) {
                        this.request.isUpdateList = true
                        let data = this.getDataAjax()
                        data['action'] = 'add'
                        data['clientId'] = clientId
                        data['apiKey'] = apiKey
                        data['name'] = name
                        data['isMain'] = isMain
                        data['signedParamsString'] = this.signedParams
                        BX.Ozon.SettingsKeyList.Vue.sendRequest(data)
                    },
                    actionDelete: function (rowId) {
                        this.request.isUpdateList = true
                        let data = this.getDataAjax()
                        data['action'] = 'delete'
                        data['rowId'] = rowId
                        data['signedParamsString'] = this.signedParams
                        BX.Ozon.SettingsKeyList.Vue.sendRequest(data)
                    },
                    actionUpdate: function (rowId, clientId, apiKey, name, isMain) {
                        this.request.isUpdateList = true
                        this.request.isUpdateRow = true
                        let data = this.getDataAjax()
                        data['action'] = 'update'
                        data['rowId'] = rowId
                        data['clientId'] = clientId
                        data['apiKey'] = apiKey
                        data['name'] = name
                        data['isMain'] = isMain
                        data['signedParamsString'] = this.signedParams
                        BX.Ozon.SettingsKeyList.Vue.sendRequest(data)
                    },
                    getDataAjax: function () {
                        let data = {}
                        data['signedParamsString'] = this.signedParams
                        return data
                    },
                },
                template: `
                    <div class='card mb-5 mb-xl-8'>
                    <div class='block_disabled' v-show='request.isUpdateList'></div>
                    <div class='card-body'>
                        <ozon-key-list
                            v-bind:data='data'
                            v-bind:request='request'
                            v-on:actionAdd='actionAdd'
                            v-on:actionDelete='actionDelete'
                            v-on:actionUpdate='actionUpdate'
                        />
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
                    }
                    if (result.STATUS === 'ERROR') {
                        $.fancybox.open({
                            src: result.MESSAGE,
                            type: 'html',
                            touch: false,
                            baseClass: 'thanks_msg',
                            openEffect: 'elastic',
                            openMethod: 'zoomIn',
                            closeEffect: 'elastic',
                            closeMethod: 'zoomOut'
                        })
                    }
                },
                onfailure: result => {

                },
            })
        },
    }
})()