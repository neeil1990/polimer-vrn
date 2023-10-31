<script>
BX.BitrixVue.component('ozon-order-exchange', {
    props: {
        data: {
            type: Object,
            required: false
        },
        page: {
            type: Number,
            required: false
        },
        isStart: {
            type: Boolean,
            required: false
        },
        isFinished: {
            type: Boolean,
            required: false
        },
    },
    data: function () {
        return {
            isShowStatus: false,
            isShowBlock: false,
        }
    },
    watch: {
        'data': function () {
            if (this.isStart) {
                this.isShowStatus = true
                this.actionStart()
            } else {
                this.isShowStatus = false
            }
        },
    },
    methods: {
        actionStart: function () {
            let nextPage = this.page + 1
            this.$emit('actionStart', nextPage)
        },
        getWidth: function () {
            return 'width: ' + Number(this.data.COUNT_CURRENT * 100 / this.data.COUNT_ALL) + '%'
        },
    },
    template: `
        <div>
        <div v-if='isShowBlock'>
            <div class='card mb-5'>
                <div class='card-header'>
                    <h3 class='card-title'>
                        {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_ORDER_LIST_EXCHANGE_TITLE') }}
                    </h3>
                    <div class='card-toolbar'>
                        <span>
                            <span v-html='data.COUNT_HELPER'></span>
                            <span v-html='data.COUNT_ALL'></span>
                        </span>
                    </div>
                </div>
                <div class='card-body'>
                    <div class='progress-showcase row'>
                        <div class='col'>
                            <div class='progress'>
                                <div class='progress-bar-animated bg-primary progress-bar-striped'
                                     role='progressbar'
                                     v-bind:style=getWidth()
                                     v-bind:aria-valuenow=data.COUNT_CURRENT_FORMATED
                                     aria-valuemin='0'
                                     v-bind:aria-valuemax=data.COUNT_ALL_FORMATED></div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <span v-if='isShowStatus' v-html='data.STATUS_HELPER'></span>
                    </div>
                    <div class='d-flex align-items-center'>
                        <button class='btn btn-primary mt-5' type='button' v-on:click='actionStart()'
                                v-bind:disabled='isStart'>
                            <span v-show='!isStart'>
                                {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_ORDER_LIST_EXCHANGE_BUTTON_START') }}
                            </span>
                            <span v-show='isStart'>
                                {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_ORDER_LIST_EXCHANGE_BUTTON_WORK') }}
                            </span>
                            <i class='fa fa-spin fa-spinner' v-show='isStart'></i>
                        </button>
                        <span class='badge badge-warning text-dark ms-10 mt-5' v-show='!isStart && isFinished'>
                            {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_ORDER_LIST_EXCHANGE_COMPLETE') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div v-else class='d-flex flex-end align-items-center gap-2 gap-lg-3 mb-5'>
            <a href='javascript:void(0)' class='btn btn-sm btn-warning text-dark'
               v-on:click='isShowBlock=!isShowBlock'>
                {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_ORDER_LIST_EXCHANGE_BUTTON_SHOW') }}
            </a>
        </div>
        </div>

    `,
})
</script>