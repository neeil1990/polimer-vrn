<script>
BX.BitrixVue.component('section-list', {
    props: {
        data: {
            type: Object,
            required: true
        },
    },
    methods: {
        setPopupData: function (title, iblockId, sectionId) {
            this.$emit('setPopupData', title, iblockId, sectionId)
        },
        actionDeleteCategory: function (iblockId, sectionId) {
            this.$emit('actionDeleteCategory', iblockId, sectionId)
        },
    },
    template: `
        <div>
        <ul class='icon-lists border navs-icon inline-nav'>
            <section-list-item
                v-bind:item='data'
                v-on:setPopupData='setPopupData'
                v-on:actionDeleteCategory='actionDeleteCategory'
            />
        </ul>
        </div>
    `,
})
</script>