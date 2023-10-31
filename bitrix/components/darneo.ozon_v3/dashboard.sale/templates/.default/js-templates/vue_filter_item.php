<script>
BX.BitrixVue.component('ozon-dashboard-filter-item', {
    props: {
        item: {
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
            isSend: false
        }
    },
    watch: {
        item: function () {
            this.isSend = false
        },
    },
    methods: {
        actionSetPeriod: function (value) {
            this.isSend = true
            this.$emit('actionSetPeriod', value)
        },
    },
    template: `
        <a href='javascript:void(0)' class='btn btn-sm' v-on:click='actionSetPeriod(item.VALUE)'>
        {{ item.TITLE }}
        <i class='fa fa-spin fa-spinner' v-show='isSend'></i>
        </a>
    `,
})
</script>