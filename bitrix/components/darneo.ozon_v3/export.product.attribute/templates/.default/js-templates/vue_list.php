<script>
BX.BitrixVue.component('ozon-attribute-list', {
    props: {
        data: {
            type: Array,
            required: true
        },
        property: {
            type: Object,
            required: true
        },
    },
    data: function () {
        return {}
    },
    mounted: function () {
        this.$nextTick(function () {
            this.initTable()
            BX.UI.Hint.init(BX('datatable'))
        })
    },
    destroyed: function () {
        $(this.$el).find('#responsive').DataTable().destroy()
    },
    methods: {
        actionSetProperty: function (propertyId, propertyType, propertyValue, value) {
            this.$emit('actionSetProperty', propertyId, propertyType, propertyValue, value)
        },
        actionSetConnectionEnum: function (attributeId, attributeValueId, propertyId, propertyEnumId) {
            this.$emit('actionSetConnectionEnum', attributeId, attributeValueId, propertyId, propertyEnumId)
        },
        initTable: function () {
            $(this.$el).find('#datatable').DataTable({
                responsive: true,
                searching: false,
                ordering: false,
                info: false,
                paging: false
            })
        },
        actionNextPage: function (propertyId, page) {
            this.$emit('actionNextPage', propertyId, page)
        },
        actionSetRatio: function (attributeId, ratio) {
            this.$emit('actionSetRatio', attributeId, ratio)
        },
    },
    template: `
        <div class='table-responsive mt-10'>
        <table class='table table-row-bordered table-striped table-row-gray-200 align-middle gs-7 gy-4' id='datatable'>
            <thead>
            <tr class='fw-bold text-muted bg-light'>
                <th class='ps-4 w-25 rounded-start'>
                    {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_ATTRIBUTE_TABLE_HEAD_NAME') }}
                </th>
                <th class='w-25'>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_ATTRIBUTE_TABLE_HEAD_TYPE') }}</th>
                <th class='w-50'>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_ATTRIBUTE_TABLE_HEAD_BINDING') }}</th>
            </tr>
            </thead>
            <tbody>
            <ozon-attribute-item
                v-for='item in data' :key='Number(item.ID)'
                v-bind:item='item'
                v-bind:property='property'
                v-on:actionSetProperty='actionSetProperty'
                v-on:actionSetConnectionEnum='actionSetConnectionEnum'
                v-on:actionNextPage='actionNextPage'
                v-on:actionSetRatio='actionSetRatio'
            />
            </tbody>
        </table>
        </div>
    `,
})
</script>