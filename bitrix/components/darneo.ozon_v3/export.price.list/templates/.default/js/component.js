(function () {
    'use strict'

    BX.namespace('BX.Ozon.ExportList.Vue')

    BX.Ozon.ExportList.Vue = {
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
                    errorList: []
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
                    }
                }
            })
        },

        initComponent: function () {
            BX.BitrixVue.createApp({
                el: '#vue-export-list',
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
                            data: state => state.data,
                            errorList: state => state.errorList,
                            successList: state => state.successList,
                            loc: () => BX.BitrixVue.getFilteredPhrases('DARNEO_OZON_'),
                        }),
                watch: {
                    data: function () {
                        this.request.isUpdateList = false
                        this.request.isUpdateRow = false
                    },
                },
                methods: {
                    actionAdd: function (iblockId, name) {
                        this.request.isUpdateList = true
                        let data = this.getDataAjax()
                        data['action'] = 'add'
                        data['iblockId'] = iblockId
                        data['name'] = name
                        data['signedParamsString'] = this.signedParams
                        BX.Ozon.ExportList.Vue.sendRequest(data)
                    },
                    actionDelete: function (rowId) {
                        this.request.isUpdateList = true
                        let data = this.getDataAjax()
                        data['action'] = 'delete'
                        data['rowId'] = rowId
                        data['signedParamsString'] = this.signedParams
                        BX.Ozon.ExportList.Vue.sendRequest(data)
                    },
                    getDataAjax: function () {
                        let data = {}
                        data['signedParamsString'] = this.signedParams
                        return data
                    },
                },
                template: `
                    <div>
                    <ozon-product-list-add
                        v-bind:iblock='data.IBLOCK'
                        v-on:actionAdd='actionAdd'
                    />
                    <div class='card'>
                        <div class='block_disabled' v-show='request.isUpdateList'></div>
                        <div class='card-body'>
                            <ozon-product-list
                                v-bind:data='data'
                                v-bind:request='request'
                                v-on:actionDelete='actionDelete'
                            />
                        </div>
                        <product-modal
                            v-bind:data='errorList'
                            v-bind:title='loc.DARNEO_OZON_VUE_PRICE_LIST_WARNING'
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
                    if (result.REDIRECT) {
                        window.location.href = result.REDIRECT
                    }
                    if (result.DATA_VUE) {
                        this.store.commit('changeData', {
                            data: result.DATA_VUE
                        })
                    }
                    if (result.STATUS === 'ERROR') {
                        if (result.ERROR_LIST && result.ERROR_LIST.length > 0) {
                            this.store.commit('changeData', {
                                successList: [],
                                errorList: result.ERROR_LIST
                            })
                        }
                    }
                },
                onfailure: result => {

                },
            })
        },
    }
})()