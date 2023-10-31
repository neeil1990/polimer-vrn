<script>
BX.BitrixVue.component('ozon-attribute-item-ratio', {
    props: {
        item: {
            type: Object,
            required: true
        },
    },
    data: function () {
        return {
            ratio: this.item.RATIO,
            isSendSave: false,
        }
    },
    mounted: function () {
        this.$nextTick(function () {
            this.init()
        })
    },
    watch: {
        item: function () {
            this.isSendSave = false
        },
    },
    methods: {
        init: function () {
            let vm = this
            $(this.$el).modal('toggle')
            $(this.$el).on('hidden.bs.modal', function () {
                vm.$emit('actionCloseModal')
            })
        },
        actionSetRatio: function () {
            if (!this.isSendSave) {
                this.isSendSave = true
                let attributeId = this.item.ID
                this.$emit('actionSetRatio', attributeId, this.ratio)
            }
        },
    },
    template: `
        <div class='modal fade bd-example-modal-lg'>
        <div class='modal-dialog modal-lg'>
            <div class='modal-content'>
                <div class='modal-header'>
                    <h5 class='modal-title' v-html='item.NAME'></h5>
                    <button class='btn-close' type='button' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body'>
                    <label class='form-label'>
                        {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_ATTRIBUTE_BUTTON_RATIO') }}
                    </label>
                    <input type='number' class='form-control' v-model='ratio'/>
                    <div class='col-md-12 mt-2'>
                        <button type='button' class='btn btn-primary btn-xs fs-8' v-on:click='actionSetRatio()'
                                v-bind:disabled='isSendSave'>
                            <span>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_ATTRIBUTE_BUTTON_SAVE') }}</span>
                            <i class='fa fa-spin fa-spinner' v-show='isSendSave'></i>
                        </button>
                    </div>
                </div>
                <div class='modal-footer'>
                    <button class='btn btn-secondary mt-2' type='button' data-bs-dismiss='modal'>
                        {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_ATTRIBUTE_BUTTON_CLOSE') }}
                    </button>
                </div>
            </div>
        </div>
        </div>
    `,
})
</script>