<script>
BX.BitrixVue.component('ozon-attribute-item-iblock-list-item-search-item', {
    props: {
        item: {
            type: Object,
            required: false
        },
        isSaveDisable: {
            type: Boolean,
            required: false
        },
    },
    data: function () {
        return {
            isSave: false
        }
    },
    methods: {
        actionSave: function () {
            if (!this.isSaveDisable) {
                this.$emit('actionSave', this.item.ID)
                return this.isSave = true
            }
        },
    },
    template: `
        <li class='list-group-item'>
        {{ item.NAME }} <span class='text-muted' v-if='item.INFO.length'>[{{ item.INFO }}]</span>

        <img v-if='item.PICTURE.length' v-bind:src='item.PICTURE' style='width: auto'>

        <button type='button' class='btn btn-primary btn-xs fs-8' v-on:click='actionSave()'
                v-bind:disabled='isSaveDisable' v-bind:class='{disable:isSaveDisable}'>
            <span>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_ATTRIBUTE_BUTTON_SELECT') }}</span>
            <i class='fa fa-spin fa-spinner' v-show='isSave'></i>
        </button>
        </li>
    `,
})
</script>