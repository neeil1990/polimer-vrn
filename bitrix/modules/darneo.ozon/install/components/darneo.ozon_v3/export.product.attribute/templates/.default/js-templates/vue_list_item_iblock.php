<script>
BX.BitrixVue.component('ozon-attribute-item-iblock', {
    props: {
        title: {
            type: String,
            required: true
        },
        attributeId: {
            type: String,
            required: true
        },
        iblockPropertyData: {
            type: Object,
            required: false
        },
    },
    mounted: function () {
        this.$nextTick(function () {
            this.init()
        })
    },
    methods: {
        actionSetConnectionEnum: function (attributeId, attributeValueId, propertyId, propertyEnumId) {
            this.$emit('actionSetConnectionEnum', attributeId, attributeValueId, propertyId, propertyEnumId)
        },
        init: function () {
            let vm = this
            $(this.$el).modal('toggle')
            $(this.$el).on('hidden.bs.modal', function () {
                vm.$emit('actionCloseModal')
                BX.Ozon.ExportAttribute.Vue.setSearchAttributeValue('')
            })
        },
        actionNextPage: function (page) {
            this.$emit('actionNextPage', page)
        },
    },
    template: `
        <div class='modal fade bd-example-modal-lg'>
        <div class='modal-dialog modal-lg'>
            <div class='modal-content'>
                <div class='modal-header'>
                    <h5 class='modal-title' v-html='title'></h5>
                    <button class='btn-close' type='button' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body'>
                    <ozon-attribute-item-iblock-list
                        v-bind:attributeId='attributeId'
                        v-bind:iblockPropertyData='iblockPropertyData.LIST'
                        v-on:actionSetConnectionEnum='actionSetConnectionEnum'
                    />
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