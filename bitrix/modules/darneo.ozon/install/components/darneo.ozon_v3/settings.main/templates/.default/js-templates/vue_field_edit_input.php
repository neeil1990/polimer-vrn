<script>
BX.BitrixVue.component('ozon-field-edit-input', {
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
            isSendSave: false
        }
    },
    watch: {
        data: function () {
            this.initEditValue()
            this.isSendSave = false
        },
    },
    mounted: function () {
        this.$nextTick(function () {
            this.initEditValue()
            this.$refs.input.focus()
        })
    },
    methods: {
        initEditValue: function () {
            if (this.data.VALUE) {
                this.inputValue = this.data.VALUE
            }
        },
        actionUpdateField: function () {
            if (!this.isSendSave) {
                this.isSendSave = true
                let data = {}
                data[this.code] = this.inputValue
                data['signedParamsString'] = this.data.HIDDEN
                this.$emit('actionUpdateField', data)
            }
        },
        showBlock: function () {
            if (!this.isSendSave) {
                this.$emit('showBlock')
            }
        },

    },
    template: `
        <div class='detail-edit p-relative'>
        <div v-bind:class='{disabled:isSendSave}'>
            <input type='text' v-model='inputValue' v-bind:disabled='isSendSave' ref='input' class='form-control'/>
            <div class='p-relative mt-2'>
                <button type='button' class='btn btn-primary btn-xs fs-8'
                        v-on:click='actionUpdateField()'>
                    <span>
                        {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_SETTINGS_MAIN_BUTTON_SAVE') }}
                    </span>
                    <i class='fa fa-spin fa-spinner' v-show='isSendSave'></i>
                </button>
                <button type='button' class='btn btn-light btn-xs' v-on:click='showBlock()'>
                    {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_SETTINGS_MAIN_BUTTON_CANCEL') }}
                </button>
            </div>
        </div>
        </div>
    `,
})
</script>