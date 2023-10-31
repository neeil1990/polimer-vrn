<script>
BX.BitrixVue.component('ozon-stock-import', {
    props: {
        isImportStart: {
            type: Boolean,
            required: false
        },
    },
    methods: {
        actionStart: function () {
            if (!this.isImportStart) {
                this.$emit('actionImportStart')
            }
        },
    },
    template: `
        <div class='d-flex flex-end align-items-center gap-2 gap-lg-3 mb-5'>
        <a href='javascript:void(0)' class='btn btn-sm btn-primary'
           v-on:click='actionStart()' v-bind:class='{disabled:isImportStart}'>
            {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_SETTINGS_STOCK_LIST_BUTTON_LOAD_STORAGE') }}
            <i class='fa fa-spin fa-spinner' v-show='isImportStart'></i>
        </a>
        </div>
    `,
})
</script>