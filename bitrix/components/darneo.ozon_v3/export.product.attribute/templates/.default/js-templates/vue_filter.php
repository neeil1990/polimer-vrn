<script>
BX.BitrixVue.component('ozon-attribute-filter', {
    props: {
        tree: {
            type: Array,
            required: false
        },
        selected: {
            type: Object,
            required: false
        },
        isImportStart: {
            type: Boolean,
            required: false
        },
    },
    data: function () {
        return {
            selectedDefault: 0,
            connectionSectionTree: Number(this.selected.CONNECTION_SECTION_TREE_ID),
            showPrevCategory: true,
            showNextCategory: true,
            isSend: false
        }
    },
    computed: {
        loc: function () {
            return BX.BitrixVue.getFilteredPhrases('DARNEO_OZON_')
        },
    },
    watch: {
        connectionSectionTree: function () {
            this.showPrevCategory = true
            this.showNextCategory = true
            this.setFilter()
        },
        tree: function () {
            this.isSend = false
        },
    },
    methods: {
        actionStart: function () {
            if (!this.isImportStart) {
                this.$emit('actionImportStart')
            }
        },
        setFilter: function () {
            this.$emit('setFilter', this.connectionSectionTree)
        },
        getValues: function () {
            let arr = []
            for (let key in this.tree) {
                let item = this.tree[key]
                let row = {}
                row['id'] = item.ID
                row['text'] = '[' + item.SECTION_ID + '] ' + item.SECTION_NAME + ' [' + item.CATEGORY_NAME + ']'
                row['selected'] = item.SELECTED
                arr.push(row)
            }
            return arr
        },
        unsetFilter: function () {
            this.connectionSectionTree = this.selectedDefault
        },
        setCategory: function (type) {
            for (let key in this.tree) {
                let item = this.tree[key]
                if (Number(item.ID) === Number(this.connectionSectionTree) || this.connectionSectionTree === this.selectedDefault) {
                    let itemNext
                    if (type === 1) {
                        itemNext = this.tree[++key]
                        if (itemNext === undefined) {
                            this.showNextCategory = false
                        }
                    }
                    if (type === -1) {
                        itemNext = this.tree[--key]
                        if (itemNext === undefined) {
                            this.showPrevCategory = false
                        }
                    }
                    if (itemNext !== undefined) {
                        this.connectionSectionTree = itemNext.ID
                        break
                    }
                }
            }
        },
    },
    template: `
        <div class='col-md-12'>
        <div class='form-group'>
            <label class='form-label'>
                {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_ATTRIBUTE_FILTER_CATEGORIES') }}
            </label>
            <div class='row'>
                <div class='col-md-12'>
                    <div class='d-flex'>
                        <div class='w-100'>
                            <darneo-ozon-select
                                v-bind:options='getValues()'
                                v-bind:value='connectionSectionTree'
                                v-bind:placeholder='loc.DARNEO_OZON_VUE_PRODUCT_ATTRIBUTE_PLACEHOLDER_SELECT_CATEGORY'
                                v-on:input='connectionSectionTree = $event'
                            />
                        </div>
                        <a class='m-2' href='javascript:void(0)' v-on:click='unsetFilter()'
                           v-show='connectionSectionTree !== selectedDefault'>
                            <i class='ki-duotone ki-cross-square fs-2x'>
                                <i class='path1'></i>
                                <i class='path2'></i>
                            </i>
                        </a>
                    </div>
                </div>
                <div class='col-md-12'>
                    <div class='d-flex flex-end mt-2'>
                    <ozon-attribute-import
                        v-bind:connectionSectionTree='Number(connectionSectionTree)'
                        v-bind:isImportStart='isImportStart'
                        v-on:actionImportStart='actionStart'
                    />
                    <a href='javascript:void(0)' v-bind:class='{disable:!showPrevCategory}' v-on:click='setCategory(-1)'>
                        <i class='ki-duotone ki-up-square fs-3x'>
                            <i class='path1'></i>
                            <i class='path2'></i>
                        </i>
                    </a>
                    <a href='javascript:void(0)' v-bind:class='{disable:!showNextCategory}' v-on:click='setCategory(1)'>
                        <i class='ki-duotone ki-down-square fs-3x'>
                            <i class='path1'></i>
                            <i class='path2'></i>
                        </i>
                    </a>
                </div>
                </div>
            </div>
        </div>
        </div>
    `,
})
</script>