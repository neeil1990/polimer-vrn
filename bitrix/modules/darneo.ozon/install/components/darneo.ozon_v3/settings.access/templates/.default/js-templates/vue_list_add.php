<script>
BX.BitrixVue.component('ozon-access-list-add', {
    props: {
        group: {
            type: Array,
            required: false
        },
    },
    data: function () {
        return {
            selectedDefault: '',
            groupId: '',
        }
    },
    computed: {
        loc: function () {
            return BX.BitrixVue.getFilteredPhrases('DARNEO_OZON_')
        },
    },
    methods: {
        actionAdd: function () {
            if (this.isActiveButton()) {
                this.$emit('actionAdd', this.groupId)
            }
        },
        isActiveButton: function () {
            return this.groupId.length > 0
        },
        getValues: function (data) {
            let arr = []
            for (let key in data) {
                let item = data[key]
                let row = {}
                row['id'] = item.ID
                row['text'] = item.NAME + ' [' + item.ID + ']'
                row['selected'] = false
                arr.push(row)
            }
            return arr
        },
    },
    template: `
        <tr>
        <td></td>
        <td>
            <div class='d-flex'>
                <div class='w-100'>
                    <darneo-ozon-select
                        v-bind:options='getValues(group)'
                        v-bind:value='groupId'
                        v-bind:placeholder='loc.DARNEO_OZON_VUE_ACCESS_LIST_PLACEHOLDER_GROUP_ID'
                        v-on:input='groupId = $event'
                    />
                </div>
                <a class='m-2' href='javascript:void(0)' v-on:click='groupId=selectedDefault'
                   v-show='groupId !== selectedDefault'>
                    <i class='ki-duotone ki-cross-square fs-2x'>
                        <i class='path1'></i>
                        <i class='path2'></i>
                    </i>
                </a>
            </div>
        </td>
        <td>
            <a href='javascript:void(0)' class='btn btn-icon btn-bg-light btn-active-color-primary btn-sm'
               v-bind:disabled='!isActiveButton()' v-on:click='actionAdd()'>
                <i class='ki-duotone ki-add-item fs-2'>
                    <i class='path1'></i>
                    <i class='path2'></i>
                    <i class='path3'></i>
                </i>
            </a>
        </td>
        </tr>
    `,
})
</script>