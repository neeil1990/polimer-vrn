<script>
BX.BitrixVue.component('ozon-catalog-list', {
    props: {
        data: {
            type: Object,
            required: true
        },
        page: {
            type: Number,
            required: true
        },
        finalPage: {
            type: Boolean,
            required: false
        },
    },
    data: function () {
        return {
            isNextPage: false,
            dataJson: {}
        }
    },
    watch: {
        data: function () {
            this.isNextPage = false
        },
        isNextPage: function (value) {
            if (value && !this.finalPage) {
                this.$emit('actionNextPage', this.page + 1)
            }
        },
    },
    mounted: function () {
        this.$nextTick(function () {
            this.initTable()
            this.initNav()
        })
    },
    destroyed: function () {
        $(this.$el).find('#responsive').DataTable().destroy()
    },
    methods: {
        setDataJson: function (dataJson) {
            this.dataJson = dataJson
        },
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
        initNav: function () {
            let vm = this
            let $win = $(window)
            let $marker = $(this.$el).find('#catalog-nav')
            $win.scroll(function () {
                if ($win.scrollTop() + $win.height() >= $marker.offset().top) {
                    if (!vm.isNextPage && !vm.finalPage) {
                        vm.isNextPage = true
                    }
                }
            })
        },
    },
    template: `
        <div class='table-responsive mt-5'>
        <table class='table table-row-bordered table-striped table-row-gray-200 align-middle gs-7 gy-4' id='basic-1'>
            <thead>
            <tr class='fw-bold text-muted bg-light'>
                <th class='ps-4 min-w-100px rounded-start'>
                    {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_CATALOG_LIST_TABLE_HEAD_CODE') }}
                </th>
                <th class='min-w-200px'>
                    {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_CATALOG_LIST_TABLE_HEAD_NAME') }}
                </th>
                <th class='min-w-100px'>
                    {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_CATALOG_LIST_TABLE_HEAD_STATUS') }}
                </th>
                <th class='min-w-100px'>
                    {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_CATALOG_LIST_TABLE_HEAD_SEC') }}
                </th>
                <th class='min-w-100px'>
                    {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_CATALOG_LIST_TABLE_HEAD_FBO') }}
                </th>
                <th class='min-w-100px'>
                    {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_CATALOG_LIST_TABLE_HEAD_FBS') }}
                </th>
                <th class='min-w-100px'>
                    {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_CATALOG_LIST_TABLE_HEAD_PRICE') }}
                </th>
                <th class='min-w-100px'>
                    {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_CATALOG_LIST_TABLE_HEAD_JSON') }}
                </th>
            </tr>
            </thead>
            <tbody>
            <ozon-catalog-list-item
                v-for='item in data.LIST' :key='Number(item.ID)'
                v-bind:item='item'
                v-on:setDataJson='setDataJson'
            />
            </tbody>
        </table>
        <div class='loader-box' v-show='isNextPage'>
            <div class='loader-19'></div>
        </div>
        <div id='catalog-nav'></div>
        <template v-if='dataJson.JSON'>
            <catalog-json
                v-bind:data='dataJson'
                v-on:setDataJson='setDataJson'
            />
        </template>
        </div>
    `,
})
</script>