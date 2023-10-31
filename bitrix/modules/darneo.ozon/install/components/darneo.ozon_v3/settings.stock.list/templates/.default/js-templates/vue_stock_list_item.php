<script>
BX.BitrixVue.component('ozon-stock-list-item', {
    props: {
        item: {
            type: Object,
            required: true
        },
    },
    template: `
        <tr>
        <td v-html='item.ID'></td>
        <td v-html='item.NAME'></td>
        <td v-html='item.IS_RFBS'></td>
        </tr>
    `,
})
</script>