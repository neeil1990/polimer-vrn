<script>
BX.BitrixVue.component('ozon-attribute-item-iblock-list-item', {
    props: {
        attributeId: {
            type: String,
            required: true
        },
        item: {
            type: Object,
            required: true
        },
    },
    data: function () {
        return {}
    },
    methods: {
        actionSetConnectionEnum: function (attributeValueId) {
            let attributeId = this.attributeId
            let propertyId = this.item.PROPERTY_ID
            let propertyEnumId = this.item.ID
            this.$emit('actionSetConnectionEnum', attributeId, attributeValueId, propertyId, propertyEnumId)
        },
        actionDeleteConnectionEnum: function () {
            BX.Ozon.ExportAttribute.Vue.actionDeleteConnectionEnum(this.item.ATTR.ID)
        }
    },
    template: `
        <tr>
        <td class='text-start'>
            <div class='d-flex center-block' v-html='item.VALUE'></div>
        </td>
        <td class='text-start'>
            <ul class='list-group' v-if='item.ATTR.ATTR_VALUE'>
                <li class='list-group-item'>
                    {{ item.ATTR.ATTR_VALUE }}
                    <span class='text-muted' v-if='item.ATTR.ATTR_INFO.length'>[{{ item.ATTR.ATTR_INFO }}]</span>
                    <img v-if='item.ATTR.ATTR_PICTURE.length' v-bind:src='item.ATTR.ATTR_PICTURE' style='width: auto'>
                    <button class='btn btn-danger btn-xs fs-8 ms-2' v-on:click='actionDeleteConnectionEnum()'>
                        {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_ATTRIBUTE_BUTTON_UNTIE') }}
                    </button>
                </li>
            </ul>
            <div v-else>
                <ozon-attribute-item-iblock-list-item-search
                    v-bind:attributeId='attributeId'
                    v-bind:helper='item.VALUE'
                    v-on:actionSetConnectionEnum='actionSetConnectionEnum'
                />
            </div>
        </td>
        </tr>
    `,
})
</script>