<script>
BX.BitrixVue.component('ozon-product-list-item', {
    props: {
        item: {
            type: Object,
            required: true
        },
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
                let link = 'element_delete_' + vm.item.ID
                if (popover && document.getElementById(link) && event.target.id === link) {
                    vm.actionDelete(vm.item.ID)
                }
            })
        })
    },
    methods: {
        actionDelete: function (rowId) {
            this.$emit('actionDelete', rowId)
        },
        getLink: function (url) {
            return window.location.pathname = url
        },
        getTextDelete: function (rowId) {
            let text = this.loc.DARNEO_OZON_VUE_PRICE_LIST_DELETE_WARNING
            let button = '<a href="javascript:void(0)" ' +
                'class="btn btn-primary btn-sm link-section ms-2 element_delete_js" id="element_delete_' + rowId + '">' +
                this.loc.DARNEO_OZON_VUE_PRICE_LIST_DELETE_WARNING_YES +
                '</a>'
            return text + ' ' + button
        },
    },
    template: `
        <tr>
        <td v-html='item.TITLE'></td>
        <td v-html='item.TYPE_PRICE_CURRENT_LANG'></td>
        <td v-html='item.PRICE_RATIO'></td>
        <td>
            <i class='ki-duotone ki-check-square text-success fs-2' v-if='item.IS_DISCOUNT_PRICE'>
                <i class='path1'></i>
                <i class='path2'></i>
            </i>
            <i class='ki-duotone ki-cross-square text-danger fs-2' v-else>
                <i class='path1'></i>
                <i class='path2'></i>
            </i>
        </td>
        <td>
            <i class='ki-duotone ki-check-square text-success fs-2' v-if='item.IS_CRON'>
                <i class='path1'></i>
                <i class='path2'></i>
            </i>
            <i class='ki-duotone ki-cross-square text-danger fs-2' v-else>
                <i class='path1'></i>
                <i class='path2'></i>
            </i>
        </td>
        <td>
            <a v-bind:href='item.DETAIL_PAGE_URL'
               class='btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1'>
                <i class='ki-duotone ki-pencil fs-2'>
                    <span class='path1'></span>
                    <span class='path2'></span>
                </i>
            </a>
            <a href='javascript:void(0)' class='btn btn-icon btn-bg-light btn-active-color-primary btn-sm'
               data-bs-toggle='popover'
               data-bs-placement='left'
               data-bs-custom-class='popover-inverse'
               data-bs-dismiss='true'
               data-bs-html='true'
               v-bind:title='loc.DARNEO_OZON_VUE_PRICE_LIST_DELETE_WARNING_H1'
               v-bind:data-bs-content='getTextDelete(item.ID)'
            >
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