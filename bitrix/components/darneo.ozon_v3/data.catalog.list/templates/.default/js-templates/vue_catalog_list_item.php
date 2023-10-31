<script>
BX.BitrixVue.component('ozon-catalog-list-item', {
    props: {
        item: {
            type: Object,
            required: true
        },
    },
    computed: {
        status: function () {
            return this.item.JSON.status
        },
        price: function () {
            return {
                current: this.item.JSON.price,
                old: this.item.JSON.old_price,
            }
        },
        tooltip: function () {
            if (this.item.IS_ERROR) {
                let errors = []
                for (let key in this.status.item_errors) {
                    let item = this.status.item_errors[key]
                    let errorText = item.attribute_name + ': ' + item.description
                    errors.push(errorText)
                }
                return errors.join(', ')
            }
            if (this.status.state_tooltip.length) {
                return this.status.state_tooltip
            }
            return ''
        },
        tooltipCommission: function () {
            let data = []
            for (let key in this.item.JSON.commissions) {
                let item = this.item.JSON.commissions[key]
                let dataText = item.sale_schema + ': ' + item.value + ' (' + item.percent + '%)'
                data.push(dataText)
            }
            return data.join(', ')
        }
    },
    mounted: function () {
        this.$nextTick(function () {
            BX.UI.Hint.init(BX('basic-1'))
        })
    },
    updated: function () {
        this.$nextTick(function () {
            BX.UI.Hint.init(BX('basic-1'))
        })
    },
    methods: {
        showDataJson: function (data) {
            this.$emit('setDataJson', data)
        },
    },
    template: `
        <tr>
        <td v-html='item.OFFER_ID'></td>
        <td v-html='item.NAME'></td>
        <td>
            <div class='d-flex'>
                <span v-if='item.IS_ERROR' class='badge rounded-pill badge-danger' v-html='item.STATUS_NAME'></span>
                <span v-else class='badge rounded-pill badge-light text-dark' v-html='item.STATUS_NAME'></span>
                <a v-if='tooltip.length' href='javascript:void(0)' class='ms-2'
                   data-bs-toggle='popover'
                   data-bs-placement='left'
                   data-bs-custom-class='popover-inverse'
                   data-bs-dismiss='true'
                   data-bs-html='true'
                   v-bind:data-bs-content='tooltip'
                >
                    <span class='ui-hint-icon'></span>
                </a>
            </div>
        </td>
        <td v-html='item.CATEGORY_NAME'></td>
        <td>
            <div>{{ item.STOCK_FBO }}</div>
            <small v-if='item.STOCK_FBO_RESERVED > 0'>
                {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_CATALOG_LIST_RESERVED') }}
                <span v-html='item.STOCK_FBO_RESERVED'></span>
            </small>
        </td>
        <td>
            <div>{{ item.STOCK_FBS }}</div>
            <small v-if='item.STOCK_FBS_RESERVED > 0'>
                {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_CATALOG_LIST_RESERVED') }}
                <span v-html='item.STOCK_FBS_RESERVED'></span>
            </small>
        </td>
        <td>
            <div v-if='price.old.length && price.current !== price.old'>
                <del>
                    <darneo-ozon-price v-bind:price='Number(price.old)'/>
                </del>
            </div>
            <div v-if='price.current.length'>
                <darneo-ozon-price v-bind:price='Number(price.current)'/>
            </div>
            <div v-if='tooltipCommission.length' class='d-flex flex-center'>
                <small>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_CATALOG_LIST_COMMISSION_TITLE') }}</small>
                <a v-if='tooltip.length' href='javascript:void(0)' class='ms-2'
                   data-bs-toggle='popover'
                   data-bs-placement='left'
                   data-bs-custom-class='popover-inverse'
                   data-bs-dismiss='true'
                   data-bs-html='true'
                   v-bind:data-bs-content='tooltipCommission'
                >
                    <span class='ui-hint-icon'></span>
                </a>
            </div>
        </td>
        <td>
            <button class='btn btn-primary' type='button' v-on:click='showDataJson(item)'>
                <span>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_CATALOG_LIST_BUTTON_JSON') }}</span>
            </button>
        </td>
        </tr>
    `,
})
</script>