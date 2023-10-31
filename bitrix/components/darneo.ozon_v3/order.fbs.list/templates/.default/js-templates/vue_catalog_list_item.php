<script>
BX.BitrixVue.component('ozon-catalog-list-item', {
    props: {
        item: {
            type: Object,
            required: true
        },
    },
    template: `
        <tr>
        <td v-html='item.IN_PROCESS_AT'></td>
        <td v-html='item.POSTING_NUMBER'></td>
        <td>
            <span v-if='item.IS_ERROR' class='badge rounded-pill badge-danger' v-html='item.STATUS_NAME'></span>
            <span v-else-if='item.IS_NEW' class='badge rounded-pill badge-primary' v-html='item.STATUS_NAME'></span>
            <span v-else-if='item.IS_FINISH' class='badge rounded-pill badge-light text-dark'
                  v-html='item.STATUS_NAME'></span>
            <span v-else class='badge rounded-pill badge-warning text-dark' v-html='item.STATUS_NAME'></span>
            <span v-if='item.CANCELLATION.cancel_reason.length'
                  v-bind:data-hint='item.CANCELLATION.cancel_reason'></span>
        </td>
        <td>
            <div v-for='product in item.PRODUCTS' :key='String(product.offer_id)'>
                <div class='text-grey'>
                    <span v-html='product.offer_id'></span>, <span v-html='product.quantity'></span>
                    <span>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_ORDER_LIST_UNIT') }}</span>
                </div>
                <small v-html='product.name'></small>
            </div>
        </td>
        <td>
            <darneo-ozon-price v-bind:price='Number(item.SUM)'/>
        </td>
        <td v-html='item.DELIVERY_METHOD.warehouse'></td>
        <td>
            <div class='text-grey'>
                <span v-html='item.DELIVERY_METHOD.tpl_provider'></span>
            </div>
            <small v-html='item.DELIVERY_METHOD.name'></small>
        </td>
        <td v-html='item.SHIPMENT_DATE'></td>
        </tr>
    `,
})
</script>