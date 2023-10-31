(function(BX) {

	BX.publicUiList = function(gridId) {
		this.gridId = gridId;
	};

	BX.publicUiList.SendSelected = function(gridId) {
		const gridInstance = BX.Main.gridManager.getById(gridId).instance;
		const values = gridInstance.getActionsPanel().getValues();
		const selectedRows = gridInstance.getRows().getSelectedIds();
		const data = Object.assign({
			ID: selectedRows,
			action: values
		}, values);

		gridInstance.reloadTable("POST", data);
	};

})(BX);