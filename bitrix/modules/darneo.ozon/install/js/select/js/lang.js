$.fn.select2.amd.define('select2/i18n/ru', [], function () {
    return {
        errorLoading: function () {
            return BX.message('DARNEO_OZON_JS_SELECT2_ERROR')
        },
        inputTooLong: function (args) {
            let overChars = args.input.length - args.maximum
            let message = BX.message('DARNEO_OZON_JS_SELECT2_DELETE_SYMBOL_1') +
                    overChars + BX.message('DARNEO_OZON_JS_SELECT2_DELETE_SYMBOL_2')
            if (overChars >= 2 && overChars <= 4) {
                message += BX.message('DARNEO_OZON_JS_SELECT2_DELETE_SYMBOL_3')
            } else if (overChars >= 5) {
                message += BX.message('DARNEO_OZON_JS_SELECT2_DELETE_SYMBOL_4')
            }

            return message
        },
        inputTooShort: function (args) {
            let remainingChars = args.minimum - args.input.length
            let message = BX.message('DARNEO_OZON_JS_SELECT2_INSERT_1') +
                    remainingChars + BX.message('DARNEO_OZON_JS_SELECT2_INSERT_2')

            return message
        },
        loadingMore: function () {
            return BX.message('DARNEO_OZON_JS_SELECT2_LOADING_MORE')
        },
        maximumSelected: function (args) {
            var message = BX.message('DARNEO_OZON_JS_SELECT2_SELECT_1') +
                    args.maximum + BX.message('DARNEO_OZON_JS_SELECT2_SELECT_2')

            if (args.maximum >= 2 && args.maximum <= 4) {
                message += BX.message('DARNEO_OZON_JS_SELECT2_SELECT_3')
            } else if (args.maximum >= 5) {
                message += BX.message('DARNEO_OZON_JS_SELECT2_SELECT_4')
            }

            return message
        },
        noResults: function () {
            return BX.message('DARNEO_OZON_JS_SELECT2_EMPTY')
        },
        searching: function () {
            return BX.message('DARNEO_OZON_JS_SELECT2_SEARCH')
        },
    }
})