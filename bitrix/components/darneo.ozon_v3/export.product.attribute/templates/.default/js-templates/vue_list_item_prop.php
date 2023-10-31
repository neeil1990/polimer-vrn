<script>
BX.BitrixVue.component('ozon-attribute-item-prop', {
    props: {
        id: {
            type: Number,
            required: true
        },
        attributeType: {
            type: String,
            required: true
        },
        property: {
            type: Object,
            required: true
        },
        selected: {
            type: Object,
            required: false
        },
    },
    data: function () {
        return {
            selectedDefault: '',
            selectGroup: this.selected.PROPERTY_TYPE,
            selectProp: this.selected.PROPERTY_VALUE,
            selectInput: this.selected.VALUE,
            isSendSave: false,
        }
    },
    computed: {
        loc: function () {
            return BX.BitrixVue.getFilteredPhrases('DARNEO_OZON_')
        },
        propertyGroup: function () {
            if (this.attributeType === 'LIST_MULTI' || this.attributeType === 'LIST') {
                let arr = []
                for (let key in this.property.GROUP) {
                    let item = this.property.GROUP[key]
                    if (item.ID === 'PROP') {
                        arr.push(item)
                    }
                }
                return arr
            }
            return this.property.GROUP
        },
        propertyItems: function () {
            if (this.attributeType === 'LIST_MULTI' || this.attributeType === 'LIST') {
                let arr = { PROP: [] }
                for (let key in this.property.ITEMS.PROP) {
                    let item = this.property.ITEMS.PROP[key]
                    if (item.PROPERTY_TYPE === 'L' || item.PROPERTY_TYPE === 'E') {
                        arr.PROP.push(item)
                    }
                }
                return arr
            }
            return this.property.ITEMS
        },
        select: function () {
            let arr = []
            if (this.selectGroup.length) {
                let items = this.propertyItems[this.selectGroup]
                if (items.length > 0) {
                    for (let key in items) {
                        let item = items[key]
                        let row = {}
                        row['id'] = item.ID
                        if (item.PROPERTY_TYPE_HTML) {
                            row['text'] = item.NAME + ' [' + item.ID + '] ' + ' [' + item.PROPERTY_TYPE_HTML + ']'
                        } else {
                            row['text'] = item.NAME + ' [' + item.ID + ']'
                        }
                        row['selected'] = item.ID === this.selectProp
                        arr.push(row)
                    }
                }
            }
            return arr
        },
    },
    watch: {
        selectGroup: function () {
            this.selectProp = ''
            this.selectInput = ''
        },
    },
    methods: {
        getValues: function (data) {
            let arr = []
            for (let key in data) {
                let item = data[key]
                let row = {}
                row['id'] = item.ID
                row['text'] = item.NAME
                row['selected'] = item.SELECTED
                arr.push(row)
            }
            return arr
        },
        actionSetProperty: function () {
            let propertyId = this.id
            let propertyType = this.selectGroup
            let propertyValue = this.selectProp
            let value = this.selectInput
            this.$emit('actionSetProperty', propertyId, propertyType, propertyValue, value)
        },
        isShowSave: function () {
            let checkPropGroup = this.selected.PROPERTY_TYPE !== this.selectGroup
            let checkPropValue = this.selected.PROPERTY_VALUE !== this.selectProp
            let checkValue = this.selected.VALUE !== this.selectInput

            return checkPropGroup || checkPropValue || checkValue
        },
    },
    template: `
        <div class='row'>
        <div class='col-md-4'>
            <div class='d-flex'>
                <div class='w-100'>
                    <darneo-ozon-select
                        v-bind:options='getValues(propertyGroup)'
                        v-bind:value='selectGroup'
                        v-bind:placeholder='loc.DARNEO_OZON_VUE_PRODUCT_ATTRIBUTE_PLACEHOLDER_SELECT_TYPE'
                        v-on:input='selectGroup = $event'
                    />
                </div>
                <a class='m-2' href='javascript:void(0)' v-on:click='selectGroup=selectedDefault'
                   v-show='selectGroup !== selectedDefault'>
                    <i class='ki-duotone ki-cross-square fs-2x'>
                        <i class='path1'></i>
                        <i class='path2'></i>
                    </i>
                </a>
            </div>
        </div>
        <div class='col-md-8'>
            <div class='d-flex' v-if='select.length > 0'>
                <div class='w-100'>
                    <darneo-ozon-select
                        v-bind:options='select'
                        v-bind:value='selectProp'
                        v-bind:placeholder='loc.DARNEO_OZON_VUE_PRODUCT_ATTRIBUTE_PLACEHOLDER_SELECT_VALUE'
                        v-on:input='selectProp = $event'
                    />
                </div>
                <a class='m-2' href='javascript:void(0)' v-on:click='selectProp=selectedDefault'
                   v-show='selectProp !== selectedDefault'>
                    <i class='ki-duotone ki-cross-square fs-2x'>
                        <i class='path1'></i>
                        <i class='path2'></i>
                    </i>
                </a>
            </div>
            <div v-else-if='selectGroup.length > 0'>
                <input type='text' class='form-control' v-model='selectInput'/>
            </div>
        </div>
        <div class='col-md-12 mt-2'>
            <button type='button' class='btn btn-primary btn-xs fs-8' v-show='isShowSave()'
                    v-on:click='actionSetProperty()'>
                <span>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_ATTRIBUTE_BUTTON_SAVE') }}</span>
                <i class='fa fa-spin fa-spinner' v-show='isSendSave'></i>
            </button>
        </div>
        </div>
    `,
})
</script>