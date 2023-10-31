(function(BX, window) {

	const Plugin = BX.namespace('YandexMarket.Plugin');
	const OrderList = BX.namespace('YandexMarket.OrderList');

	const constructor = OrderList.DialogAction = Plugin.Base.extend({

		defaults: {
			url: null,
			width: null,
			height: null,
			minWidth: 400,
			minHeight: 300,
			maxWidth: 600,
			maxHeight: 600,
			items: [],
			lang: {},
			langPrefix: 'YANDEX_MARKET_UI_TRADING_ORDER_LIST_DIALOG_ACTION_'
		},

		openGroupDialog: function(type, adminList) {
			try {
				this.openDialog(type, this.selectedRows(adminList));
			} catch (error) {
				this.showError(error.message);
			}
		},

		selectedRows: function(adminList) {
			const selected = this.isUiGrid(adminList)
				? this.gridSelectedRows(adminList)
				: this.adminSelectedRows(adminList);

			if (selected.length === 0) {
				throw new Error(this.getLang('REQUIRE_SELECT_ORDERS'));
			}

			return selected;
		},

		gridSelectedRows: function(adminList) {
			return this.getUiGrid(adminList).getRows().getSelectedIds();
		},

		adminSelectedRows: function(adminList) {
			const checkboxes = adminList.CHECKBOX;
			const result = [];
			let checkbox;
			let checkboxIndex;

			for (checkboxIndex = 0; checkboxIndex < checkboxes.length; checkboxIndex++) {
				checkbox = checkboxes[checkboxIndex];

				if (checkbox.checked && !checkbox.disabled) {
					result.push(checkbox.value);
				}
			}

			return result;
		},

		actionValues: function(adminList) {
			return this.isUiGrid(adminList)
				? this.gridActionValues(adminList)
				: this.adminActionValues(adminList);
		},

		gridActionValues: function(adminList) {
			return this.getUiGrid(adminList).getActionsPanel().getValues();
		},

		adminActionValues: function(adminList) {
			const result = {};

			for (const input of adminList.FOOTER.querySelectorAll('input, select')) {
				if (input.type === 'button' || input.type === 'submit') { continue; }
				if (!input.name) { continue; }

				result[input.name] = input.value;
			}

			return result;
		},

		openDialog: function(type, orderIds) {
			throw new Error('not implemented');
		},

		buildUrl: function(item, orderIds) {
			let result = this.options.url;
			let orderId;
			let orderIndex;

			result +=
				(result.indexOf('?') === -1 ? '?' : '&')
				+ 'type=' + item.TYPE;

			if (orderIds == null) {
				// nothing
			} else if (Array.isArray(orderIds)) {
				for (orderIndex = 0; orderIndex < orderIds.length; orderIndex++) {
					orderId = orderIds[orderIndex];

					result += '&id[]=' + encodeURIComponent(orderId);
				}
			} else {
				result += '&id=' + encodeURIComponent(orderIds);
			}

			return result;
		},

		getItem: function(type) {
			const items = this.options.items;
			let result;
			let itemIndex;
			let item;

			for (itemIndex = 0; itemIndex < items.length; itemIndex++) {
				item = items[itemIndex];

				if (item.TYPE === type) {
					result = item;
					break;
				}
			}

			return result;
		},

		showLoading: function(grid) {
			this.isUiGrid(grid)
				? this.getUiGrid(grid).tableFade()
				: BX.showWait(grid.gridId);
		},

		hideLoading: function(grid) {
			this.isUiGrid(grid)
				? this.getUiGrid(grid).tableUnfade()
				: BX.closeWait(grid.gridId);
		},

		showError: function(grid, error) {
			const message = error instanceof Error ? error.message : error;

			if (this.isUiGrid(grid)) {
				this.showUiGridError(grid, message);
			} else {
				this.showAdminError(message);
			}
		},

		showUiGridError: function(grid, message) {
			const uiGrid = this.getUiGrid(grid);
			const parts = (message || '').split(/<br[ \/]*>/i);

			uiGrid.arParams.MESSAGES = parts.map((part) => {
				return { TYPE: 'ERROR', TEXT: part };
			});

			BX.onCustomEvent(window, 'BX.Main.grid:paramsUpdated', []);
		},

		showAdminError(message) {
			message = (message || '').replace(/<br[ \/]*>/ig, "\n\n");
			alert(message);
		},

		reloadGrid: function(grid) {
			if (this.isUiGrid(grid)) {
				this.getUiGrid(grid).reloadTable();
			} else {
				grid.GetAdminList(window.location.href);
			}
		},

		isUiGrid: function(adminList) {
			return (
				(BX.adminUiList != null && adminList instanceof BX.adminUiList)
				|| (BX.publicUiList != null && adminList instanceof BX.publicUiList)
			);
		},

		getUiGrid: function(adminList) {
			return BX.Main.gridManager.getById(adminList.gridId).instance;
		}

	});

})(BX, window);