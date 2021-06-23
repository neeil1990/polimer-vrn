(function() {

	var AdminList = BX.namespace('YandexMarket.AdminList');

	AdminList.ListExtension = function(options) {
		this.options = BX.merge({}, this.defaults, options);
		this.initialize();
	};

	BX.merge(AdminList.ListExtension.prototype, {

		defaults: {
			limitTop: null,
			disabledRows: null,
			loadMore: false
		},

		initialize: function() {
			this.applyLimitTop();
			this.applyDisabledIndexes();
			this.applyLoadMore();
		},

		applyLimitTop: function() {
			if (this.options.limitTop == null) { return; }

			var element = this.getPageSizeElement();
			var items = this.getPageSizeItems(element);
			var newItems = this.filterPageSizeItems(items, this.options.limitTop);

			if (newItems != null) {
				this.setPageSizeItems(element, newItems);
			}
		},

		getPageSizeElement: function() {
			var grid = this.getGrid();
			var pageSizeId = grid.getContainerId() + '_' + grid.settings.get('pageSizeId');

			return document.getElementById(pageSizeId);
		},

		getPageSizeItems: function(element) {
			var itemsAttribute = BX.data(element, 'items');

			return this.parsePageSizeItems(itemsAttribute);
		},

		setPageSizeItems: function(element, items) {
			BX.data(element, 'items', JSON.stringify(items));
		},

		parsePageSizeItems: function(attributeValue) {
			var result;

			try {
				result = eval(attributeValue);
			} catch (e) {
				result = [];
			}

			return result;
		},

		filterPageSizeItems: function(items, top) {
			var itemIndex;
			var item;
			var itemValue;
			var result = [];
			var isChanged = false;

			for (itemIndex = 0; itemIndex < items.length; itemIndex++) {
				item = items[itemIndex];
				itemValue = parseInt(item.VALUE, 10);

				if (!isNaN(itemValue) && itemValue > top) {
					isChanged = true;
				} else {
					result.push(item);
				}
			}

			return isChanged ? result : null;
		},

		applyDisabledIndexes: function() {
			if (this.options.disabledRows == null) { return; }

			var disabled = this.options.disabledRows;
			var disabledIndex;
			var rows = this.getGrid().getRows();
			var rowId;
			var row;
			var checkbox;

			for (disabledIndex = 0; disabledIndex < disabled.length; disabledIndex++) {
				rowId = disabled[disabledIndex];
				row = rows.getById(rowId);

				if (row == null) { continue; }

				checkbox = row.getCheckbox();

				checkbox.disabled = true;
				BX.data(checkbox, 'disabled', '1');
			}
		},

		applyLoadMore: function() {
			if (!this.options.loadMore) { return; }

			var moreButton = this.getGrid().getMoreButton();
			var moreButtonElement;
			var nextUrl = this.getNextPageUrl();

			if (nextUrl) {
				moreButtonElement = moreButton.getNode();

				moreButtonElement.href = nextUrl;
				moreButtonElement.style.display = '';
			}
		},

		getNextPageUrl: function() {
			var pagination = this.getGrid().getPagination();
			var nextLink = pagination.getContainer().querySelector('.main-ui-pagination-next');
			var result;

			if (nextLink && nextLink.href) {
				result = nextLink.href;
			}

			return result;
		},

		getAdminList: function() {
			return window[this.options.grid];
		},

		getGrid: function() {
			return BX.Main.gridManager.getById(this.options.grid).instance;
		}

	});

})();