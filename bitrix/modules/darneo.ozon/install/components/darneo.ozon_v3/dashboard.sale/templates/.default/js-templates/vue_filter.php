<script>
BX.BitrixVue.component('ozon-dashboard-filter', {
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
    methods: {
        actionSetPeriod: function (value) {
            this.$emit('actionSetPeriod', value)
        },
    },
    template: `
        <div class='d-flex flex-end align-items-center gap-2 gap-lg-3 mb-5'>
        <ozon-dashboard-filter-item
            v-for='item in data.FILTER' :key='String(item.VALUE)'
            v-on:actionSetPeriod='actionSetPeriod'
            v-bind:item='item'
            v-bind:request='request'
            :class="{
                'btn-primary': item.ACTIVE,
                'fw-bold bg-body btn-color-gray-700 btn-active-color-primary': !item.ACTIVE,
                'disabled': request.isUpdateList
            }"
        />
        </div>
    `,
})
</script>