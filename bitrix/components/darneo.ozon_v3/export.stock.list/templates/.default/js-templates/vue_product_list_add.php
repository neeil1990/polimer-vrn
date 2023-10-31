<script>
BX.BitrixVue.component('ozon-product-list-add', {
    props: {
        item: {
            type: Object,
            required: false
        },
        iblock: {
            type: Array,
            required: false
        },
    },
    data: function () {
        return {
            selectedDefault: 0,
            iblockId: 0,
            name: '',
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
                this.$emit('actionAdd', this.iblockId, this.name)
            }
        },
        isActiveButton: function () {
            return this.iblockId.length > 0
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
        <div class='d-flex flex-end align-items-center gap-2 gap-lg-3 mb-5'>
        <a href='javascript:void(0)' class='btn btn-sm btn-primary' data-bs-toggle='modal' data-bs-target='#kt_modal_1'>
            {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_STOCK_LIST_BUTTON_ADD') }}
        </a>
        <div class='modal' tabindex='-1' role='dialog' id='kt_modal_1'>
            <div class='modal-dialog modal-dialog-centered' role='document'>
                <div class='modal-content'>
                    <div class='modal-header'>
                        <h5 class='modal-title' v-html='loc.DARNEO_OZON_VUE_STOCK_LIST_FORM_ADD'></h5>
                        <button class='btn-close' type='button' data-bs-dismiss='modal' aria-label='Close'></button>
                    </div>
                    <div class='modal-body'>
                        <div class='mb-5'>
                            <label class='form-label' v-html='loc.DARNEO_OZON_VUE_STOCK_LIST_PLACEHOLDER_INPUT_UPLOAD_NAME'></label>
                            <input class='form-control' type='text' v-model='name'>
                        </div>
                        <div class='mb-5'>
                            <label class='form-label' v-html='loc.DARNEO_OZON_VUE_STOCK_LIST_PLACEHOLDER_IBLOCK'></label>
                            <div class='d-flex'>
                                <div class='w-100'>
                                    <darneo-ozon-select
                                        v-bind:options='getValues(iblock)'
                                        v-bind:value='iblockId'
                                        v-bind:placeholder='loc.DARNEO_OZON_VUE_STOCK_LIST_PLACEHOLDER_SELECT_IBLOCK'
                                        v-on:input='iblockId = $event'
                                    />
                                </div>
                                <a class='m-2' href='javascript:void(0)' v-on:click='iblockId=selectedDefault'
                                   v-show='iblockId !== selectedDefault'>
                                    <i class='ki-duotone ki-cross-square fs-2x'>
                                        <i class='path1'></i>
                                        <i class='path2'></i>
                                    </i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class='modal-footer'>
                        <a href='javascript:void(0)' class='btn btn-light' data-bs-dismiss='modal'>
                            {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_STOCK_LIST_BUTTON_CANCEL') }}
                        </a>
                        <a href='javascript:void(0)' class='btn btn-primary'
                           :class={disabled:!isActiveButton()} v-on:click='actionAdd()'>
                            {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_STOCK_LIST_BUTTON_ADD') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
        </div>
    `,
})
</script>