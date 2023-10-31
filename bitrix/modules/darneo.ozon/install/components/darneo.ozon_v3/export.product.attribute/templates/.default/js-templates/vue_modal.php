<script>
BX.BitrixVue.component('ozon-modal', {
    props: {
        title: {
            type: String,
            required: true
        },
        data: {
            type: Array,
            required: true
        },
    },
    mounted: function () {
        this.$nextTick(function () {
            this.init()
        })
    },
    updated: function () {
        this.$nextTick(function () {
            this.init()
        })
    },
    methods: {
        init: function () {
            if (this.data.length > 0) {
                $(this.$el).modal('toggle')
            }
        },
    },
    template: `
        <div class='modal' tabindex='-1' role='dialog' v-if='data.length > 0'>
        <div class='modal-dialog modal-dialog-centered' role='document'>
            <div class='modal-content'>
                <div class='modal-header'>
                    <h5 class='modal-title' v-html='title'></h5>
                    <button class='btn-close' type='button' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body'>
                    <ul>
                        <li v-for='item in data'>
                            <i class='fa fa-angle-double-right txt-primary m-r-10'></i>{{ item }}
                        </li>
                    </ul>
                </div>
                <div class='modal-footer'>
                    <button class='btn btn-secondary' type='button' data-bs-dismiss='modal'>
                        {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_ATTRIBUTE_BUTTON_CLOSE') }}
                    </button>
                </div>
            </div>
        </div>
        </div>
    `,
})
</script>