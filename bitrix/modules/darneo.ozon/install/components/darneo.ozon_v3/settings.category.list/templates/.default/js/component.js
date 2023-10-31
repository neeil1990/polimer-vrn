(function () {
    'use strict'

    BX.namespace('BX.Ozon.SettingsCategoryList.Vue')

    BX.Ozon.SettingsCategoryList.Vue = {
        init: function (parameters) {
            this.ajaxUrl = parameters.ajaxUrl || ''
            this.ajaxImportUrl = parameters.ajaxImportUrl || ''
            this.signedParams = parameters.signedParams
            this.tree = parameters.tree || {}
            this.selected = parameters.selected || {}

            this.initStore()
            this.initComponent()
        },
        initStore: function () {
            this.store = BX.Vuex.store({
                state: {
                    tree: this.tree,
                    selected: this.selected,
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
                        if (params.selected) {
                            state.selected = params.selected
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
                el: '#vue-settings-category',
                store: this.store,
                data: BX.delegate(function () {
                    return {
                        signedParams: this.signedParams,
                        selectedLevel1: Number(this.selected.LEVEL_1),
                        selectedLevel2: Number(this.selected.LEVEL_2),
                        selectedLevel3: Number(this.selected.LEVEL_3),
                        request: {
                            isUpdateList: false
                        },
                        dataSection: {
                            level3: '',
                            title: '',
                            data: [],
                        },
                    }
                }, this),
                computed:
                        BX.Vuex.mapState({
                            tree: state => state.tree,
                            selected: state => state.selected,
                            errorList: state => state.errorList,
                            successList: state => state.successList,
                            isImportStart: state => state.isImportStart,
                        }),
                watch: {
                    tree: function () {
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
                        BX.Ozon.SettingsCategoryList.Vue.sendImport(data)
                    },
                    setLevel: function (level1, level2, level3) {
                        this.selectedLevel1 = level1
                        this.selectedLevel2 = level2
                        this.selectedLevel3 = level3
                        this.initFilterUrl()
                        this.actionReload()
                    },
                    actionCategoryDisable: function (categoryId, value) {
                        this.request.isUpdateList = true
                        let data = this.getDataAjax()
                        data['categoryId'] = categoryId
                        data['value'] = value
                        data['action'] = 'disable'
                        BX.Ozon.SettingsCategoryList.Vue.sendRequest(data)
                    },
                    actionCategoryDisableAll: function (categoryIds) {
                        this.request.isUpdateList = true
                        let data = this.getDataAjax()
                        data['categoryIds'] = categoryIds
                        data['action'] = 'group_disable'
                        BX.Ozon.SettingsCategoryList.Vue.sendRequest(data)
                    },
                    actionCategoryActiveAll: function (categoryIds) {
                        this.request.isUpdateList = true
                        let data = this.getDataAjax()
                        data['categoryIds'] = categoryIds
                        data['action'] = 'group_active'
                        BX.Ozon.SettingsCategoryList.Vue.sendRequest(data)
                    },
                    actionReload: function () {
                        this.request.isUpdateList = true
                        let data = this.getDataAjax()
                        data['action'] = 'tree'
                        BX.Ozon.SettingsCategoryList.Vue.sendRequest(data)
                    },
                    actionShowModal: function (level3, title, data) {
                        this.dataSection.level3 = level3
                        this.dataSection.title = title
                        this.dataSection.data = data
                    },
                    actionCloseModal: function () {
                        this.dataSection.level3 = ''
                    },
                    initFilterUrl: function () {
                        let form = {
                            level1: this.selectedLevel1,
                            level2: this.selectedLevel2,
                            level3: this.selectedLevel3,
                        }
                        const params = new URLSearchParams(form)
                        let url = params.toString()
                        history.pushState(null, null, '?' + url)
                    },
                    getDataAjax: function () {
                        let data = {}
                        data['level1'] = this.selectedLevel1
                        data['level2'] = this.selectedLevel2
                        data['level3'] = this.selectedLevel3
                        data['signedParamsString'] = this.signedParams
                        return data
                    },
                },
                template: `
                    <div>
                    <ozon-category-import
                        v-bind:isImportStart='isImportStart'
                        v-on:actionImportStart='actionImportStart'
                    />
                    <div class='card'>
                        <div class='block_disabled' v-show='request.isUpdateList'></div>
                        <div class='card-body'>
                            <ozon-category-tree
                                v-bind:tree='tree'
                                v-bind:selected='selected'
                                v-on:setLevel='setLevel'
                                v-on:actionCategoryDisable='actionCategoryDisable'
                                v-on:actionCategoryDisableAll='actionCategoryDisableAll'
                                v-on:actionCategoryActiveAll='actionCategoryActiveAll'
                                v-on:actionShowModal='actionShowModal'
                            />
                        </div>
                        <div v-if='dataSection.level3 > 0'>
                            <ozon-category-section-list
                                v-bind:level3='dataSection.level3'
                                v-bind:title='dataSection.title'
                                v-bind:data='dataSection.data'
                                v-on:actionCloseModal='actionCloseModal'
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
                        if (result.DATA_VUE.TREE) {
                            this.store.commit('changeData', {
                                tree: result.DATA_VUE.TREE,
                                selected: result.DATA_VUE.SELECTED
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
    }
})()