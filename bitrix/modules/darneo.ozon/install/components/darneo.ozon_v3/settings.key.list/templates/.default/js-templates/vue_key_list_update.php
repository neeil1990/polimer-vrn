<script>
BX.BitrixVue.component('ozon-key-list-update', {
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
            rowId: this.item.ID,
            clientId: this.item.CLIENT_ID,
            apiKey: this.item.KEY,
            name: this.item.NAME,
            isMain: this.item.DEFAULT,
        }
    },
    computed: {
        loc: function () {
            return BX.BitrixVue.getFilteredPhrases('DARNEO_OZON_')
        },
    },
    mounted: function () {
        this.$nextTick(function () {
            this.init()
            BX.UI.Hint.init(BX('basic-1'))
        })
    },
    methods: {
        init: function () {
            let vm = this
            $(this.$el).modal('toggle')
            $(this.$el).on('hidden.bs.modal', function () {
                vm.$emit('showUpdate', {})
            })
        },
        actionUpdate: function () {
            if (this.isActiveButton()) {
                this.$emit('actionUpdate', this.rowId, this.clientId, this.apiKey, this.name, this.isMain)
            }
        },
        isActiveButton: function () {
            return this.clientId.length > 0 && this.apiKey.length > 0 && !this.request.isUpdateRow
        },
    },
    template: `
        <div class='modal fade bd-example-modal-lg'>
        <div class='modal-dialog modal-lg'>
            <div class='modal-content'>
                <div class='modal-header'>
                    <h5 class='modal-title'>
                        {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_KEY_LIST_UPDATE_API_KEY_TITLE') }}
                    </h5>
                    <button class='btn-close' type='button' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body'>
                    <div class='mb-10'>
                        <label
                            class='form-label'>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_KEY_LIST_TABLE_HEAD_ID') }}</label>
                        <input type='text' class='form-control' v-bind:value='item.ID' disabled>
                    </div>
                    <div class='mb-10'>
                        <label
                            class='form-label'>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_KEY_LIST_TABLE_HEAD_KEY_NAME') }}</label>
                        <input type='text' class='form-control' v-model='name'
                               v-bind:placeholder='loc.DARNEO_OZON_VUE_KEY_LIST_PLACEHOLDER_INPUT_KEY_NAME'>
                    </div>
                    <div class='mb-10'>
                        <label
                            class='form-label'>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_KEY_LIST_TABLE_HEAD_CLIENT_ID') }}</label>
                        <input type='text' class='form-control' v-model='clientId'
                               v-bind:placeholder='loc.DARNEO_OZON_VUE_KEY_LIST_PLACEHOLDER_INPUT_CLIENT_ID'>
                    </div>
                    <div class='mb-10'>
                        <label
                            class='form-label'>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_KEY_LIST_TABLE_HEAD_KEY_API') }}</label>
                        <input type='text' class='form-control' v-model='apiKey'
                               v-bind:placeholder='loc.DARNEO_OZON_VUE_KEY_LIST_PLACEHOLDER_INPUT_KEY_API'>
                    </div>
                    <div class='mb-10'>
                        <label class='form-label'>
                            {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_KEY_LIST_TABLE_HEAD_DEFAULT') }}
                            <span v-bind:data-hint='loc.DARNEO_OZON_VUE_KEY_LIST_UPLOAD_DIRECTORY'></span>
                        </label>
                        <label class='form-check form-switch form-check-custom form-check-solid'>
                            <input class='form-check-input' type='checkbox' v-model='isMain'
                                   true-value='1' false-value='0'/>
                        </label>
                    </div>
                </div>
                <div class='modal-footer'>
                    <a href='javascript:void(0)' class='btn btn-light' data-bs-dismiss='modal'>
                        {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_KEY_LIST_BUTTON_CLOSE') }}
                    </a>
                    <a href='javascript:void(0)' class='btn btn-primary'
                       :class={disabled:!isActiveButton()} v-on:click='actionUpdate()'>
                        {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_KEY_LIST_BUTTON_UPDATE') }}
                        <i class='fa fa-spin fa-spinner' v-show='request.isUpdateRow'></i>
                    </a>
                </div>
            </div>
        </div>
        </div>
    `,
})
</script>