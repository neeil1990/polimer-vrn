<script>
BX.BitrixVue.component('ozon-attribute-item-modal-list', {
    props: {
        ozonPropertyEnum: {
            type: Array,
            required: true
        },
        iblockPropertyEnum: {
            type: Array,
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
            dataSearch: '',
            pause: {
                startTime: 1,
                currentTime: 0,
                timer: null
            }
        }
    },
    computed: {
        loc: function () {
            return BX.BitrixVue.getFilteredPhrases('DARNEO_OZON_')
        },
    },
    watch: {
        ozonPropertyEnum: function () {
            this.isNextPage = false
        },
        dataSearch: function () {
            this.startTimer()
        },
        'pause.currentTime': function (time) {
            if (time === 0) {
                this.stopTimer()
                BX.Ozon.ExportAttribute.Vue.setSearchAttributeValue(this.dataSearch)
                this.$emit('actionNextPage', 1)
            }
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
        actionSetConnectionEnum: function (attributeId, attributeValueId, propertyId, propertyEnumId) {
            this.$emit('actionSetConnectionEnum', attributeId, attributeValueId, propertyId, propertyEnumId)
        },
        startTimer: function () {
            this.stopTimer()
            this.pause.currentTime = this.pause.startTime
            this.pause.timer = setInterval(() => {
                this.pause.currentTime--
            }, 1000)
        },
        stopTimer: function () {
            clearTimeout(this.pause.timer)
        },
        actionUnset: function () {
            this.dataSearch = ''
            this.$emit('actionNextPage', 1, this.dataSearch)
        },
        initTable: function () {
            $(this.$el).find('#basic-1').DataTable({
                responsive: true,
                searching: false,
                ordering: false,
                info: false,
                paging: false
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
        <div>
        <div class='table-responsive'>
            <table class='table table-row-bordered table-striped table-row-gray-200 align-middle gs-7 gy-4' id='basic-1'>
                <thead>
                <tr class='fw-bold text-muted bg-light'>
                    <th class='w-50'>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_ATTRIBUTE_TABLE_HEAD_TITLE') }}</th>
                    <th class='w-50'>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_ATTRIBUTE_TABLE_HEAD_VALUE') }}</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>
                        <div class='d-flex'>
                            <input
                                class='form-control'
                                type='text'
                                v-bind:placeholder='loc.DARNEO_OZON_VUE_PRODUCT_ATTRIBUTE_PLACEHOLDER_SEARCH'
                                v-model='dataSearch'
                            >
                            <a v-show='dataSearch.length>0' class='m-2' href='javascript:void(0)' v-on:click='actionUnset()'>
                                <i class='ki-duotone ki-cross-square fs-2x'>
                                    <i class='path1'></i>
                                    <i class='path2'></i>
                                </i>
                            </a>
                        </div>
                    </td>
                    <td></td>
                </tr>
                <ozon-attribute-item-modal-item
                    v-for='item in ozonPropertyEnum' :key='Number(item.ID)'
                    v-bind:item='item'
                    v-bind:iblockPropertyEnum='iblockPropertyEnum'
                    v-on:actionSetConnectionEnum='actionSetConnectionEnum'
                />
                </tbody>
            </table>
            <div class='mt-2' v-show='!finalPage'>
                <button type='button' class='btn btn-warning text-dark btn-xs' v-on:click='isNextPage=true'>
                    <span>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_ATTRIBUTE_BUTTON_SHOW') }}</span>
                </button>
            </div>
            <div class='loader-box' v-show='isNextPage'>
                <div class='loader-19'></div>
            </div>
        </div>
        <div id='catalog-nav'></div>
        </div>
    `,
})
</script>