<script>
BX.BitrixVue.component('ozon-order-list', {
    props: {
        data: {
            type: Object,
            required: true
        },
    },
    data: function () {
        return {
            kanban: '',
        }
    },
    watch: {
        data: function () {
            $('#kanbanJs').empty()
            this.kanban = ''
            this.initKanban()
        },
    },
    mounted: function () {
        this.$nextTick(function () {
            this.initKanban()
        })
    },
    methods: {
        getHtmlRow: function (data) {
            let basket = []
            if (data.PRODUCTS) {
                for (let key in data.PRODUCTS) {
                    let item = data.PRODUCTS[key]
                    let row = this.getHtmlBasketRow(item)
                    basket.push(row)
                }
            }
            return `
                <a class='kanban-box' href='javascript:void(0)'>
                    <span class='date'>` + data.DATE_CREATED + `</span>
                    <span class='badge badge-success f-right'>` + data.SUM_FORMATED + `</span>
                    <h6>` + data.POSTING_NUMBER + `</h6>
                    <div class='media'>
                        <div class='media-body p-l-20'>
                            ` + basket.join('') + `
                        </div>
                    </div>
                </a>
            `
        },
        getHtmlBasketRow: function (data) {
            return `
               <b>` + data.offer_id + `</b><div>` + data.name + ` - ` + data.quantity + `</div>
           `
        },
        getHtmlTitle: function (title, count, sum) {
            return `
               <span>` + title + ` (` + count + `) <span>` + sum + `</span></span>
           `
        },
        initKanban: function () {
            console.log(this.data.LIST)
            let firstSum = 0
            let firstCount = 0
            let firstColumn = []
            if (this.data.LIST.awaiting_packaging) {
                firstSum = this.data.LIST.awaiting_packaging.SUM_FORMATED
                firstCount = this.data.LIST.awaiting_packaging.ITEMS.length
                for (let key in this.data.LIST.awaiting_packaging.ITEMS) {
                    let item = this.data.LIST.awaiting_packaging.ITEMS[key]
                    let row = this.getHtmlRow(item)
                    firstColumn.push({ 'title': row })
                }
            }
            let secondSum = 0
            let secondCount = 0
            let secondColumn = []
            if (this.data.LIST.awaiting_deliver) {
                secondSum = this.data.LIST.awaiting_deliver.SUM_FORMATED
                secondCount = this.data.LIST.awaiting_deliver.ITEMS.length
                for (let key in this.data.LIST.awaiting_deliver.ITEMS) {
                    let item = this.data.LIST.awaiting_deliver.ITEMS[key]
                    let row = this.getHtmlRow(item)
                    secondColumn.push({ 'title': row })
                }
            }
            let thirdSum = 0
            let thirdCount = 0
            let thirdColumn = []
            if (this.data.LIST.delivering) {
                thirdSum = this.data.LIST.delivering.SUM_FORMATED
                thirdCount = this.data.LIST.delivering.ITEMS.length
                for (let key in this.data.LIST.delivering.ITEMS) {
                    let item = this.data.LIST.delivering.ITEMS[key]
                    let row = this.getHtmlRow(item)
                    thirdColumn.push({ 'title': row })
                }
            }
            this.kanban = new jKanban({
                element: '#kanbanJs',
                gutter: '15px',
                widthBoard: '30%',
                dragItems: false,
                dragBoards: false,
                boards: [
                    {
                        'id': '_todo',
                        'title': this.getHtmlTitle(this.$Bitrix.Loc.getMessage('DARNEO_OZON_VUE_ORDER_LIST_TAB_NEW'), firstCount, firstSum),
                        'class': 'bg-primary',
                        'item': firstColumn
                    },
                    {
                        'id': '_working',
                        'title': this.getHtmlTitle(this.$Bitrix.Loc.getMessage('DARNEO_OZON_VUE_ORDER_LIST_TAB_STORE'), secondCount, secondSum),
                        'class': 'bg-warning',
                        'item': secondColumn,
                    },
                    {
                        'id': '_done',
                        'title': this.getHtmlTitle(this.$Bitrix.Loc.getMessage('DARNEO_OZON_VUE_ORDER_LIST_TAB_WORK'), thirdCount, thirdSum),
                        'class': 'bg-secondary',
                        'item': thirdColumn
                    }
                ]
            })
        },
    },
    template: `
        <div class='kanban-block' id='kanbanJs'></div>
    `,
})
</script>