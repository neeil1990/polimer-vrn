<script>
BX.BitrixVue.component('ozon-field', {
    props: {
        field: {
            type: Object,
            required: false
        },
        isOnlyEdit: {
            type: Boolean,
            required: false
        },
        type: {
            type: String,
            required: false
        },
    },
    data: function () {
        return {
            isSendTrigger: false,
            isShowEdit: this.isOnlyEdit
        }
    },
    computed: {
        isEdit: function () {
            return typeof this.field.EDIT !== 'undefined'
        },
    },
    watch: {
        field: function () {
            if (this.isSendTrigger === false && this.isOnlyEdit !== true) {
                this.isShowEdit = false
            } else {
                this.isSendTrigger = false
            }
        }
    },
    methods: {
        actionUpdateField: function (dataForm) {
            this.$emit('actionUpdateField', dataForm)
        },
        isButtonEdit: function () {
            return this.isEdit && this.isShowEdit === false
        },
        showEdit: function () {
            this.isShowEdit = true
        },
        showBlock: function () {
            this.isShowEdit = false
        },
        isEditTypeInput: type => type === 'input',
        isEditTypeSelect: type => type === 'select',
        isEditTypeBoolean: type => type === 'boolean',
    },
    template: `
        <div>
        <div class='detail-product--trigger field-title'>
            <!--<span v-html='field.NAME'></span>-->
        </div>
        <div v-if='!isShowEdit'>
            <a href='javascript:void(0)' class='btn-edit f-right' v-if='isButtonEdit()' v-on:click='showEdit()'>
                <svg class='icon_edit'>
                    <use
                        xlink:href='/bitrix/templates/darneo.ozon_v3/image/sprite.svg#icon_edit'></use>
                </svg>
            </a>
            <div v-html='field.SHOW'></div>
        </div>
        <template v-if='isShowEdit'>
            <template v-if='isEditTypeInput(type)'>
                <ozon-field-edit-input
                    v-bind:data='field.EDIT'
                    v-bind:code='field.CODE'
                    v-on:actionUpdateField='actionUpdateField'
                    v-on:showBlock='showBlock'
                />
            </template>
            <template v-if='isEditTypeSelect(type)'>
                <ozon-field-edit-select
                    v-bind:data='field.EDIT'
                    v-bind:code='field.CODE'
                    v-on:actionUpdateField='actionUpdateField'
                    v-on:showBlock='showBlock'
                />
            </template>
            <template v-if='isEditTypeBoolean(type)'>
                <ozon-field-edit-boolean
                    v-bind:data='field.EDIT'
                    v-bind:code='field.CODE'
                    v-on:actionUpdateField='actionUpdateField'
                    v-on:showBlock='showBlock'
                />
            </template>
        </template>
        </div>
    `,
})
</script>