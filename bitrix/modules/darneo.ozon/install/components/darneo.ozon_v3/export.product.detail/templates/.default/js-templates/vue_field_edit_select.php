<script>
BX.BitrixVue.component('ozon-field-edit-select', {
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
            selectValue: '',
            isSendSave: false,
            selectedDefault: 0,
        }
    },
    computed: {
        loc: function () {
            return BX.BitrixVue.getFilteredPhrases('DARNEO_OZON_')
        },
    },
    watch: {
        data: function () {
            this.initEditValue()
            this.isSendSave = false
        },
    },
    mounted: function () {
        this.$nextTick(function () {
            this.initEditValue()
        })
    },
    methods: {
        initEditValue: function () {
            if (this.data.VALUE) {
                for (let key in this.data.VALUE) {
                    let item = this.data.VALUE[key]
                    if (item.SELECTED) {
                        this.selectValue = item.ID
                    }
                }
            }
        },
        actionUpdateField: function () {
            if (!this.isSendSave) {
                this.isSendSave = true
                let data = {}
                data[this.code] = this.selectValue
                data['signedParamsString'] = this.data.HIDDEN
                this.$emit('actionUpdateField', data)
            }
        },
        showBlock: function () {
            if (!this.isSendSave) {
                this.$emit('showBlock')
            }
        },
        getValues: function () {
            let arr = []
            for (let key in this.data.VALUE) {
                let item = this.data.VALUE[key]
                let row = {}
                row['id'] = item.ID
                row['text'] = item.NAME + ' [' + item.ID + ']'
                row['selected'] = item.SELECTED
                arr.push(row)
            }
            return arr
        },
    },
    template: `
        <div class='detail-edit p-relative'>
        <div v-bind:class='{disabled:isSendSave}'>
            <div class='d-flex flex-center'>
                <div class='w-100'>
                    <darneo-ozon-select
                        v-bind:options='getValues()'
                        v-bind:value='selectValue'
                        v-bind:placeholder='loc.DARNEO_OZON_VUE_PRODUCT_DETAIL_PLACEHOLDER_SELECT_VALUE'
                        v-on:input='selectValue = $event'
                    />
                </div>
                <a href='javascript:void(0)' class='m-2' v-on:click='selectValue=selectedDefault' v-show='selectValue !== selectedDefault'>
                    <i class='ki-duotone ki-cross-square fs-2x'>
                        <i class='path1'></i>
                        <i class='path2'></i>
                    </i>
                </a>
            </div>
            <div class='position-relative mb-5 mt-2'>
                <button type='button' class='btn btn-sm fw-bold btn-primary'
                        v-on:click='actionUpdateField()'>
                    <span>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_DETAIL_BUTTON_SAVE') }}</span>
                    <i class='fa fa-spin fa-spinner' v-show='isSendSave'></i>
                </button>
                <button type='button' class='btn btn-sm fw-bold btn-light' v-on:click='showBlock()'>
                    {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_DETAIL_BUTTON_CANCEL') }}
                </button>
            </div>
        </div>
        </div>
    `,
})
</script>