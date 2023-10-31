<script>
BX.BitrixVue.component('ozon-cron-list-item', {
    props: {
        item: {
            type: Object,
            required: true
        },
    },
    data: function () {
        return {
            isActive: this.item.VALUE,
        }
    },
    watch: {
        isActive: function (value, old) {
            if (value !== old) {
                this.actionUpdate(value)
            }
        },
    },
    mounted: function () {
        this.$nextTick(function () {
            BX.UI.Hint.init(BX('basic-1'))
        })
    },
    methods: {
        actionUpdate: function (value) {
            this.$emit('actionUpdate', this.item.CODE, value)
        },
    },
    template: `
        <tr>
        <td v-html='item.TITLE'></td>
        <td>
            <div class='d-flex flex-center'>
                <div class='alert alert-primary d-flex align-items-center p-5'>
                    <div class='d-flex flex-column'>
                        <span v-html='item.DESCRIPTION'></span>
                    </div>
                </div>
                <span v-if='item.HELPER.length' v-bind:data-hint='item.HELPER'></span>
            </div>
        </td>
        <td>
            <div v-html='item.DATE_START'></div>
            <span v-if='item.IS_ERROR' class='badge badge-danger'>
                {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_CRON_LIST_IS_ERROR') }}
            </span>
            <span v-else-if='item.IS_STARTED' class='badge badge-primary'>
                {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_CRON_LIST_IS_STARTED') }}
            </span>
        </td>
        <td v-html='item.DATE_FINISH'></td>
        <td>
            <label class='form-check form-switch form-check-custom form-check-solid'>
                <input class='form-check-input' type='checkbox' v-model='isActive' true-value='1' false-value='0'/>
            </label>
        </td>
        </tr>
    `,
})
</script>