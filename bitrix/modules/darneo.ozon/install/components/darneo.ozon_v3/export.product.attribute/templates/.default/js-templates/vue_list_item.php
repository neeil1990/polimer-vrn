<script>
BX.BitrixVue.component('ozon-attribute-item', {
    props: {
        item: {
            type: Object,
            required: true
        },
        property: {
            type: Object,
            required: true
        },
    },
    data: function () {
        return {
            isHelp: false,
            showCompareOzon: false,
            showCompareIblock: false,
            showRatio: false,
        }
    },
    computed: {
        loc: function () {
            return BX.BitrixVue.getFilteredPhrases('DARNEO_OZON_')
        },
    },
    methods: {
        actionSetProperty: function (propertyId, propertyType, propertyValue, value) {
            this.$emit('actionSetProperty', propertyId, propertyType, propertyValue, value)
        },
        actionSetConnectionEnum: function (attributeId, attributeValueId, propertyId, propertyEnumId) {
            this.$emit('actionSetConnectionEnum', attributeId, attributeValueId, propertyId, propertyEnumId)
        },
        actionSetRatio: function (attributeId, ratio) {
            this.$emit('actionSetRatio', attributeId, ratio)
        },
        actionCloseModal: function () {
            this.showCompareOzon = false
            this.showCompareIblock = false
        },
        actionNextPage: function (page) {
            this.$emit('actionNextPage', this.item.ID, page)
        },
    },
    template: `
        <tr>
        <td class='text-start'>
            <span v-html='item.NAME'></span>
            <span class='text-danger' v-if='item.IS_REQUIRED'> *</span>
            <span class='ui-hint-icon mt-2 c-pointer helper-ico'
                  v-if='!isHelp && item.DESCRIPTION.length > 0' v-on:click='isHelp=!isHelp'></span>
            <p v-show='isHelp'>
                <small v-html='item.DESCRIPTION'></small>
            </p>
        </td>
        <td class='text-start text-muted'>
            <div v-html='item.TYPE_HTML'></div>
            <div class='mt-2' v-if='item.DICTIONARY_ID > 0'>
                <button type='button' class='btn btn-warning text-dark btn-xs mb-2 fs-8'
                        v-on:click='showCompareOzon=!showCompareOzon'>
                    <span>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_ATTRIBUTE_BUTTON_COMPARE_OZON') }}</span>
                </button>
                <button type='button' class='btn btn-warning text-dark btn-xs mb-2 fs-8'
                        v-on:click='showCompareIblock=!showCompareIblock'>
                    <span>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_ATTRIBUTE_BUTTON_COMPARE_IBLOCK') }}</span>
                </button>
            </div>
            <div v-if='showCompareOzon'>
                <ozon-attribute-item-modal
                    v-bind:title='item.NAME'
                    v-bind:ozonPropertyData='item.OZON_PROPERTY_DATA'
                    v-bind:iblockPropertyData='item.IBLOCK_PROPERTY_DATA'
                    v-on:actionCloseModal='actionCloseModal'
                    v-on:actionNextPage='actionNextPage'
                    v-on:actionSetConnectionEnum='actionSetConnectionEnum'
                />
            </div>
            <div v-if='showCompareIblock'>
                <ozon-attribute-item-iblock
                    v-bind:title='item.NAME'
                    v-bind:attributeId='item.ID'
                    v-bind:ozonPropertyData='item.OZON_PROPERTY_DATA'
                    v-bind:iblockPropertyData='item.IBLOCK_PROPERTY_DATA'
                    v-on:actionCloseModal='actionCloseModal'
                    v-on:actionSetConnectionEnum='actionSetConnectionEnum'
                />
            </div>
            <div v-if='item.IS_RATIO' class='mt-2'>
                <button type='button' class='btn btn-secondary btn-xs mb-2 fs-8' v-on:click='showRatio=!showRatio'>
                    <span>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_ATTRIBUTE_BUTTON_RATIO') }}</span>
                </button>
                <span v-bind:data-hint='loc.DARNEO_OZON_VUE_PRODUCT_ATTRIBUTE_HELPER_RATIO'></span>
                <div v-if='showRatio'>
                    <ozon-attribute-item-ratio
                        v-bind:item='item'
                        v-on:actionCloseModal='actionCloseModal'
                        v-on:actionSetRatio='actionSetRatio'
                    />
                </div>
                <div v-if='item.RATIO.length'>
                    <span class='badge badge-success' v-html='item.RATIO'></span>
                </div>
            </div>
        </td>
        <td class='text-start'>
            <ozon-attribute-item-prop
                v-bind:id='Number(item.ID)'
                v-bind:attributeType='item.ATTRIBUTE_TYPE'
                v-bind:property='property'
                v-bind:selected='item.PROPERTY'
                v-on:actionSetProperty='actionSetProperty'
            />
        </td>
        </tr>
    `,
})
</script>