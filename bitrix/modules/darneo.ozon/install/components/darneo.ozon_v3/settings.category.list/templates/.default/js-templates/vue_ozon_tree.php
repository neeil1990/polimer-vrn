<script>
BX.BitrixVue.component('ozon-category-tree', {
    props: {
        tree: {
            type: Object,
            required: false
        },
        selected: {
            type: Object,
            required: false
        },
    },
    data: function () {
        return {
            selectedDefault: '0',
            selectedLevel1: Number(this.selected.LEVEL_1),
            selectedLevel2: Number(this.selected.LEVEL_2),
            selectedLevel3: Number(this.selected.LEVEL_3),
            isSend: false
        }
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
    methods: {
        setLevel: function () {
            this.$emit('setLevel', this.selectedLevel1, this.selectedLevel2, this.selectedLevel3)
        },
        actionCategoryDisable: function (categoryId, value) {
            this.$emit('actionCategoryDisable', categoryId, value)
        },
        actionCategoryActiveAll: function (level) {
            let categoryIds = []
            for (let key in level) {
                let item = level[key]
                categoryIds.push(Number(item.CATEGORY_ID))
            }
            this.$emit('actionCategoryActiveAll', categoryIds)
        },
        actionCategoryDisableAll: function (level) {
            let categoryIds = []
            for (let key in level) {
                let item = level[key]
                categoryIds.push(Number(item.CATEGORY_ID))
            }
            this.$emit('actionCategoryDisableAll', categoryIds)
        },
        actionShowModal: function (level3, title, data) {
            if (data.length > 0) {
                this.setLevel3(level3)
                this.$emit('actionShowModal', level3, title, data)
            }
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
        isDisableSection: function (disabled, section) {
            return disabled || section.length === 0
        },
    },
    template: `
        <div>
        <div class='row'>
            <div class='col-4' v-show='tree.LEVEL_1.length > 0'>
                <p>
                    <a href='javascript:void(0)' v-on:click='actionCategoryActiveAll(tree.LEVEL_1)'>
                        {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_CATEGORY_LIST_BUTTON_ON') }}
                    </a>
                     /
                    <a href='javascript:void(0)' v-on:click='actionCategoryDisableAll(tree.LEVEL_1)'>
                        {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_CATEGORY_LIST_BUTTON_OFF') }}
                    </a>
                </p>
                <ul class='list-group'>
                    <li class='list-group-item' v-for='level1 in tree.LEVEL_1' :key='Number(level1.CATEGORY_ID)'
                        v-bind:class='{
                            active:selectedLevel1 === Number(level1.CATEGORY_ID),
                            disable:level1.DISABLE
                        }'>
                        <div class='row'>
                            <div class='col-9' v-on:click='setLevel1(level1.CATEGORY_ID)'>
                                <span v-html='level1.TITLE'></span>
                            </div>
                            <div class='col-3'>
                                <label class='form-check form-switch form-check-solid'>
                                    <input class='form-check-input' type='checkbox' v-bind:checked='!level1.DISABLE'
                                           v-on:change='actionCategoryDisable(Number(level1.CATEGORY_ID), level1.DISABLE)'/>
                                </label>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
            <div class='col-4' v-show='tree.LEVEL_2.length > 0'>
                <p>
                    <a href='javascript:void(0)' v-on:click='actionCategoryActiveAll(tree.LEVEL_2)'>
                        {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_CATEGORY_LIST_BUTTON_ON') }}

                    </a>
                     /
                    <a href='javascript:void(0)' v-on:click='actionCategoryDisableAll(tree.LEVEL_2)'>
                        {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_CATEGORY_LIST_BUTTON_OFF') }}
                    </a>
                </p>
                <ul class='list-group'>
                    <li class='list-group-item' v-for='level2 in tree.LEVEL_2' :key='Number(level2.CATEGORY_ID)'
                        v-bind:class='{
                            active:selectedLevel2 === Number(level2.CATEGORY_ID),
                            disable:level2.DISABLE
                        }'>
                        <div class='row'>
                            <div class='col-9' v-on:click='setLevel2(level2.CATEGORY_ID)'>
                                <span v-html='level2.TITLE'></span>
                            </div>
                            <div class='col-3'>
                                <label class='form-check form-switch form-check-solid'>
                                    <input class='form-check-input' type='checkbox' v-bind:checked='!level2.DISABLE'
                                           v-on:change='actionCategoryDisable(Number(level2.CATEGORY_ID), level2.DISABLE)'/>
                                </label>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
            <div class='col-4' v-show='tree.LEVEL_3.length > 0'>
                <p>
                    <a href='javascript:void(0)' v-on:click='actionCategoryActiveAll(tree.LEVEL_3)'>
                        {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_CATEGORY_LIST_BUTTON_ON') }}
                    </a>
                     /
                    <a href='javascript:void(0)' v-on:click='actionCategoryDisableAll(tree.LEVEL_3)'>
                        {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_CATEGORY_LIST_BUTTON_OFF') }}
                    </a>
                </p>
                <ul class='list-group'>
                    <li class='list-group-item' v-for='level3 in tree.LEVEL_3' :key='Number(level3.CATEGORY_ID)'
                        v-bind:class='{disable:level3.DISABLE}'>
                        <div class='row'>
                            <div class='col-8'>
                                <span v-html='level3.TITLE'></span>
                            </div>
                            <div class='col-4'>
                                <label class='form-check form-switch form-check-solid'>
                                    <input class='form-check-input' type='checkbox' v-bind:checked='!level3.DISABLE'
                                           v-on:change='actionCategoryDisable(Number(level3.CATEGORY_ID), level3.DISABLE)'/>
                                </label>
                                <div class='m-t-10 m-b-10'>
                                    <a href='javascript:void(0)'
                                       v-on:click='actionShowModal(Number(level3.CATEGORY_ID), level3.TITLE, level3.SECTIONS)'
                                       v-bind:class='{disable: isDisableSection(level3.DISABLE, level3.SECTIONS)}'>
                                        {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_CATEGORY_LIST_SECTIONS_TEXT') }}
                                        (<span v-html='level3.SECTIONS.length'></span>)
                                    </a>
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
        </div>
    `,
})
</script>