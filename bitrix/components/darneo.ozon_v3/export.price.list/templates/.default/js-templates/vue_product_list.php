<script>
BX.BitrixVue.component('ozon-product-list', {
    props: {
        data: {
            type: Object,
            required: true
        },
        request: {
            type: Object,
            required: false
        },
    },
    data: function () {
        return {}
    },
    mounted: function () {
        this.$nextTick(function () {
            this.initTable()
        })
    },
    updated: function () {

    },
    methods: {
        initTable: function () {
            $(this.$el).find('#basic-1').DataTable({
                responsive: true,
                searching: false,
                ordering: false,
                info: false,
                paging: false,
                autoWidth: false
            })
        },
        actionDelete: function (rowId) {
            this.$emit('actionDelete', rowId)
        },
    },
    template: `
        <div class='table-responsive'>
        <table class='table table-row-bordered table-striped table-row-gray-200 align-middle gs-7 gy-4' id='basic-1'>
            <thead>
            <tr class='fw-bold text-muted bg-light'>
                <th class='ps-4 min-w-100px rounded-start'>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRICE_LIST_TABLE_HEAD_TITLE') }}</th>
                <th class='min-w-125px'>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRICE_LIST_TABLE_HEAD_PRICE') }}</th>
                <th class='min-w-125px'>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRICE_LIST_TABLE_HEAD_RATIO') }}</th>
                <th class='min-w-100px'>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRICE_LIST_TABLE_HEAD_DISCOUNT') }}</th>
                <th class='min-w-100px'>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRICE_LIST_TABLE_HEAD_IS_CRON') }}</th>
                <th class='w-125px text-end rounded-end'></th>
            </tr>
            </thead>
            <tbody>
            <ozon-product-list-item
                v-for='item in data.ITEMS' :key='Number(item.ID)'
                v-bind:item='item'
                v-on:actionDelete='actionDelete'
            />
            </tbody>
        </table>
        </div>
    `,
})
</script>