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
            inputValue: Number(this.data.VALUE),
        }
    },
    watch: {
        inputValue: function (val, old) {
            if (val !== old) {
                this.actionUpdateField()
            }
        },
    },
    methods: {
        actionUpdateField: function () {
            let data = {}
            data[this.code] = this.inputValue
            data['signedParamsString'] = this.data.HIDDEN
            this.$emit('actionUpdateField', data)
        },
    },
    template: `
        <div class='detail-edit p-relative'>
        <div>
            <div class='p-relative mt-2'>
                <label class='form-check form-switch form-check-custom form-check-solid'>
                    <input class='form-check-input' type='checkbox' v-model='inputValue' true-value='1' false-value='0'/>
                </label>
            </div>
        </div>
        </div>
    `,
})
</script>