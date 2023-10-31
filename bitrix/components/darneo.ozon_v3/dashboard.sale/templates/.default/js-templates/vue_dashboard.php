<script>
BX.BitrixVue.component('ozon-dashboard-sale', {
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
        category: function () {
            return this.data.CATEGORY
        },
        year: function () {
            return this.data.YEAR
        },
        sum: function () {
            let sum = parseFloat(this.data.SUM)
            return sum.toLocaleString('ru-RU')
        },
        dataShop: function () {
            return this.data.ORDER_SITE
        },
        dataOzon: function () {
            return this.data.ORDER_OZON
        },
    },
    mounted: function () {
        this.$nextTick(function () {
            this.initChart()
        })
    },
    updated: function () {
        this.$nextTick(function () {
            this.chart.destroy()
            this.initChart()
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
            return document.getElementById('kt_apexcharts_3')
        },
        getOptionChart: function () {
            let height = parseInt(KTUtil.css(this.getElementChart(), 'height'))
            let labelColor = KTUtil.getCssVariableValue('--bs-success-500')
            let borderColor = KTUtil.getCssVariableValue('--bs-success-200')
            let baseColor = KTUtil.getCssVariableValue('--bs-success')
            let lightColor = KTUtil.getCssVariableValue('--bs-success-light')

            let options = {
                series: [
                    {
                        name: 'Site',
                        data: this.dataShop
                    },
                    {
                        name: 'Ozon',
                        data: this.dataOzon
                    },
                ],
                chart: {
                    fontFamily: 'inherit',
                    type: 'area',
                    height: height,
                    toolbar: {
                        show: false
                    }
                },
                plotOptions: {},
                legend: {
                    show: true,
                    markers: {
                        fillColors: [baseColor, 'rgba(47, 155, 249, 1)'],
                        strokeColors: [baseColor, 'rgba(47, 155, 249, 1)'],
                        strokeWidth: 3
                    }
                },
                dataLabels: {
                    enabled: false
                },
                fill: {
                    type: 'solid',
                    opacity: 1
                },
                stroke: {
                    curve: 'smooth',
                    show: true,
                    width: 3,
                    colors: [baseColor, 'rgba(47, 155, 249, 1)']
                },
                xaxis: {
                    categories: this.category,
                    axisBorder: {
                        show: false,
                    },
                    axisTicks: {
                        show: false
                    },
                    crosshairs: {
                        position: 'front',
                        stroke: {
                            color: baseColor,
                            width: 1,
                            dashArray: 3
                        }
                    },
                    tooltip: {
                        enabled: true,
                        formatter: undefined,
                        offsetY: 0,
                        style: {
                            fontSize: '12px'
                        }
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: labelColor,
                            fontSize: '12px'
                        }
                    }
                },
                states: {
                    normal: {
                        filter: {
                            type: 'none',
                            value: 0
                        }
                    },
                    hover: {
                        filter: {
                            type: 'none',
                            value: 0
                        }
                    },
                    active: {
                        allowMultipleDataPointsSelection: false,
                        filter: {
                            type: 'none',
                            value: 0
                        }
                    }
                },
                tooltip: {
                    enabled: true,
                    style: {
                        fontSize: '12px'
                    },
                    y: {
                        formatter: function (val) {
                            return val.toLocaleString('ru-RU')
                        }
                    }
                },
                colors: [lightColor, 'rgba(211, 234, 253, 0.5)'],
                grid: {
                    borderColor: [borderColor, 'rgba(47, 155, 249, 1)'],
                    strokeDashArray: 4,
                    yaxis: {
                        lines: {
                            show: true
                        }
                    }
                },
                markers: {
                    strokeColor: [baseColor, 'rgba(47, 155, 249, 1)'],
                    strokeWidth: 3
                },
            }

            return options
        },
    },
    template: `
        <div class='card card-flush overflow-hidden h-md-100 pb-5'>
        <div class='card-header py-5'>
            <h3 class='card-title align-items-start flex-column'>
                <span class='card-label fw-bold text-dark'>
                    {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_DASHBOARD_SALE_TITLE') }}
                </span>
                <span class='text-gray-400 mt-1 fw-semibold fs-6'>
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
        <div class='card-body d-flex justify-content-between flex-column pb-1 px-0'>
            <div id='kt_apexcharts_3' class='min-h-auto ps-4 pe-6' style='height: 350px'></div>
        </div>
        </div>
    `,
})
</script>