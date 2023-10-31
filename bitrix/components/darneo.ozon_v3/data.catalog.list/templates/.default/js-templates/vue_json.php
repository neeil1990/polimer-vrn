<script>
BX.BitrixVue.component('catalog-json', {
    props: {
        data: {
            type: Object,
            required: true
        },
    },
    mounted: function () {
        this.$nextTick(function () {
            this.init()
            this.initJson('#Json', this.data.JSON)
        })
    },
    methods: {
        init: function () {
            let vm = this
            $(this.$el).modal('toggle')
            $(this.$el).on('hidden.bs.modal', function () {
                vm.$emit('setDataJson', {})
            })
        },
        initJson: function (selector, data) {
            new JsonEditor($(this.$el).find(selector), data)
        },
    },
    template: `
        <div class='modal fade bd-example-modal-lg'>
        <div class='modal-dialog modal-lg'>
            <div class='modal-content'>
                <div class='modal-header'>
                    <h5 class='modal-title' v-html='data.NAME'></h5>
                    <button class='btn-close' type='button' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body'>
                    <div id='Json'></div>
                </div>
                <div class='modal-footer'>
                    <button class='btn btn-secondary' type='button' data-bs-dismiss='modal'>
                        {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_CATALOG_LIST_BUTTON_CLOSE') }}
                    </button>
                </div>
            </div>
        </div>
        </div>
    `,
})
</script>