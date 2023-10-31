<script>
BX.BitrixVue.component('ozon-field-edit-filter', {
    props: {
        data: {
            type: Object,
            required: false
        },
        code: {
            type: String,
            required: true
        },
    },
    data: function () {
        return {
            filterList: this.data.VALUE.FILTER_LIST,
            countNew: 0,
            isSendSave: false
        }
    },
    computed: {
        loc: function () {
            return BX.BitrixVue.getFilteredPhrases('DARNEO_OZON_')
        },
    },
    watch: {
        data: function () {
            this.isSendSave = false
        },
    },
    methods: {
        addFilterData: function () {
            this.countNew++
            let newRow = {
                ID: 'n' + this.countNew,
                PROP_ID: 0,
                COMPARE_TYPE: 0,
                COMPARE_VALUE: 0,
            }
            this.filterList.push(newRow)
        },
        updateFilterData: function (item) {
            for (let key in this.filterList) {
                let formItem = this.filterList[key]
                if (item.ID === formItem.ID) {
                    this.filterList[key] = item
                }
            }
        },
        deleteFilterData: function (item) {
            let data = []
            for (let key in this.filterList) {
                let formItem = this.filterList[key]
                if (item.ID !== formItem.ID) {
                    data.push(formItem)
                }
            }
            this.filterList = data
        },
        actionUpdateField: function () {
            if (!this.isSendSave) {
                this.isSendSave = true
                let data = {}

                data[this.code] = this.filterList
                data['signedParamsString'] = this.data.HIDDEN
                this.$emit('actionUpdateField', data)
            }
        },
        showBlock: function () {
            if (!this.isSendSave) {
                this.$emit('showBlock')
            }
        },
    },
    template: `
        <div class='detail-edit p-relative'>
        <div v-bind:class='{disabled:isSendSave}'>
            <div v-for='(item, index) in filterList' :key='String(item.ID)'>
                <ozon-field-edit-filter-item
                    v-bind:filter='item'
                    v-bind:propertyList='data.VALUE.PROPERTY_LIST'
                    v-bind:enumList='data.VALUE.ENUM_LIST'
                    v-bind:type='data.VALUE.TYPE'
                    v-on:updateFilterData='updateFilterData'
                    v-on:deleteFilterData='deleteFilterData'
                />
                <h4 class='text-center list-add' v-if='index + 1 !== filterList.length'>
                    <span class='badge badge-light text-dark'>
                        {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_STOCK_DETAIL_SETTINGS_AND') }}
                    </span>
                </h4>
            </div>

            <div class='m-t-20 m-b-20 text-end'>
                <a href='javascript:void(0)' v-on:click='addFilterData()'>
                    <i class='ki-duotone ki-plus-square fs-2x'>
                        <i class='path1'></i>
                        <i class='path2'></i>
                        <i class='path3'></i>
                    </i>
                </a>
            </div>
            <div class='position-relative mb-5 mt-2'>
                <button type='button' class='btn btn-sm fw-bold btn-primary'
                        v-on:click='actionUpdateField()'>
                    <span>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_STOCK_DETAIL_BUTTON_SAVE') }}</span>
                    <i class='fa fa-spin fa-spinner' v-show='isSendSave'></i>
                </button>
                <button type='button' class='btn btn-sm fw-bold btn-light' v-on:click='showBlock()'>
                    {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_STOCK_DETAIL_BUTTON_CANCEL') }}
                </button>
            </div>
        </div>
        </div>
    `,
})
</script>