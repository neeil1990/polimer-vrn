<script>
BX.BitrixVue.component('ozon-product-widget', {
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
            BX.UI.Hint.init(BX('product_widget'))
        })
    },
    methods: {
        calculatePercentage(total, current) {
            if (total > 0 && current > 0) {
                const percentage = (current / total) * 100
                return Math.round(percentage)
            }
            return 0
        }
    },
    template: `
        <div class='row g-5 g-xl-8 mt-5' id='product_widget'>
        <div class='col-xl-4'>
            <div class='card bg-light-success card-xl-stretch mb-xl-8'>
                <div class='card-body my-3'>
                    <span class='card-title fw-bold text-success fs-5 mb-3 d-block'>
                        {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_WIDGET_1') }}
                        <span v-bind:data-hint='loc.DARNEO_OZON_VUE_PRODUCT_WIDGET_1_HELPER'></span>
                    </span>
                    <div class='py-1'>
                        <span class='text-dark fs-1 fw-bold me-2'>
                            {{ calculatePercentage(data.TOTAL.LIMIT, data.TOTAL.USAGE) }}%
                        </span>
                        <span class='fw-semibold text-muted fs-7' v-html='data.TOTAL.LIMIT_TEXT'></span>
                    </div>
                    <div class='progress h-7px bg-success bg-opacity-50 mt-7'>
                        <div class='progress-bar bg-success'
                             role='progressbar'
                             :style="{ width: calculatePercentage(data.TOTAL.LIMIT, data.TOTAL.USAGE) + '%' }"
                             :aria-valuenow='calculatePercentage(data.TOTAL.LIMIT, data.TOTAL.USAGE)'
                             aria-valuemin='0'
                             aria-valuemax='100'
                        ></div>
                    </div>
                </div>
            </div>
        </div>
        <div class='col-xl-4'>
            <div class='card bg-light-warning card-xl-stretch mb-xl-8'>
                <div class='card-body my-3'>
                    <span class='card-title fw-bold text-warning fs-5 mb-3 d-block'>
                         {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_WIDGET_2') }}
                        <span v-bind:data-hint='loc.DARNEO_OZON_VUE_PRODUCT_WIDGET_2_HELPER'></span>
                    </span>
                    <div class='py-1'>
                        <span class='text-dark fs-1 fw-bold me-2'>
                            {{ calculatePercentage(data.CREATE.LIMIT, data.CREATE.USAGE) }}%
                        </span>
                        <span class='fw-semibold text-muted fs-7' v-html='data.CREATE.LIMIT_TEXT'></span>
                    </div>
                    <div class='progress h-7px bg-warning bg-opacity-50 mt-7'>
                        <div class='progress-bar bg-warning'
                             role='progressbar'
                             :style="{ width: calculatePercentage(data.CREATE.LIMIT, data.CREATE.USAGE) + '%' }"
                             :aria-valuenow='calculatePercentage(data.CREATE.LIMIT, data.CREATE.USAGE)'
                             aria-valuemin='0'
                             aria-valuemax='100'
                        ></div>
                    </div>
                </div>
            </div>
        </div>
        <div class='col-xl-4'>
            <div class='card bg-light-primary card-xl-stretch mb-xl-8'>
                <div class='card-body my-3'>
                    <span class='card-title fw-bold text-primary fs-5 mb-3 d-block'>
                         {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_WIDGET_3') }}
                        <span v-bind:data-hint='loc.DARNEO_OZON_VUE_PRODUCT_WIDGET_3_HELPER'></span>
                    </span>
                    <div class='py-1'>
                        <span class='text-dark fs-1 fw-bold me-2'>
                             {{ calculatePercentage(data.UPDATE.LIMIT, data.UPDATE.USAGE) }}%
                        </span>
                        <span class='fw-semibold text-muted fs-7' v-html='data.UPDATE.LIMIT_TEXT'></span>
                    </div>
                    <div class='progress h-7px bg-primary bg-opacity-50 mt-7'>
                        <div class='progress-bar bg-primary'
                             role='progressbar'
                             :style="{ width: calculatePercentage(data.UPDATE.LIMIT, data.UPDATE.USAGE) + '%' }"
                             :aria-valuenow='calculatePercentage(data.UPDATE.LIMIT, data.UPDATE.USAGE)'
                             aria-valuemin='0'
                             aria-valuemax='100'
                        ></div>
                    </div>
                </div>
            </div>
        </div>
        </div>
    `,
})
</script>