<script>
BX.BitrixVue.component('ozon-product-detail', {
    props: {
        data: {
            type: Object,
            required: false
        },
    },
    data: function () {
        return {
            showSettingsOther: false,
        }
    },
    computed: {
        loc: function () {
            return BX.BitrixVue.getFilteredPhrases('DARNEO_OZON_')
        },
    },
    mounted: function () {
        this.$nextTick(function () {
            let vm = this
            let parentElement = document.getElementById('kt_app_body')
            parentElement.addEventListener('click', function (event) {
                let popover = event.target.closest('.popover')
                let link = 'element_delete_' + vm.data.ELEMENT_ID
                if (popover && document.getElementById(link) && event.target.id === link) {
                    vm.actionDelete(vm.data.ELEMENT_ID)
                }
            })
        })
    },
    methods: {
        actionUpdateField: function (dataForm) {
            this.$emit('actionUpdateField', dataForm)
        },
        actionDelete: function (rowId) {
            this.$emit('actionDelete', rowId)
        },
        getTextDelete: function (rowId) {
            let text = this.loc.DARNEO_OZON_VUE_PRODUCT_LIST_DELETE_WARNING
            let button = '<a href="javascript:void(0)" ' +
                'class="btn btn-primary btn-sm link-section ms-2 element_delete_js" id="element_delete_' + rowId + '">' +
                this.loc.DARNEO_OZON_VUE_PRODUCT_LIST_DELETE_WARNING_YES +
                '</a>'
            return text + ' ' + button
        },
    },
    template: `
        <div class='row'>
        <div class='col-lg-9'>
            <div class='card mb-6'>
                <div class='card-body'>
                    <div class='row'>
                        <div class='col-xs-12 col-lg-6 col-xxl-6'>
                            <ozon-field
                                v-bind:field='data.FIELDS.TITLE'
                                type='input'
                                v-on:actionUpdateField='actionUpdateField'
                            />
                        </div>
                        <div class='col-xs-12 col-lg-6 col-xxl-6'>
                            <ozon-field
                                v-bind:field='data.FIELDS.IBLOCK'
                                type='select'
                                v-on:actionUpdateField='actionUpdateField'
                            />
                        </div>
                    </div>
                </div>
            </div>
            <div class='card mb-6'>
                <div class='card-body'>
                    <div class='row'>
                        <div class='col-md-12'>
                            <h4 class='text-gray-700'>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_DETAIL_PRICE_TITLE') }}</h4>
                        </div>
                        <div class='col-xs-12 col-lg-6 col-xxl-6 mt-5'>
                            <ozon-field
                                v-bind:field='data.FIELDS.TYPE_PRICE_ID'
                                type='select'
                                v-on:actionUpdateField='actionUpdateField'
                            />
                        </div>
                        <div class='col-xs-12 col-lg-6 col-xxl-6 mt-5'>
                            <ozon-field
                                v-bind:field='data.FIELDS.PRICE_RATIO'
                                type='input'
                                v-on:actionUpdateField='actionUpdateField'
                            />
                        </div>
                        <div class='col-md-12 mt-10'>
                            <h4 class='text-gray-700'>
                                {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_DETAIL_DISCOUNT_TITLE') }}
                            </h4>
                        </div>
                        <div class='col-xs-12 col-lg-6 col-xxl-6 mt-5'>
                            <ozon-field
                                v-bind:field='data.FIELDS.IS_DISCOUNT_PRICE'
                                v-bind:isOnlyEdit=true
                                type='boolean'
                                v-on:actionUpdateField='actionUpdateField'
                            />
                        </div>
                        <div class='col-xs-12 col-lg-6 col-xxl-6 mt-5'>
                            <ozon-field
                                v-bind:field='data.FIELDS.SITE_ID'
                                type='select'
                                v-on:actionUpdateField='actionUpdateField'
                            />
                        </div>
                    </div>
                </div>
            </div>
            <div class='card mb-6'>
                <div class='card-body'>
                    <div class='row'>
                        <div class='col-md-12'>
                            <h4 class='text-gray-700'>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_DETAIL_IMG_FILE_TITLE') }}</h4>
                        </div>
                        <div class='col-xs-12 col-lg-6 col-xxl-6 mt-5'>
                            <ozon-field
                                v-bind:field='data.FIELDS.PHOTO_MAIN'
                                type='select'
                                v-on:actionUpdateField='actionUpdateField'
                            />
                        </div>
                        <div class='col-xs-12 col-lg-6 col-xxl-6 mt-5'>
                            <ozon-field
                                v-bind:field='data.FIELDS.PHOTO_OTHER'
                                type='select'
                                v-on:actionUpdateField='actionUpdateField'
                            />
                        </div>
                        <div class='col-xs-12 col-lg-6 col-xxl-6 mt-5'>
                            <ozon-field
                                v-bind:field='data.FIELDS.DOMAIN'
                                type='input'
                                v-on:actionUpdateField='actionUpdateField'
                            />
                        </div>
                    </div>
                </div>
            </div>
            <div class='card mb-6'>
                <div class='card-body'>
                    <div class='row'>
                        <div class='col-md-12'>
                            <h4 class='text-gray-700'>
                                {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_DETAIL_CHARACTERISTICS_TITLE') }}</h4>
                        </div>
                        <div class='col-xs-12 col-lg-6 col-xxl-6 mt-5'>
                            <ozon-field
                                v-bind:field='data.FIELDS.ELEMENT_NAME'
                                type='select'
                                v-on:actionUpdateField='actionUpdateField'
                            />
                        </div>
                        <div class='col-xs-12 col-lg-6 col-xxl-6 mt-5'>
                            <ozon-field
                                v-bind:field='data.FIELDS.VENDOR_CODE'
                                type='select'
                                v-on:actionUpdateField='actionUpdateField'
                            />
                        </div>
                        <div class='col-xs-12 col-lg-6 col-xxl-6 mt-5'>
                            <ozon-field
                                v-bind:field='data.FIELDS.BAR_CODE'
                                type='select'
                                v-on:actionUpdateField='actionUpdateField'
                            />
                        </div>
                    </div>
                </div>
            </div>
            <div class='card mb-6'>
                <div class='card-body'>
                    <div class='row'>
                        <div class='col-md-12'>
                            <h4 class='text-gray-700'>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_DETAIL_FILTER_TITLE') }}</h4>
                        </div>
                        <div class='col-xs-12 col-lg-12 col-xxl-6 mt-5'>
                            <ozon-field
                                v-bind:field='data.FIELDS.FILTER'
                                type='filter'
                                v-on:actionUpdateField='actionUpdateField'
                            />
                            <div class='mt-15'>
                                {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_DETAIL_PRODUCTS_FOUND') }}
                                <span class='badge badge-primary f-14' v-html='data.FIND'></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <button type='button' class='btn btn-warning text-dark m-b-30'
                    v-on:click='showSettingsOther=!showSettingsOther'
                    v-show='!showSettingsOther'>
                {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_DETAIL_SETTINGS_OTHER') }}
            </button>
            <div class='card mb-6' v-show='showSettingsOther'>
                <div class='card-body'>
                    <div class='row'>
                        <div class='col-md-12'>
                            <h4 class='text-gray-700'>
                                {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_DETAIL_DIMENSION_TITLE') }}</h4>
                        </div>
                        <div class='col-xs-12 col-lg-6 col-xxl-6 mt-5'>
                            <ozon-field
                                v-bind:field='data.FIELDS.WEIGHT'
                                type='select'
                                v-on:actionUpdateField='actionUpdateField'
                            />
                        </div>
                        <div class='col-xs-12 col-lg-6 col-xxl-6 mt-5'>
                            <ozon-field
                                v-bind:field='data.FIELDS.WEIGHT_UNIT'
                                type='select'
                                v-on:actionUpdateField='actionUpdateField'
                            />
                        </div>
                        <div class='col-xs-12 col-lg-6 col-xxl-6 mt-5'>
                            <ozon-field
                                v-bind:field='data.FIELDS.WIDTH'
                                type='select'
                                v-on:actionUpdateField='actionUpdateField'
                            />
                        </div>
                        <div class='col-xs-12 col-lg-6 col-xxl-6 mt-5'>
                            <ozon-field
                                v-bind:field='data.FIELDS.HEIGHT'
                                type='select'
                                v-on:actionUpdateField='actionUpdateField'
                            />
                        </div>
                        <div class='col-xs-12 col-lg-6 col-xxl-6 mt-5'>
                            <ozon-field
                                v-bind:field='data.FIELDS.LENGTH'
                                type='select'
                                v-on:actionUpdateField='actionUpdateField'
                            />
                        </div>
                        <div class='col-xs-12 col-lg-6 col-xxl-6 mt-5'>
                            <ozon-field
                                v-bind:field='data.FIELDS.DIMENSION_UNIT'
                                type='select'
                                v-on:actionUpdateField='actionUpdateField'
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class='col-lg-3'>
            <div class='card mb-6'>
                <div class='card-body'>
                    <ozon-field
                        v-bind:field='data.FIELDS.DATE_CREATED'
                    />
                </div>
            </div>
            <div class='text-center'>
                <button class='btn btn-danger' type='button'
                        data-bs-toggle='popover'
                        data-bs-placement='left'
                        data-bs-custom-class='popover-inverse'
                        data-bs-dismiss='true'
                        data-bs-html='true'
                        v-bind:title='loc.DARNEO_OZON_VUE_PRODUCT_LIST_DELETE_WARNING_H1'
                        v-bind:data-bs-content='getTextDelete(data.ELEMENT_ID)'
                >
                    {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_DETAIL_BUTTON_DELETE') }}
                </button>
            </div>
        </div>
        </div>
    `,
})
</script>