(function () {
    'use strict'

    BX.namespace('BX.Ozon.ExportSection.Vue')

    BX.Ozon.ExportSection.Vue = {
        init: function (parameters) {
            this.ajaxUrl = parameters.ajaxUrl || ''
            this.signedParams = parameters.signedParams
            this.section = parameters.section || {}
            this.tree = parameters.tree || {}
            this.filter = parameters.filter || {}

            this.initStore()
            this.initComponent()
        },
        initStore: function () {
            this.store = BX.Vuex.store({
                state: {
                    section: this.section,
                    tree: this.tree,
                    filter: this.filter,
                },
                actions: {
                    change(store, payload) {
                        store.commit('changeData', payload)
                    }
                },
                mutations: {
                    changeData(state, params) {
                        if (params.section) {
                            state.section = params.section
                        }
                        if (params.tree) {
                            state.tree = params.tree
                        }
                        if (params.filter) {
                            state.filter = params.filter
                        }
                    }
                }
            })
        },

        initComponent: function () {
            BX.BitrixVue.createApp({
                el: '#vue-export-section',
                store: this.store,
                data: BX.delegate(function () {
                    return {
                        signedParams: this.signedParams,
                        request: {
                            isUpdateList: false,
                            isUpdateTree: false,
                            isSaveTree: false
                        },
                        iblockId: Number(this.filter.SELECTED),
                        popup: {
                            show: false,
                            title: '',
                            iblockId: 0,
                            sectionId: 0,
                        }
                    }
                }, this),
                computed:
                        BX.Vuex.mapState({
                            section: state => state.section,
                            tree: state => state.tree,
                            filter: state => state.filter,
                        }),
                watch: {
                    section: function () {
                        this.clearRequest()
                    },
                    tree: function () {
                        this.clearRequest()
                    },
                    filter: function () {
                        this.clearRequest()
                    },
                    iblockId: function () {
                        this.actionReloadData()
                    },
                },
                methods: {
                    clearRequest: function () {
                        this.request.isUpdateTree = false
                        this.request.isUpdateList = false
                        this.request.isSaveTree = false
                    },
                    setPopupData: function (title, iblockId, sectionId) {
                        this.popup.title = title
                        this.popup.iblockId = Number(iblockId)
                        this.popup.sectionId = Number(sectionId)
                        this.popup.show = true
                    },
                    actionCloseModal: function () {
                        this.popup.title = ''
                        this.popup.iblockId = 0
                        this.popup.sectionId = 0
                        this.popup.show = false
                    },
                    setIblockId: function (iblockId) {
                        this.iblockId = iblockId
                    },
                    actionReloadData: function () {
                        this.request.isUpdateList = true
                        this.initFilterUrl()
                        let data = this.getDataAjax()
                        data['action'] = 'list'
                        BX.Ozon.ExportSection.Vue.sendRequest(data)
                    },
                    initFilterUrl: function () {
                        let form = {
                            iblockId: this.iblockId
                        }
                        const params = new URLSearchParams(form)
                        let url = params.toString()
                        history.pushState(null, null, '?' + url)
                    },
                    getDataAjax: function () {
                        let data = {}
                        data['signedParamsString'] = this.signedParams
                        data['iblockId'] = this.iblockId
                        return data
                    },
                    actionReloadTree: function (level1, level2, level3) {
                        this.request.isUpdateTree = true
                        let data = this.getDataAjax()
                        data['level1'] = level1
                        data['level2'] = level2
                        data['level3'] = level3
                        data['action'] = 'tree'
                        BX.Ozon.ExportSection.Vue.sendRequest(data)
                    },
                    actionSetCategory: function (iblockId, sectionId, level1, level2, level3) {
                        this.request.isSaveTree = true
                        let data = this.getDataAjax()
                        data['iblockId'] = iblockId
                        data['sectionId'] = sectionId
                        data['level1'] = level1
                        data['level2'] = level2
                        data['level3'] = level3
                        data['action'] = 'setCategory'
                        BX.Ozon.ExportSection.Vue.sendRequest(data)
                    },
                    actionDeleteCategory: function (iblockId, sectionId) {
                        this.request.isUpdateList = true
                        let data = this.getDataAjax()
                        data['iblockId'] = iblockId
                        data['sectionId'] = sectionId
                        data['action'] = 'deleteCategory'
                        BX.Ozon.ExportSection.Vue.sendRequest(data)
                    },
                },
                template: `
                    <div class='card'>
                        <div class='block_disabled' v-show='request.isUpdateList'></div>
                        <div class='card-body'>
                            <div v-if='section.IBLOCK_ID'>
                            <section-list 
                                v-bind:data='section'
                                v-on:setPopupData='setPopupData'
                                v-on:actionDeleteCategory='actionDeleteCategory'
                            />
                            </div>
                            <div v-if='popup.show'>
                                <select-category 
                                    v-bind:tree='tree'
                                    v-bind:iblockId='popup.iblockId'
                                    v-bind:sectionId='popup.sectionId'
                                    v-bind:title='popup.title'
                                    v-bind:request='request'
                                    v-on:actionReloadTree='actionReloadTree'
                                    v-on:actionSetCategory='actionSetCategory'
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
                        if (result.DATA_VUE) {
                            this.store.commit('changeData', {
                                section: result.DATA_VUE.SECTION,
                                tree: result.DATA_VUE.TREE,
                                filter: result.DATA_VUE.FILTER,
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