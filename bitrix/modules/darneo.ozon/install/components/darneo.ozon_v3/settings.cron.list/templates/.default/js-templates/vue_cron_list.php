<script>
BX.BitrixVue.component('ozon-cron-list', {
    props: {
        data: {
            type: Object,
            required: true
        },
    },
    computed: {
        loc: function () {
            return BX.BitrixVue.getFilteredPhrases('DARNEO_OZON_')
        },
    },
    mounted: function () {
        this.$nextTick(function () {
            this.initTable()
            this.initTooltip()
        })
    },
    updated: function () {
        this.$nextTick(function () {
            this.initTooltip()
        })
    },
    methods: {
        initTable: function () {
            $(this.$el).find('#setting-cron').DataTable({
                responsive: true,
                searching: false,
                ordering: false,
                info: false,
                paging: false,
                autoWidth: false
            })
            BX.UI.Hint.init(BX('setting-cron'))
        },
        actionUpdate: function (code, value) {
            this.$emit('actionUpdate', code, value)
        },
        initTooltip: function () {
            BX.UI.Hint.init(BX('setting-cron'))
        },
    },
    template: `
        <div class='table-responsive'>
        <table class='table table-row-bordered table-striped table-row-gray-200 align-middle gs-7 gy-4' id='setting-cron'>
            <thead>
            <tr class='fw-bold text-muted bg-light'>
                <th class='ps-4 min-w-325px rounded-start'>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_CRON_LIST_TABLE_HEAD_TITLE') }}</th>
                <th class='w-400px'>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_CRON_LIST_TABLE_HEAD_DESCRIPTION') }}</th>
                <th class='min-w-125px'>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_CRON_LIST_TABLE_HEAD_DATE_START') }}</th>
                <th class='min-w-125px'>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_CRON_LIST_TABLE_HEAD_DATE_FINISH') }}</th>
                <th class='min-w-125px'>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_CRON_LIST_TABLE_HEAD_VALUE') }}</th>
            </tr>
            </thead>
            <tbody>
            <ozon-cron-list-item
                v-for='item in data.ITEMS' :key='String(item.CODE)'
                v-bind:item='item'
                v-on:actionUpdate='actionUpdate'
            />
            </tbody>
        </table>
        </div>
    `,
})
</script>