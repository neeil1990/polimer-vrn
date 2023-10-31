(function () {
    'use strict'

    BX.namespace('BX.Ozon.OrderCatalogList.Vue')

    BX.Ozon.OrderCatalogList.Vue = {
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
                el: '#vue-data-catalog',
                store: this.store,
                data: BX.delegate(function () {
                    return {
                        signedParams: this.signedParams,
                        request: {
                            isUpdateList: false
                        },
                        filter: ''
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
                    actionNextPage: function (page) {
                        let data = this.getDataAjax()
                        this.request.isUpdateList = true
                        data['action'] = 'list'
                        data['page'] = page
                        BX.Ozon.OrderCatalogList.Vue.sendRequest(data)
                    },
                    getDataAjax: function () {
                        let data = {}
                        data['signedParamsString'] = this.signedParams
                        data['filter'] = this.filter
                        return data
                    },
                    actionReload: function () {
                        this.request.isUpdateList = true
                        let data = this.getDataAjax()
                        data['action'] = 'list'
                        BX.Ozon.OrderCatalogList.Vue.sendRequest(data)
                    },
                    setFilter: function (section) {
                        this.filter = section
                        this.initFilterUrl()
                        this.actionReload()
                    },
                    initFilterUrl: function () {
                        let form = {
                            filter: this.filter
                        }
                        const params = new URLSearchParams(form)
                        let url = params.toString()
                        history.pushState(null, null, '?' + url)
                    },
                },
                template: `
                <div>
                    <div class='card'>
                    <div class='block_disabled' v-show='request.isUpdateList'></div>
                        <div class='card-body'>
                            <ozon-catalog-filter 
                                v-bind:data='data.FILTER'
                                v-bind:filter='filter'
                                v-on:setFilter='setFilter'
                            />
                            <div v-if='data.LIST.length'>
                                <ozon-catalog-list
                                    v-bind:data='data'
                                    v-bind:page='Number(data.PAGE)'
                                    v-bind:finalPage='Boolean(data.FINAL_PAGE)'
                                    v-on:actionNextPage='actionNextPage'
                                />
                            </div>
                        </div>
                        <ozon-modal
                            v-bind:data='errorList'
                            v-bind:title='loc.DARNEO_OZON_VUE_ORDER_LIST_WARNING'
                        />
                    </div>
                </div>
                `
            })
        },

        updateList: function () {
            let data = {}
            data['action'] = 'list'
            data['signedParamsString'] = this.signedParams
            BX.Ozon.OrderCatalogList.Vue.sendRequest(data)
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