<script>
BX.BitrixVue.component('ozon-stock-list', {
    props: {
        data: {
            type: Object,
            required: true
        },
    },
    data: function () {
        return {}
    },
    computed: {
        loc: function () {
            return BX.BitrixVue.getFilteredPhrases('DARNEO_OZON_')
        },
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
            BX.UI.Hint.init(BX('basic-1'))
        },
    },
    template: `
        <div class='table-responsive'>
        <table class='table table-row-bordered table-striped table-row-gray-200 align-middle gs-7 gy-4' id='basic-1'>
            <thead>
            <tr class='fw-bold text-muted bg-light'>
                <th class='ps-4 min-w-125px rounded-start'>
                    {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_SETTINGS_STOCK_LIST_TABLE_HEAD_ID') }}
                </th>
                <th>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_SETTINGS_STOCK_LIST_TABLE_HEAD_NAME') }}</th>
                <th>
                    {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_SETTINGS_STOCK_LIST_TABLE_HEAD_RFBS') }}
                    <span v-bind:data-hint='loc.DARNEO_OZON_VUE_SETTINGS_STOCK_LIST_RFBS'></span>
                </th>
            </tr>
            </thead>
            <tbody>
            <ozon-stock-list-item
                v-for='item in data.ITEMS' :key='Number(item.ID)'
                v-bind:item='item'
            />
            </tbody>
        </table>
        </div>
    `,
})
</script>