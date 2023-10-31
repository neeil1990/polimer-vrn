<script>
BX.BitrixVue.component('ozon-cron-detail', {
    props: {
        data: {
            type: Object,
            required: false
        },
    },
    methods: {
        actionUpdateField: function (dataForm) {
            this.$emit('actionUpdateField', dataForm)
        },
    },
    template: `
        <div class='row g-5'>
        <div class='col-lg-4'>
            <div class='card card-stretch'>
                <div class='card-body'>
                    <ozon-field
                        v-bind:field='data.FIELDS.IS_CRON'
                        v-bind:isOnlyEdit=true
                        type='boolean'
                        v-on:actionUpdateField='actionUpdateField'
                    />
                </div>
            </div>
        </div>
        <div class='col-lg-8'>
            <div class='card card-stretch'>
                <div class='card-body'>
                    <div class='detail-cron--trigger field-title'>
                        {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRICE_CRON_HELPER') }}
                    </div>
                    <div class='alert alert-primary d-flex align-items-center p-5'>
                        <div class='d-flex flex-column'>
                            <span v-html='data.SETTING_CRON_HELPER'></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class='col-lg-12' v-if='!data.GENERAL_ACTIVE'>
            <div class='alert alert-danger d-flex align-items-center p-5 mt-2 mb-2'>
                <i class='ki-duotone ki-shield-tick fs-2hx text-danger me-4'>
                    <span class='path1'></span>
                    <span class='path2'></span>
                </i>
                <div class='d-flex flex-column'>
                    <h4 class='mb-1 text-danger'>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRICE_CRON_GENERAL_ACTIVE_WARNING_H1') }}</h4>
                    <span>
                        {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRICE_CRON_GENERAL_ACTIVE_WARNING') }}
                        <a v-bind:href='data.SETTING_CRON_FOLDER' class='btn btn-primary ms-5'>
                            {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRICE_CRON_GENERAL_ACTIVE_BUTTON') }}
                        </a>
                    </span>
                </div>
            </div>
        </div>
        </div>
    `,
})
</script>