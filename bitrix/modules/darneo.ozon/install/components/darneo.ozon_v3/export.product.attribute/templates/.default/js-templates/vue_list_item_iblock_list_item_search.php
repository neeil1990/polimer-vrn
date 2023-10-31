<script>
BX.BitrixVue.component('ozon-attribute-item-iblock-list-item-search', {
    props: {
        attributeId: {
            type: String,
            required: true
        },
        helper: {
            type: String,
            required: false
        },
    },
    data: function () {
        return {
            textSearch: '',
            dataSearch: {},
            pause: {
                startTime: 1,
                currentTime: 0,
                timer: null
            },
            isStartSearch: false,
            dataSave: {
                id: ''
            }
        }
    },
    computed: {
        loc: function () {
            return BX.BitrixVue.getFilteredPhrases('DARNEO_OZON_')
        },
        dataSearchArray: function () {
            if (this.dataSearch.SEARCH !== undefined && this.textSearch !== '') {
                return this.dataSearch.SEARCH.DATA
            }
            return []
        },
        isEmptyResult: function () {
            return this.dataSearch.SEARCH !== undefined && this.textSearch !== ''
        },
    },
    watch: {
        textSearch: function () {
            this.isStartSearch = true
            this.dataSearch = {}
            this.startTimer()
        },
        'pause.currentTime': function (time) {
            if (time === 0) {
                this.stopTimer()
                this.dataSearch = BX.Ozon.ExportAttribute.Vue.actionSearchEnumOzon(this.attributeId, this.textSearch)
                this.isStartSearch = false
            }
        },
    },
    methods: {
        actionSave: function (id) {
            this.dataSave.id = id
            this.$emit('actionSetConnectionEnum', id)
        },
        isDisableSave: function (id) {
            if (this.dataSave.id !== '') {
                return Number(this.dataSave.id) !== Number(id)
            }
            return false
        },
        startTimer: function () {
            this.stopTimer()
            this.pause.currentTime = this.pause.startTime
            this.pause.timer = setInterval(() => {
                this.pause.currentTime--
            }, 1000)
        },
        stopTimer: function () {
            clearTimeout(this.pause.timer)
        },
        actionUnset: function () {
            this.textSearch = ''
        },
        isShowSpinner: function () {
            return this.isStartSearch && this.textSearch.length > 0
        },
        isShowReset: function () {
            return this.textSearch.length > 0 && !this.isStartSearch
        },
    },
    template: `
        <div>
        <div class='d-flex p-relative'>
            <input
                class='form-control'
                type='text'
                v-bind:placeholder='loc.DARNEO_OZON_VUE_PRODUCT_ATTRIBUTE_PLACEHOLDER_INPUT_SEARCH'
                v-model='textSearch'>
            <i class='fa fa-spin fa-spinner spinner-search' v-show='isShowSpinner()'></i>
            <a v-show='isShowReset()' class='m-2' href='javascript:void(0)' v-on:click='actionUnset()'>
                <i class='ki-duotone ki-cross-square fs-2x'>
                    <i class='path1'></i>
                    <i class='path2'></i>
                </i>
            </a>
        </div>
        <div>
            <small class='dashed helper' v-html='helper' v-on:click='textSearch=helper'></small>
        </div>
        <div v-if='dataSearchArray.length > 0' class='m-t-10'>
            <ul class='list-group'>
                <ozon-attribute-item-iblock-list-item-search-item
                    v-for='item in dataSearchArray' :key='Number(item.ID)'
                    v-bind:item='item'
                    v-bind:isSaveDisable='isDisableSave(item.ID)'
                    v-on:actionSave='actionSave'
                />
            </ul>
        </div>
        <div v-else-if='isEmptyResult' class='m-t-10'>
            <ul class='list-group'>
                <li class='list-group-item'>
                    {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_ATTRIBUTE_NOT_FOUND') }}
                </li>
            </ul>
        </div>
        </div>
    `,
})
</script>