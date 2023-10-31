<script>
BX.BitrixVue.component('ozon-key-list-item', {
    props: {
        item: {
            type: Object,
            required: true
        },
    },
    methods: {
        actionDelete: function (rowId) {
            this.$emit('actionDelete', rowId)
        },
        showUpdate: function (row) {
            this.$emit('showUpdate', row)
        },
    },
    template: `
        <tr>
        <td v-html='item.NAME'></td>
        <td v-html='item.CLIENT_ID'></td>
        <td v-html='item.KEY'></td>
        <td v-html='item.DEFAULT_HTML'></td>
        <td>
            <a href='javascript:void(0)' class='btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1'
               v-on:click='showUpdate(item)'>
                <i class='ki-duotone ki-pencil fs-2'>
                    <span class='path1'></span>
                    <span class='path2'></span>
                </i>
            </a>
            <a href='javascript:void(0)' class='btn btn-icon btn-bg-light btn-active-color-primary btn-sm'
               v-on:click='actionDelete(item.ID)'>
                <i class='ki-duotone ki-trash fs-2'>
                    <span class='path1'></span>
                    <span class='path2'></span>
                    <span class='path3'></span>
                    <span class='path4'></span>
                    <span class='path5'></span>
                </i>
            </a>
        </td>
        </tr>
    `,
})
</script>