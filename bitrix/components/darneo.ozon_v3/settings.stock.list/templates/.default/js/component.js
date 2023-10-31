(function () {
    'use strict'

    BX.namespace('BX.Ozon.SettingsStockList.Vue')

    BX.Ozon.SettingsStockList.Vue = {
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
                    successList: [],
                    errorList: [],
                    isImportStart: false
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
                        if (params.errorList) {
                            state.errorList = params.errorList
                        }
                        if (params.successList) {
                            state.successList = params.successList
                        }
                        if (params.isImportStart !== undefined) {
                            state.isImportStart = params.isImportStart
                        }
                    }
                }
            })
        },

        initComponent: function () {
            BX.BitrixVue.createApp({
                el: '#vue-settings-stock',
                store: this.store,
                data: BX.delegate(function () {
                    return {
                        signedParams: this.signedParams
                    }
                }, this),
                computed:
                        BX.Vuex.mapState({
                            data: state => state.data,
                            errorList: state => state.errorList,
                            successList: state => state.successList,
                            isImportStart: state => state.isImportStart,
                            loc: () => BX.BitrixVue.getFilteredPhrases('DARNEO_OZON_'),
                        }),
                methods: {
                    actionImportStart: function () {
                        let data = this.getDataAjax()
                        data['action'] = 'import'
                        data['signedParamsString'] = this.signedParams
                        BX.Ozon.SettingsStockList.Vue.sendRequest(data)
                    },
                    getDataAjax: function () {
                        let data = {}
                        data['signedParamsString'] = this.signedParams
                        return data
                    },
                },
                template: `
                    <div>
                    <ozon-stock-import
                        v-bind:isImportStart='isImportStart'
                        v-on:actionImportStart='actionImportStart'
                    />
                    <div class='card'>
                        <div class='block_disabled' v-show='isImportStart'></div>
                        <div class='card-body'>
                            <div v-if='data.ITEMS.length'>
                                <ozon-stock-list
                                    v-bind:data='data'
                                />
                            </div>
                        </div>
                        <ozon-modal
                            v-bind:data='errorList'
                            v-bind:title='loc.DARNEO_OZON_VUE_SETTINGS_STOCK_LIST_WARNING'
                        />
                    </div>
                    </div>
                `
            })
        },

        sendRequest: function (data) {
            this.store.commit('changeData', { isImportStart: true })
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
                        if (result.ERROR_LIST && result.ERROR_LIST.length > 0) {
                            this.store.commit('changeData', {
                                successList: [],
                                errorList: result.ERROR_LIST
                            })
                        }
                    }
                    this.store.commit('changeData', { isImportStart: false })
                },
                onfailure: result => {
                    this.store.commit('changeData', { isImportStart: false })
                },
            })
        },
    }
})()