<script>
BX.BitrixVue.component('ozon-category-section-list', {
    props: {
        level3: {
            type: Number,
            required: true
        },
        title: {
            type: String,
            required: true
        },
        data: {
            type: Array,
            required: false
        },
    },
    data: function () {
        return {}
    },
    mounted: function () {
        this.$nextTick(function () {
            this.init()
        })
    },
    updated: function () {
        this.$nextTick(function () {
            //this.init()
        })
    },
    methods: {
        init: function () {
            let vm = this
            $(this.$el).modal('toggle')
            $(this.$el).on('hidden.bs.modal', function () {
                vm.$emit('actionCloseModal')
            })
        },
    },
    template: `
        <div class='modal fade bd-example-modal-lg'>
        <div class='modal-dialog modal-lg'>
            <div class='modal-content'>
                <div class='modal-header'>
                    <h4 class='modal-title' id='myLargeModalLabel'>
                        {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_CATEGORY_LIST_HEAD_TITLE') }}
                        <br>
                        <span v-html='title'></span>
                    </h4>
                    <button class='btn-close' type='button' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body'>
                    <div class='table-responsive'>
                        <table class='table table-hover'>
                            <thead>
                            <tr>
                                <th scope='col'>
                                    {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_CATEGORY_LIST_TABLE_IBLOCK') }}
                                </th>
                                <th scope='col'>
                                    {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_CATEGORY_LIST_TABLE_ID') }}
                                </th>
                                <th scope='col'>
                                    {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_CATEGORY_LIST_TABLE_NAME') }}
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for='item in data' :key='item.SECTION_ID'>
                                <th>[{{ item.IBLOCK_ID }}] {{ item.IBLOCK_NAME }}</th>
                                <td v-html='item.SECTION_ID'></td>
                                <td v-html='item.TITLE'></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        </div>
    `,
})
</script>