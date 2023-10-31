<script>
BX.BitrixVue.component('ozon-catalog-filter', {
    props: {
        data: {
            type: Object,
            required: true
        },
        filter: {
            type: String,
            required: false
        },
    },
    data: function () {
        return {}
    },
    methods: {
        setFilter: function (code) {
            this.$emit('setFilter', code)
        },
        isActive: function (code, active) {
            if (this.filter.length > 0) {
                return this.filter === code
            } else {
                return active
            }
        }
    },
    template: `
        <ul class='nav nav-pills m-b-20'>
        <li class='nav-item' v-for='item in data.LIST' :key='item.CODE'>
            <a class='nav-link' v-bind:class='{active:isActive(item.CODE, item.ACTIVE)}' href='javascript:void(0)'
               v-on:click='setFilter(item.CODE)'>
                {{ item.NAME }}
                <span class='badge badge-primary' v-if='item.COUNT > 0 && !isActive(item.CODE, item.ACTIVE)'
                      v-html=item.COUNT></span>
                <span class='badge badge-light text-dark' v-if='item.COUNT > 0 && isActive(item.CODE, item.ACTIVE)'
                      v-html=item.COUNT></span>
                <div class='media' v-show='isActive(item.CODE, item.ACTIVE)'></div>
            </a>
        </li>
        </ul>
    `,
})
</script>