<script>
BX.BitrixVue.component('ozon-cron-install', {
    props: {
        isReinstallStart: {
            type: Boolean,
            required: false
        },
    },
    methods: {
        actionReinstall: function () {
            if (!this.isReinstallStart) {
                this.$emit('actionReinstall')
            }
        },
    },
    template: `
        <div class='d-flex flex-end align-items-center gap-2 gap-lg-3 mb-5'>
        <a href='javascript:void(0)' class='btn btn-sm btn-light'
           v-on:click='actionReinstall()' v-bind:class='{disabled:isReinstallStart}'>
            {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_CRON_LIST_REINSTALL') }}
            <i class='fa fa-spin fa-spinner' v-show='isReinstallStart'></i>
        </a>
        </div>
    `,
})
</script>