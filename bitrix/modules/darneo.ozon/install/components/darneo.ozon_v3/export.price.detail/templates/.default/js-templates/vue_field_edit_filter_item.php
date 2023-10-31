<script>
BX.BitrixVue.component('ozon-field-edit-filter-item', {
    props: {
        filter: {
            type: Object,
            required: false
        },
        propertyList: {
            type: Array,
            required: false
        },
        enumList: {
            type: Array,
            required: false
        },
        type: {
            type: Object,
            required: true
        },
    },
    data: function () {
        return {
            form: {
                ID: this.filter.ID,
                PROP_ID: this.filter.PROP_ID,
                COMPARE_TYPE: this.filter.COMPARE_TYPE,
                COMPARE_VALUE: this.filter.COMPARE_VALUE,
            },
            selectDefault: 0,
            typeProperty: ''
        }
    },
    computed: {
        loc: function () {
            return BX.BitrixVue.getFilteredPhrases('DARNEO_OZON_')
        },
        filterEnumList: function () {
            let data = []
            if (this.form.PROP_ID.length > 0 && this.enumList.length > 0) {
                for (let key in this.enumList) {
                    let item = this.enumList[key]
                    if (Number(this.form.PROP_ID) === Number(item.PROPERTY_ID)) {
                        data.push(item)
                    }
                }
            }
            return data
        },
    },
    watch: {
        'form.PROP_ID': function () {
            this.form.COMPARE_TYPE = ''
            this.form.COMPARE_VALUE = ''
            this.setTypeValue()
            this.updateFilterData()
        },
        'form.COMPARE_TYPE': function () {
            this.updateFilterData()
        },
        'form.COMPARE_VALUE': function () {
            this.updateFilterData()
        },
    },
    mounted: function () {
        this.$nextTick(function () {
            this.setTypeValue()
            this.updateFilterData()
        })
    },
    methods: {
        updateFilterData: function () {
            this.$emit('updateFilterData', this.form)
        },
        deleteFilterData: function () {
            this.$emit('deleteFilterData', this.form)
        },
        setTypeValue: function () {
            if (this.propertyList) {
                for (let key in this.propertyList) {
                    let item = this.propertyList[key]
                    if (Number(this.form.PROP_ID) === Number(item.ID)) {
                        this.typeProperty = item.PROPERTY_TYPE
                        break
                    }
                }
            }
        },
        getValues: function (data) {
            let arr = []
            for (let key in data) {
                let item = data[key]
                let row = {}
                row['id'] = item.ID
                row['text'] = item.NAME + ' [' + item.ID + ']'
                arr.push(row)
            }
            return arr
        },

        isTypeString: type => type === 'S',
        isTypeNumber: type => type === 'N',
        isTypeEnum: type => type === 'L',
        isTypeElement: type => type === 'E',
    },
    template: `
        <div class='detail-edit p-relative' style='margin-bottom: 0.5rem'>
        <div class='d-flex flex-center'>

            <a href='javascript:void(0)' v-on:click='deleteFilterData()'>
                <i class='ki-duotone ki-trash-square fs-2x'>
                    <i class='path1'></i>
                    <i class='path2'></i>
                    <i class='path3'></i>
                    <i class='path4'></i>
                </i>
            </a>

            <div class='w-100'>
                <darneo-ozon-select
                    v-bind:options='getValues(propertyList)'
                    v-bind:value='form.PROP_ID'
                    v-bind:placeholder='loc.DARNEO_OZON_VUE_PRICE_DETAIL_PLACEHOLDER_SELECT_VALUE'
                    v-on:input='form.PROP_ID = $event'
                />
            </div>
            <a class='m-2' href='javascript:void(0)' v-on:click='form.PROP_ID=selectDefault'
               v-show='form.PROP_ID !== selectDefault'>
                <i class='ki-duotone ki-cross-square fs-2x'>
                    <i class='path1'></i>
                    <i class='path2'></i>
                </i>
            </a>
        </div>

        <div v-show='typeProperty.length > 0' class='prop-compare'>
            <template v-if='isTypeString(typeProperty)'>
                    <span class='field-title'>
                        {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRICE_DETAIL_VALUE_STRING') }}
                    </span>
                <div class='row'>
                    <div class='col-md-12'>
                        <darneo-ozon-select
                            v-bind:options='getValues(type.STRING)'
                            v-bind:value='form.COMPARE_TYPE'
                            v-bind:placeholder='loc.DARNEO_OZON_VUE_PRICE_DETAIL_PLACEHOLDER_SELECT_VALUE'
                            v-on:input='form.COMPARE_TYPE = $event'
                        />
                    </div>
                    <div class='col-md-12 mt-2'>
                        <input type='text' class='form-control' v-model='form.COMPARE_VALUE'/>
                    </div>
                </div>
            </template>
            <template v-if='isTypeNumber(typeProperty)'>
                    <span class='field-title'>
                        {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRICE_DETAIL_VALUE_INT') }}
                    </span>
                <div class='row'>
                    <div class='col-md-12'>
                        <darneo-ozon-select
                            v-bind:options='getValues(type.NUMBER)'
                            v-bind:value='form.COMPARE_TYPE'
                            v-bind:placeholder='loc.DARNEO_OZON_VUE_PRICE_DETAIL_PLACEHOLDER_SELECT_VALUE'
                            v-on:input='form.COMPARE_TYPE = $event'
                        />
                    </div>
                    <div class='col-md-12 mt-2'>
                        <input type='text' class='form-control' v-model='form.COMPARE_VALUE'/>
                    </div>
                </div>
            </template>
            <template v-if='isTypeEnum(typeProperty)'>
                    <span class='field-title'>
                        {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRICE_DETAIL_VALUE_LIST') }}
                    </span>
                <div class='row'>
                    <div class='col-md-12'>
                        <darneo-ozon-select
                            v-bind:options='getValues(type.ENUM)'
                            v-bind:value='form.COMPARE_TYPE'
                            v-bind:placeholder='loc.DARNEO_OZON_VUE_PRICE_DETAIL_PLACEHOLDER_SELECT_VALUE'
                            v-on:input='form.COMPARE_TYPE = $event'
                        />
                    </div>
                    <div class='col-md-12 mt-2'>
                        <darneo-ozon-select
                            v-bind:options='getValues(filterEnumList)'
                            v-bind:value='form.COMPARE_VALUE'
                            v-bind:placeholder='loc.DARNEO_OZON_VUE_PRICE_DETAIL_PLACEHOLDER_SELECT_VALUE'
                            v-on:input='form.COMPARE_VALUE = $event'
                        />
                    </div>
                </div>
            </template>
            <template v-if='isTypeElement(typeProperty)'>
                <span class='field-title'>
                    {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRICE_DETAIL_VALUE_ELEMENT') }}
                </span>
                <div class='row'>
                    <div class='col-md-12'>
                        <darneo-ozon-select
                            v-bind:options='getValues(type.ELEMENT)'
                            v-bind:value='form.COMPARE_TYPE'
                            v-bind:placeholder='loc.DARNEO_OZON_VUE_PRICE_DETAIL_PLACEHOLDER_SELECT_VALUE'
                            v-on:input='form.COMPARE_TYPE = $event'
                        />
                    </div>
                    <div class='col-md-12 mt-2'>
                        <div class='col-md-12 mt-2'>
                            <input type='text' class='form-control' v-model='form.COMPARE_VALUE'/>
                        </div>
                    </div>
                </div>
            </template>
        </div>


        </div>
    `,
})
</script>