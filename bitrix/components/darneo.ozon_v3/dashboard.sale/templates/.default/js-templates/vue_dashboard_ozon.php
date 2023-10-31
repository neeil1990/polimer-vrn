<script>
BX.BitrixVue.component('ozon-dashboard-ozon', {
    props: {
        data: {
            type: Object,
            required: true
        },
        request: {
            type: Object,
            required: false
        },
    },
    data: function () {
        return {
            chart: ''
        }
    },
    computed: {
        percent: function () {
            if (Number(this.data.SUM) === 0) {
                return 0
            }
            return Math.round((this.data.SUM_OZON / this.data.SUM) * 100)
        },
        sumOzon: function () {
            return Number(this.data.SUM_OZON)
        },
        year: function () {
            return this.data.YEAR
        },
    },
    mounted: function () {
        this.$nextTick(function () {
            this.initChart()
            this.animateNumber()
        })
    },
    updated: function () {
        this.$nextTick(function () {
            this.chart.destroy()
            this.initChart()
            this.animateNumber()
        })
    },
    methods: {
        actionSetYearPlus: function () {
            let year = Number(this.data.YEAR) + 1
            let currentYear = new Date().getFullYear()
            if (year <= currentYear) {
                this.$emit('actionSetYear', year)
            }
        },
        actionSetYearMinus: function () {
            let year = Number(this.data.YEAR) - 1
            this.$emit('actionSetYear', year)
        },
        initChart: function () {
            this.chart = new ApexCharts(this.getElementChart(), this.getOptionChart())
            this.chart.render()
        },
        getElementChart: function () {
            return document.getElementById('kt_apexcharts_ozon')
        },
        getOptionChart: function () {
            let element = this.getElementChart()

            let height = parseInt(KTUtil.css(element, 'height'))
            let baseColor = 'rgba(47, 155, 249, 1)'
            let lightColor = 'rgba(211, 234, 253, 0.5)'

            let options = {
                series: [this.percent],
                chart: { fontFamily: 'inherit', height: height, type: 'radialBar' },
                plotOptions: {
                    radialBar: {
                        hollow: { margin: 0, size: '65%' },
                        dataLabels: {
                            showOn: 'always',
                            name: { show: !1, fontWeight: '700' },
                            value: {
                                color: baseColor,
                                fontSize: '30px',
                                fontWeight: '700',
                                offsetY: 12,
                                show: !0,
                                formatter: function (element) {
                                    return element + '%'
                                }
                            }
                        },
                        track: { background: lightColor, strokeWidth: '100%' }
                    }
                },
                colors: [baseColor],
                stroke: { lineCap: 'round' },
                labels: ['Progress']
            }

            return options
        },
        animateNumber() {
            const element = document.getElementById('sum_count_ozon')
            const startValue = 0
            const endValue = Number(this.sumOzon)
            const duration = 1000

            customCountUp(element, startValue, endValue, duration)
        },
    },
    template: `
        <div class='card card-xl-stretch mb-xl-8 h-md-100'>
        <div class='card-header border-0 py-5'>
            <h3 class='card-title align-items-start flex-column'>
                    <span class='card-label fw-bold fs-3 mb-1'>
                        {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_DASHBOARD_OZON_TITLE') }}
                    </span>
                <span class='text-muted fw-semibold fs-7'>
                        {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_DASHBOARD_SALE_DESC') }}
                    </span>
            </h3>
            <div class='card-toolbar'>
                <ul class='nav'>
                    <li class='nav-item' v-show='data.IS_FILTER_YEAR'>
                        <a class='nav-link btn btn-sm btn-color-muted btn-active btn-active-light fw-bold px-4 me-1'
                           v-on:click='actionSetYearMinus()'>
                            <i class='ki-duotone ki-left fs-2'></i>
                        </a>
                    </li>
                    <li class='nav-item'>
                        <a class='nav-link btn btn-sm btn-color-muted btn-active btn-active-light fw-bold px-4 me-1 active'
                           v-html='year'></a>
                    </li>
                    <li class='nav-item' v-show='data.IS_FILTER_YEAR'>
                        <a class='nav-link btn btn-sm btn-color-muted btn-active btn-active-light fw-bold px-4 me-1'
                           v-on:click='actionSetYearPlus()'>
                            <i class='ki-duotone ki-right fs-2'></i>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        <div class='card-body d-flex flex-column'>
            <div class='flex-grow-1'>
                <div class='mixed-widget-4-chart' id='kt_apexcharts_ozon' data-kt-chart-color='primary'
                     style='height: 200px'></div>
            </div>
            <div class='pt-5'>
                <div class='px-9 card-rounded w-100 bg-primary'>
                    <div class='d-flex text-center flex-column text-white pt-8 pb-8'>
                        <span class='fw-bold fs-2x pt-1' id='sum_count_ozon'></span>
                    </div>
                </div>
            </div>
        </div>
        </div>
    `,
})
</script>