<script>
BX.BitrixVue.component('ozon-key-list-add', {
    props: {
        item: {
            type: Object,
            required: false
        },
    },
    data: function () {
        return {
            clientId: '',
            apiKey: '',
            name: '',
            isMain: '',
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
                this.$emit('actionAdd', this.clientId, this.apiKey, this.name, this.isMain)
            }
        },
        isActiveButton: function () {
            return this.clientId.length > 0 && this.apiKey.length > 0
        },
    },
    template: `
        <tr>
        <td>
            <input class='form-control' type='text'
                   v-bind:placeholder='loc.DARNEO_OZON_VUE_KEY_LIST_PLACEHOLDER_INPUT_KEY_NAME' v-model='name'>
        </td>
        <td>
            <input class='form-control' type='text'
                   v-bind:placeholder='loc.DARNEO_OZON_VUE_KEY_LIST_PLACEHOLDER_INPUT_CLIENT_ID' v-model='clientId'>
        </td>
        <td>
            <input class='form-control' type='text'
                   v-bind:placeholder='loc.DARNEO_OZON_VUE_KEY_LIST_PLACEHOLDER_INPUT_KEY_API' v-model='apiKey'>
        </td>
        <td>
            <label class='form-check form-switch form-check-custom form-check-solid'>
                <input class='form-check-input' type='checkbox' v-model='isMain' true-value='1' false-value='0'/>
            </label>
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