<script>
BX.BitrixVue.component('ozon-access-list-item', {
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
    },
    template: `
        <tr>
        <td v-html='item.ID'></td>
        <td v-html='item.NAME'></td>
        <td>
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