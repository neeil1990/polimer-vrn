//<script>
(function (window) {
    'use strict'

    const BX = window.BX
    BX.Vue.component('darneo-ozon-select', {
        props: {
            options: {
                type: Array,
                required: false
            },
            disabled: {
                type: Boolean,
                default: false,
                required: false
            },
            multiple: {
                type: Boolean,
                required: false
            },
            isRequired: {
                type: Boolean,
                required: false
            },
            placeholder: {
                type: String,
                required: false
            },
            value: ''
        },
        watch: {
            options: function () {
                $(this.$el).find('select').empty()
                this.destroy()
                this.init()
            }
        },
        destroyed: function () {
            this.destroy()
        },
        mounted: function () {
            this.init()
        },
        methods: {
            init: function () {
                let vm = this
                let options = this.getOptions(this.options)
                $(this.$el).find('select').select2(options).val(this.value).trigger('change').on('change', function () {
                    vm.$emit('input', $(this).val())
                })
                if (this.value === '') {
                    for (let key in this.options) {
                        let item = this.options[key]
                        if (item.selected === true) {
                            vm.$emit('input', item.id)
                        }
                    }
                }
            },
            destroy: function () {
                $(this.$el).find('select').off().select2('destroy')
            },
            getOptions: function (data) {
                return {
                    data: data,
                    disabled: this.disabled,
                    language: 'ru',
                    multiple: this.multiple,
                    minimumResultsForSearch: '10',
                    placeholder: this.placeholder,
                    width: '100%'
                }
            }
        },
        template: `
            <div>
            <select class="form-select" />
            </div>
        `
    })
})(window)
//</script>