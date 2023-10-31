(function(BX, window) {

	const Input = BX.namespace('YandexMarket.Ui.Input');

	const constructor = Input.ExportParam = Input.Autocomplete.extend({

		defaults: {
			tags: false,
			width: 220,
			minLength: 2,
			iblockId: null,
		},

		makeAjaxData: function(params) {
			let result = this.callParent('makeAjaxData', [params], constructor);

			if (this.options.iblockId !== null) {
				result = $.extend(result, { IBLOCK_ID: String(this.options.iblockId).split(',') });
			} else {
				const formData = this.$el.closest('form').serializeArray();
				const usedKeys = this.getUsedKeys();

				result = $.extend(
					result,
					this.getFormDataValues(formData, usedKeys)
				);
			}

			return result;
		},

		getUsedKeys: function() {
			return [
				'PRODUCT_SKU_FIELD',
			];
		},

		getFormDataValues: function(formData, keys) {
			const result = {};
			let formValue;
			let formValueIndex;
			let keyIndex;

			for (formValueIndex = 0; formValueIndex < formData.length; formValueIndex++) {
				formValue = formData[formValueIndex];

				if (!formValue.name) { continue; }

				for (keyIndex = 0; keyIndex < keys.length; keyIndex++) {
					if (formValue.name.indexOf(keys[keyIndex]) === 0) {
						result[formValue.name] = formValue.value;
						break;
					}
				}
			}

			return result;
		},

	}, {
		dataName: 'uiInputExportParam'
	});

})(BX, window);