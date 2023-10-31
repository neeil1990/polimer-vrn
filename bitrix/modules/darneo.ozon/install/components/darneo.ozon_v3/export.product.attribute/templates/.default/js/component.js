(function () {
    'use strict'

    BX.namespace('BX.Ozon.ExportAttribute.Vue')

    BX.Ozon.ExportAttribute.Vue = {
        init: function (parameters) {
            this.ajaxUrl = parameters.ajaxUrl || ''
            this.ajaxImportUrl = parameters.ajaxImportUrl || ''
            this.signedParams = parameters.signedParams
            this.tree = parameters.tree || []
            this.property = parameters.property || {}
            this.attribute = parameters.attribute || {}
            this.selected = parameters.selected || {}

            this.initStore()
            this.initComponent()
        },
        initStore: function () {
            this.store = BX.Vuex.store({
                state: {
                    tree: this.tree,
                    property: this.property,
                    attribute: this.attribute,
                    selected: this.selected,
                    searchAttributeValue: '',
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
                        if (params.tree) {
                            state.tree = params.tree
                        }
                        if (params.property) {
                            state.property = params.property
                        }
                        if (params.attribute) {
                            state.attribute = params.attribute
                        }
                        if (params.selected) {
                            state.selected = params.selected
                        }
                        if (params.errorList) {
                            state.errorList = params.errorList
                        }
                        if (params.searchAttributeValue !== undefined) {
                            state.searchAttributeValue = params.searchAttributeValue
                        }
                        if (params.successList) {
                            state.successList = params.successList
                        }
                        if (params.isImportStart !== undefined) {
                            state.isImportStart = params.isImportStart
                        }
                    }
                },
                getters: {
                    selected(state) {
                        return state.selected
                    }
                }
            })
        },

        initComponent: function () {
            BX.BitrixVue.createApp({
                el: '#vue-export-attribute',
                store: this.store,
                data: BX.delegate(function () {
                    return {
                        signedParams: this.signedParams,
                        connectionSectionTree: this.selected.CONNECTION_SECTION_TREE_ID,
                        request: {
                            isUpdateList: false
                        },
                    }
                }, this),
                computed:
                        BX.Vuex.mapState({
                            tree: state => state.tree,
                            property: state => state.property,
                            attribute: state => state.attribute,
                            selected: state => state.selected,
                            searchAttributeValue: state => state.searchAttributeValue,
                            errorList: state => state.errorList,
                            successList: state => state.successList,
                            isImportStart: state => state.isImportStart,
                            loc: () => BX.BitrixVue.getFilteredPhrases('DARNEO_OZON_'),
                        }),
                watch: {
                    tree: function () {
                        this.request.isUpdateList = false
                    },
                    attribute: function () {
                        this.request.isUpdateList = false
                    },
                    isImportStart: function (val, old) {
                        if (old && !val) {
                            this.actionReload()
                        }
                    },
                },
                methods: {
                    actionImportStart: function () {
                        this.request.isUpdateList = true
                        let data = this.getDataAjax()
                        data['action'] = 'import'
                        BX.Ozon.ExportAttribute.Vue.sendImport(data)
                    },
                    setFilter: function (connectionSectionTree) {
                        this.connectionSectionTree = connectionSectionTree
                        this.initFilterUrl()
                        this.actionReload()
                    },
                    actionReload: function () {
                        this.request.isUpdateList = true
                        let data = this.getDataAjax()
                        data['action'] = 'tree'
                        BX.Ozon.ExportAttribute.Vue.sendRequest(data)
                    },
                    actionNextPage: function (propertyId, page) {
                        let data = this.getDataAjax()
                        data['propertyId'] = propertyId
                        data['page'] = page
                        data['action'] = 'list'
                        BX.Ozon.ExportAttribute.Vue.sendRequest(data)
                    },
                    actionSetProperty: function (propertyId, propertyType, propertyValue, value) {
                        let data = this.getDataAjax()
                        data['propertyId'] = propertyId
                        data['propertyType'] = propertyType
                        data['propertyValue'] = propertyValue
                        data['value'] = value
                        data['action'] = 'bindProperty'
                        BX.Ozon.ExportAttribute.Vue.sendRequest(data)
                    },
                    actionSetRatio: function (attributeId, ratio) {
                        let data = this.getDataAjax()
                        data['attributeId'] = attributeId
                        data['ratio'] = ratio
                        data['action'] = 'bindRatio'
                        BX.Ozon.ExportAttribute.Vue.sendRequest(data)
                    },
                    actionSetConnectionEnum: function (attributeId, attributeValueId, propertyId, propertyEnumId) {
                        let data = this.getDataAjax()
                        data['attributeId'] = attributeId
                        data['attributeValueId'] = attributeValueId
                        data['propertyId'] = propertyId
                        data['propertyEnumId'] = propertyEnumId
                        data['action'] = 'bindConnectionEnum'
                        BX.Ozon.ExportAttribute.Vue.sendRequest(data)
                    },
                    initFilterUrl: function () {
                        let form = {
                            connectionSectionTree: this.connectionSectionTree
                        }
                        const params = new URLSearchParams(form)
                        let url = params.toString()
                        history.pushState(null, null, '?' + url)
                    },
                    getDataAjax: function () {
                        let data = {}
                        data['connectionSectionTree'] = this.connectionSectionTree
                        data['search'] = this.searchAttributeValue
                        data['signedParamsString'] = this.signedParams
                        return data
                    },
                },
                template: `
                    <div class='card'>
                    <div class='block_disabled' v-show='request.isUpdateList'></div>
                    <div class='card-body'>
                        <ozon-attribute-filter
                            v-bind:tree='tree'
                            v-bind:selected='selected'
                            v-bind:isImportStart='isImportStart'
                            v-on:actionImportStart='actionImportStart'
                            v-on:setFilter='setFilter'
                        />
                        <div v-if='attribute.LIST.length'>
                            <ozon-attribute-list
                                v-bind:data='attribute.LIST'
                                v-bind:property='property'
                                v-on:actionNextPage='actionNextPage'
                                v-on:actionSetProperty='actionSetProperty'
                                v-on:actionSetConnectionEnum='actionSetConnectionEnum'
                                v-on:actionSetRatio='actionSetRatio'
                            />
                        </div>
                    </div>
                    <ozon-modal
                        v-bind:data='errorList'
                        v-bind:title='loc.DARNEO_OZON_VUE_PRODUCT_ATTRIBUTE_WARNING'
                    />
                    </div>
                `
            })
        },

        setSearchAttributeValue: function (text) {
            this.store.commit('changeData', {
                searchAttributeValue: text
            })
        },

        actionSearchEnumOzon: function (attributeId, text) {
            let data = this.getDataAjax()
            data['search'] = text
            data['attributeId'] = attributeId
            data['action'] = 'search'
            return this.sendSearch(data)
        },

        actionDeleteConnectionEnum: function (connectionId) {
            let data = this.getDataAjax()
            data['connectionId'] = connectionId
            data['action'] = 'deleteConnectionEnum'
            return this.sendRequest(data)
        },

        getDataAjax: function () {
            let data = {}
            data['connectionSectionTree'] = this.store.getters.selected.CONNECTION_SECTION_TREE_ID
            data['signedParamsString'] = this.signedParams
            return data
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
                        if (result.DATA_VUE.TREE) {
                            this.store.commit('changeData', {
                                tree: result.DATA_VUE.TREE,
                                attribute: result.DATA_VUE.ATTRIBUTE,
                                property: result.DATA_VUE.PROPERTY,
                                selected: result.DATA_VUE.SELECTED,
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
                },
                onfailure: result => {

                },
            })
        },

        sendImport: function (data) {
            this.store.commit('changeData', { isImportStart: true })
            BX.ajax({
                method: 'POST',
                dataType: 'json',
                url: this.ajaxImportUrl,
                data: data,
                async: true,
                onsuccess: result => {
                    this.store.commit('changeData', { isImportStart: false })
                },
                onfailure: result => {
                    this.store.commit('changeData', { isImportStart: false })
                },
            })
        },

        sendSearch: function (data) {
            let searchData = {}
            BX.ajax({
                method: 'POST',
                dataType: 'json',
                url: this.ajaxUrl,
                data: data,
                async: false,
                onsuccess: result => {
                    searchData = result
                },
                onfailure: result => {

                },
            })
            return searchData
        },
    }
})()