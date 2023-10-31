<script>
BX.BitrixVue.component('ozon-access-list', {
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
        actionAdd: function (rowId) {
            this.$emit('actionAdd', rowId)
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
                <th class='w-100px'>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_ACCESS_LIST_TABLE_HEAD_GROUP_ID') }}</th>
                <th class='min-w-200px'>
                    {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_ACCESS_LIST_TABLE_HEAD_GROUP_NAME') }}
                </th>
                <th class='w-125px text-end rounded-end'></th>
            </tr>
            </thead>
            <tbody>
            <ozon-access-list-add
                v-bind:group='data.GROUP'
                v-on:actionAdd='actionAdd'
            />
            <ozon-access-list-item
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