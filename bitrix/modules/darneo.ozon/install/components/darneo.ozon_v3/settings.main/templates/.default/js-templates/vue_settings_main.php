<script>
BX.BitrixVue.component('ozon-settings-main', {
    props: {
        data: {
            type: Object,
            required: false
        },
    },
    computed: {
        loc: function () {
            return BX.BitrixVue.getFilteredPhrases('DARNEO_OZON_')
        },
    },
    mounted: function () {
        this.$nextTick(function () {
            BX.UI.Hint.init(BX('ozon_settings'))
        })
    },
    methods: {
        actionUpdateField: function (dataForm) {
            this.$emit('actionUpdateField', dataForm)
        },
        actionDelete: function (rowId) {
            this.$emit('actionDelete', rowId)
        },
    },
    template: `
        <div class='row' id='ozon_settings'>
        <div class='col-lg-12'>
            <div class='card'>
                <div class='card-body'>
                    <div class='row'>
                        <div class='col-xs-12 col-md-6 m-t-10'>
                            <h4>
                                {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_SETTINGS_MAIN_BLOCK_TEST') }}
                                <span v-bind:data-hint='loc.DARNEO_OZON_VUE_SETTINGS_MAIN_IS_TEST_HELPER'></span>
                            </h4>
                            <ozon-field
                                v-bind:field='data.FIELDS.IS_TEST'
                                v-bind:isOnlyEdit=true
                                type='boolean'
                                v-on:actionUpdateField='actionUpdateField'
                            />
                        </div>
                        <div class='col-xs-12 col-md-6 m-t-10'>
                            <h4>
                                {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_SETTINGS_MAIN_BLOCK_CHAT') }}
                                <span v-bind:data-hint='loc.DARNEO_OZON_VUE_SETTINGS_MAIN_IS_CHAT_HELPER'></span>
                            </h4>
                            <ozon-field
                                v-bind:field='data.FIELDS.IS_CHAT'
                                v-bind:isOnlyEdit=true
                                type='boolean'
                                v-on:actionUpdateField='actionUpdateField'
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>
    `,
})
</script>