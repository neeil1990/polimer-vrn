<script>
BX.BitrixVue.component('select-category', {
    props: {
        tree: {
            type: Object,
            required: false
        },
        iblockId: {
            type: Number,
            required: true
        },
        sectionId: {
            type: Number,
            required: false
        },
        title: {
            type: String,
            required: false
        },
        request: {
            type: Object,
            required: false
        },
    },
    data: function () {
        return {
            selectedDefault: 0,
            selectedLevel1: Number(this.tree.SELECTED.LEVEL_1),
            selectedLevel2: Number(this.tree.SELECTED.LEVEL_2),
            selectedLevel3: Number(this.tree.SELECTED.LEVEL_3),
            isSend: false
        }
    },
    computed: {
        loc: function () {
            return BX.BitrixVue.getFilteredPhrases('DARNEO_OZON_')
        },
    },
    watch: {
        tree: function () {
            this.isSend = false
        },
        selectedLevel1: function () {
            if (this.isSend === false) {
                this.isSend = true
                this.selectedLevel2 = this.selectedDefault
                this.selectedLevel3 = this.selectedDefault
                this.setLevel()
            }
        },
        selectedLevel2: function () {
            if (this.isSend === false) {
                this.isSend = true
                this.selectedLevel3 = this.selectedDefault
                this.setLevel()
            }
        },
        selectedLevel3: function () {
            if (this.isSend === false) {
                this.isSend = true
                this.setLevel()
            }
        }
    },
    mounted: function () {
        this.$nextTick(function () {
            this.init()
        })
    },
    updated: function () {
        this.$nextTick(function () {
            //this.init()
        })
    },
    methods: {
        init: function () {
            let vm = this
            $(this.$el).modal('toggle')
            $(this.$el).on('hidden.bs.modal', function () {
                vm.$emit('actionCloseModal')
            })
        },
        setLevel: function () {
            this.$emit('actionReloadTree', this.selectedLevel1, this.selectedLevel2, this.selectedLevel3)
        },
        setLevel1: function (categoryId) {
            this.selectedLevel1 = Number(categoryId)
        },
        setLevel2: function (categoryId) {
            this.selectedLevel2 = Number(categoryId)
        },
        setLevel3: function (categoryId) {
            this.selectedLevel3 = Number(categoryId)
        },
        getValues: function (dataLevel) {
            let arr = []
            for (let key in dataLevel) {
                let item = dataLevel[key]
                let row = {}
                row['id'] = item.CATEGORY_ID
                row['text'] = item.TITLE
                row['selected'] = item.SELECTED
                arr.push(row)
            }
            return arr
        },
        actionSetCategory: function () {
            if (this.selectedLevel3 !== this.selectedDefault) {
                this.$emit('actionSetCategory', this.iblockId, this.sectionId, this.selectedLevel1, this.selectedLevel2, this.selectedLevel3)
            }
        },
        isDisableButton: function () {
            return this.selectedLevel3 === this.selectedDefault || this.request.isSaveTree
        },
    },
    template: `
        <div class='modal fade bd-example-modal-lg'>
        <div class='modal-dialog modal-lg'>
            <div class='modal-content'>
                <div class='block_disabled' v-show='request.isUpdateTree'></div>
                <div class='modal-header'>
                    <h4 class='modal-title' id='myLargeModalLabel'>
                        {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_SECTION_MODAL_TITLE') }}
                    </h4>
                    <button class='btn-close' type='button' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body'>
                    <div class='input-group mb-5'>
                        <label class='form-label' v-html='title'></label>
                        <div class='w-100 d-flex'>
                            <darneo-ozon-select
                                v-bind:options='getValues(tree.ITEMS.LEVEL_1)'
                                v-bind:value='selectedLevel1'
                                v-bind:placeholder='loc.DARNEO_OZON_VUE_PRODUCT_SECTION_PLACEHOLDER_CATEGORY_L1'
                                v-on:input='selectedLevel1 = $event'
                                class='w-100'
                            />
                        </div>
                    </div>
                    <div class='input-group mb-5'>
                        <div class='w-100 d-flex'>
                            <darneo-ozon-select
                                v-bind:options='getValues(tree.ITEMS.LEVEL_2)'
                                v-bind:value='selectedLevel2'
                                v-bind:placeholder='loc.DARNEO_OZON_VUE_PRODUCT_SECTION_PLACEHOLDER_CATEGORY_L2'
                                v-on:input='selectedLevel2 = $event'
                                class='w-100'
                            />
                            <a class='m-2' href='javascript:void(0)'
                               v-on:click='selectedLevel2=selectedDefault'
                               v-show='selectedLevel2 !== selectedDefault'>
                                <i class='ki-duotone ki-cross-square fs-2x'>
                                    <i class='path1'></i>
                                    <i class='path2'></i>
                                </i>
                            </a>
                        </div>
                    </div>
                    <div class='input-group mb-5'>
                        <div class='w-100 d-flex'>
                            <darneo-ozon-select
                                v-bind:options='getValues(tree.ITEMS.LEVEL_3)'
                                v-bind:value='selectedLevel3'
                                v-bind:placeholder='loc.DARNEO_OZON_VUE_PRODUCT_SECTION_PLACEHOLDER_CATEGORY_L3'
                                v-on:input='selectedLevel3 = $event'
                                class='w-100'
                            />
                            <a class='m-2' href='javascript:void(0)'
                               v-on:click='selectedLevel3=selectedDefault'
                               v-show='selectedLevel3 !== selectedDefault'>
                                <i class='ki-duotone ki-cross-square fs-2x'>
                                    <i class='path1'></i>
                                    <i class='path2'></i>
                                </i>
                            </a>
                        </div>
                    </div>
                </div>
                <div class='modal-footer'>
                    <button class='btn btn-primary p-relative' type='button' v-bind:disabled='isDisableButton()'
                            v-on:click='actionSetCategory()'>
                        <span>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_SECTION_BUTTON_SAVE') }}</span>
                        <i class='fa fa-spin fa-spinner' v-show='request.isSaveTree'></i>
                    </button>
                </div>
            </div>
        </div>
        </div>
    `,
})
</script>