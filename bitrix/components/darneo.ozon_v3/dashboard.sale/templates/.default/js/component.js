(function () {
    'use strict'

    BX.namespace('BX.Ozon.DashboardSale.Vue')

    BX.Ozon.DashboardSale.Vue = {
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
                el: '#vue-dashboard-sale',
                store: this.store,
                data: BX.delegate(function () {
                    return {
                        signedParams: this.signedParams,
                        request: {
                            isUpdateList: false
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
                    },
                },
                methods: {
                    getDataAjax: function () {
                        let data = {}
                        data['signedParamsString'] = this.signedParams
                        return data
                    },
                    actionSetPeriod: function (value) {
                        this.request.isUpdateList = true
                        let name = 'period'
                        this.initFilterUrl(name, value)
                        let data = this.getDataAjax()
                        data[name] = value
                        data['action'] = 'filter'
                        BX.Ozon.DashboardSale.Vue.sendRequest(data)
                    },
                    actionSetYear: function (value) {
                        this.request.isUpdateList = true
                        let name = 'year'
                        this.initFilterUrl(name, value)
                        let data = this.getDataAjax()
                        data[name] = value
                        data['action'] = 'filter'
                        BX.Ozon.DashboardSale.Vue.sendRequest(data)
                    },
                    initFilterUrl: function (name, value) {
                        let form = {
                            [name]: value
                        }
                        const params = new URLSearchParams(form)
                        let url = params.toString()
                        history.pushState(null, null, '?' + url)
                    },
                },
                template: `
                    <div style='position: relative'>
                    <ozon-dashboard-filter
                        v-bind:data='data'
                        v-bind:request='request'
                        v-on:actionSetPeriod='actionSetPeriod'
                    />
                    <div class='row g-5 g-xl-8'>
                        <div class='block_disabled' v-show='request.isUpdateList'></div>
                        <div class='col-md-12'>
                            <ozon-dashboard-sale
                                v-bind:data='data'
                                v-bind:request='request'
                                v-on:actionSetYear='actionSetYear'
                            />
                        </div>
                        <div class='col-md-6'>
                            <ozon-dashboard-shop
                                v-bind:data='data'
                                v-bind:request='request'
                                v-on:actionSetYear='actionSetYear'
                            />
                        </div>
                        <div class='col-md-6'>
                            <ozon-dashboard-ozon
                                v-bind:data='data'
                                v-bind:request='request'
                                v-on:actionSetYear='actionSetYear'
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