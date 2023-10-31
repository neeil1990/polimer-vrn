<script>
BX.BitrixVue.component('ozon-attribute-import', {
    props: {
        connectionSectionTree: {
            type: Number,
            required: false
        },
        isImportStart: {
            type: Boolean,
            required: false
        },
    },
    methods: {
        actionStart: function () {
            if (!this.isImportStart && this.connectionSectionTree > 0) {
                this.$emit('actionImportStart')
            }
        },
    },
    template: `
        <button class='btn btn-primary me-2' type='button' v-on:click='actionStart()'
                v-bind:disabled='isImportStart || !connectionSectionTree'>
        <span v-show='!isImportStart'>
            {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_ATTRIBUTE_BUTTON_LOAD_CHARACTERISTICS') }}
        </span>
        <span v-show='isImportStart'>
            {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_ATTRIBUTE_BUTTON_WORK') }}
        </span>
        <i class='fa fa-spin fa-spinner' v-show='isImportStart'></i>
        </button>
    `,
})
</script>