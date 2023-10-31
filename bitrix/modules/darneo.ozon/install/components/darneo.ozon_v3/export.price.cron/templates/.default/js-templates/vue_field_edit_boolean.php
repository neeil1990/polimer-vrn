<script>
BX.BitrixVue.component('ozon-field-edit-boolean', {
    props: {
        data: {
            type: Object,
            required: false
        },
        code: {
            type: String,
            required: true
        },
    },
    data: function () {
        return {
            inputValue: '',
        }
    },
    computed: {
        isCheck: function () {
            return Number(this.data.VALUE)
        },
    },
    watch: {
        data: function () {
            this.initEditValue()
        },
    },
    mounted: function () {
        this.$nextTick(function () {
            this.initEditValue()
        })
    },
    methods: {
        initEditValue: function () {
            this.inputValue = Number(this.data.VALUE)
        },
        actionUpdateField: function () {
            let data = {}
            data[this.code] = !(Boolean(this.inputValue) === true)
            data['signedParamsString'] = this.data.HIDDEN
            this.$emit('actionUpdateField', data)
        },
    },
    template: `
        <div class='detail-edit p-relative'>
        <div class='p-relative mt-2'>
            <label class='form-check form-switch form-check-custom form-check-solid'>
                <input class='form-check-input' type='checkbox' v-bind:checked='isCheck'
                       v-on:change='actionUpdateField()'/>
            </label>
        </div>
        </div>
    `,
})
</script>