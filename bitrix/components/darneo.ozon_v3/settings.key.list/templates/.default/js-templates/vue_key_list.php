<script>
BX.BitrixVue.component('ozon-key-list', {
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
        return {
            updateRow: {}
        }
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
        actionAdd: function (clientId, apiKey, name, isMain) {
            this.$emit('actionAdd', clientId, apiKey, name, isMain)
        },
        actionDelete: function (rowId) {
            this.$emit('actionDelete', rowId)
        },
        actionUpdate: function (rowId, clientId, apiKey, name, isMain) {
            this.$emit('actionUpdate', rowId, clientId, apiKey, name, isMain)
        },
        showUpdate: function (row) {
            this.updateRow = row
        },
    },
    template: `
        <div class='table-responsive'>
        <table class='table table-row-bordered table-striped table-row-gray-200 align-middle gs-7 gy-4' id='basic-1'>
            <thead>
            <tr class='fw-bold text-muted bg-light'>
                <th class='min-w-200px'>
                    {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_KEY_LIST_TABLE_HEAD_KEY_NAME') }}
                </th>
                <th class='min-w-200px'>
                    {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_KEY_LIST_TABLE_HEAD_CLIENT_ID') }}
                </th>
                <th class='min-w-200px'>
                    {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_KEY_LIST_TABLE_HEAD_KEY_API') }}
                </th>
                <th class='min-w-125px'>
                    {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_KEY_LIST_TABLE_HEAD_DEFAULT') }}
                    <span v-bind:data-hint='loc.DARNEO_OZON_VUE_KEY_LIST_UPLOAD_DIRECTORY'></span>
                </th>
                <th class='min-w-100px text-end rounded-end'></th>
            </tr>
            </thead>
            <tbody>
            <ozon-key-list-add
                v-on:actionAdd='actionAdd'
            />
            <ozon-key-list-item
                v-for='item in data.ITEMS' :key='Number(item.ID)'
                v-bind:item='item'
                v-on:actionDelete='actionDelete'
                v-on:showUpdate='showUpdate'
            />
            </tbody>
        </table>
        <div v-if='updateRow.ID'>
            <ozon-key-list-update
                v-bind:item='updateRow'
                v-bind:request='request'
                v-on:actionUpdate='actionUpdate'
                v-on:showUpdate='showUpdate'
            />
        </div>
        </div>
    `,
})
</script>