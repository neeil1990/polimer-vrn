//<script>
(function (window) {
    'use strict'

    const BX = window.BX
    BX.Vue.component('darneo-ozon-price', {
        props: {
            price: {
                type: Number,
                required: false
            },
        },
        data: function () {
            return {
                tweenedPrice: Number(this.price)
            }
        },
        computed: {
            animatedPrice: function () {
                let cnt = this.tweenedPrice.toFixed(0)
                return new Intl.NumberFormat('ru-RU', {
                    style: 'currency',
                    currency: 'RUB',
                    minimumFractionDigits: 0
                }).format(cnt)
            },
            computedPrice: {
                get: function () {
                    return this.price
                },
                set: function (value) {
                    this.price = value
                }
            },
        },
        watch: {
            computedPrice: function (val) {
                gsap.to(this.$data, { duration: 0.5, tweenedPrice: val })
            }
        },
        template: `
        <div>{{ animatedPrice }}</div>
    `,
    })
})(window)
//</script>