<script>
BX.BitrixVue.component('ozon-attribute-item-modal-item', {
    props: {
        item: {
            type: Object,
            required: true
        },
        iblockPropertyEnum: {
            type: Array,
            required: false
        },
    },
    data: function () {
        return {
            selectedDefault: '',
            attributeId: this.item.PROPERTY_ID,
            attributeValueId: this.item.ID,
            propertyId: '',
            propertyEnumId: this.item.ENUM_SELECTED_ID,
            isSendSave: false,
        }
    },
    computed: {
        loc: function () {
            return BX.BitrixVue.getFilteredPhrases('DARNEO_OZON_')
        },
    },
    watch: {
        propertyEnumId: function () {
            for (let key in this.iblockPropertyEnum) {
                let item = this.iblockPropertyEnum[key]
                if (item.ID === this.propertyEnumId) {
                    this.propertyId = item.PROPERTY_ID
                    break
                }
            }
        },
    },
    methods: {
        getValues: function () {
            let arr = []
            for (let key in this.iblockPropertyEnum) {
                let item = this.iblockPropertyEnum[key]
                let row = {}
                row['id'] = item.ID
                row['text'] = item.VALUE + ' [' + item.ID + ']'
                row['selected'] = item.SELECTED
                arr.push(row)
            }
            return arr
        },
        unset: function () {
            this.propertyEnumId = this.selectedDefault
        },
        actionSetProperty: function () {
            let attributeId = this.attributeId
            let attributeValueId = this.attributeValueId
            let propertyId = this.propertyId
            let propertyEnumId = this.propertyEnumId
            this.$emit('actionSetConnectionEnum', attributeId, attributeValueId, propertyId, propertyEnumId)
        },
        isShowSave: function () {
            return this.item.ENUM_SELECTED_ID !== this.propertyEnumId
        },
    },
    template: `
        <tr>
        <td class='text-start'>
            <div class='d-flex center-block'>
                <div class='img-block' v-if='item.PICTURE.length'>
                    <img v-bind:src='item.PICTURE' class='img-row' alt=''/>
                </div>
                {{ item.NAME }}
                <span v-if='item.INFO.length' class='text-muted m-l-10'> [{{ item.INFO }}]</span>
            </div>
        </td>
        <td class='text-start'>
            <div class='d-flex'>
                <div class='w-100'>
                    <darneo-ozon-select
                        v-bind:options='getValues()'
                        v-bind:value='propertyEnumId'
                        v-bind:placeholder='loc.DARNEO_OZON_VUE_PRODUCT_ATTRIBUTE_PLACEHOLDER_SELECT_VALUE'
                        v-on:input='propertyEnumId = $event'
                    />
                </div>
                <a class='m-2' href='javascript:void(0)' v-on:click='unset()'
                   v-show='propertyEnumId !== selectedDefault'>
                    <i class='ki-duotone ki-cross-square fs-2x'>
                        <i class='path1'></i>
                        <i class='path2'></i>
                    </i>
                </a>
            </div>
            <div class='col-md-12 mt-2'>
                <button type='button' class='btn btn-primary btn-xs fs-8' v-show='isShowSave()'
                        v-on:click='actionSetProperty()'>
                    <span>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_ATTRIBUTE_BUTTON_SAVE') }}</span>
                    <i class='fa fa-spin fa-spinner' v-show='isSendSave'></i>
                </button>
            </div>
        </td>
        </tr>
    `,
})
</script>