<script>
BX.BitrixVue.component('ozon-attribute-item-iblock-list', {
    props: {
        attributeId: {
            type: String,
            required: true
        },
        iblockPropertyData: {
            type: Array,
            required: false
        },
    },
    mounted: function () {
        this.$nextTick(function () {
            this.initTable()
        })
    },
    destroyed: function () {
        $(this.$el).find('#responsive').DataTable().destroy()
    },
    methods: {
        actionSetConnectionEnum: function (attributeId, attributeValueId, propertyId, propertyEnumId) {
            this.$emit('actionSetConnectionEnum', attributeId, attributeValueId, propertyId, propertyEnumId)
        },
        initTable: function () {
            $(this.$el).find('#basic-2').DataTable({
                responsive: true,
                searching: false,
                ordering: false,
                info: false,
                paging: false
            })
        },
    },
    template: `
        <div class='table-responsive'>
        <table class='table table-row-bordered table-striped table-row-gray-200 align-middle gs-7 gy-4' id='basic-2'>
            <thead>
            <tr class='fw-bold text-muted bg-light'>
                <th class='w-25'>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_ATTRIBUTE_TABLE_HEAD_VALUE') }}</th>
                <th class='w-75'>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_ATTRIBUTE_TABLE_HEAD_TITLE') }}</th>
            </tr>
            </thead>
            <tbody>
            <ozon-attribute-item-iblock-list-item
                v-for='item in iblockPropertyData' :key='Number(item.ID)'
                v-bind:attributeId='attributeId'
                v-bind:item='item'
                v-on:actionSetConnectionEnum='actionSetConnectionEnum'
            />
            </tbody>
        </table>
        </div>
    `,
})
</script>