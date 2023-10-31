(function(BX, window) {

	var Reference = BX.namespace('YandexMarket.Field.Reference');
	var SkuField = BX.namespace('YandexMarket.Field.SkuField');

	var constructor = SkuField.Row = Reference.Base.extend({

		defaults: {
			inputElement: '.js-sku-field-row__input',
			deleteElement: '.js-sku-field-row__delete'
		},

		initialize: function() {
			this.callParent('initialize', constructor);
			this.bind();
		},

		destroy: function() {
			this.unbind();
			this.callParent('destroy', constructor);
		},

		bind: function() {
			this.handleDeleteClick(true);
			this.handleIblockChange(true);
		},

		unbind: function() {
			this.handleDeleteClick(false);
			this.handleIblockChange(false);
		},

		handleDeleteClick: function(dir) {
			var deleteButton = this.getElement('delete');

			deleteButton[dir ? 'on' : 'off']('click', $.proxy(this.onDeleteClick, this));
		},

		handleIblockChange: function(dir) {
			var iblockInput = this.getInput('IBLOCK');

			iblockInput[dir ? 'on' : 'off']('change', $.proxy(this.onIblockChange, this));
		},

		onDeleteClick: function(evt) {
			var parentField = this.getParentField();

			if (parentField != null) {
				parentField.deleteItem(this.$el);
			}
		},

		onIblockChange: function(evt) {
			var iblockId = evt.target.value;

			this.reloadFieldEnum(iblockId);
		},

		clear: function() {
			var iblockInput = this.getInput('IBLOCK');

			this.callParent('clear');

			if (iblockInput != null) {
				this.reloadFieldEnum(iblockInput.value);
			}
		},

		reloadFieldEnum: function(iblockId) {
			var fieldSource = this.getSource();
			var fieldEnum;

			if (fieldSource.hasEnum(iblockId)) {
				fieldEnum = fieldSource.getEnum(iblockId);
				this.renderFieldEnum(fieldEnum);
			} else {
				fieldSource.loadEnum(iblockId, $.proxy(this.renderFieldEnum, this));
			}
		},

		renderFieldEnum: function(fieldEnum) {
			var fieldInput = this.getInput('FIELD');
			var noValueOption;
			var i;
			var previousValue;
			var data;
			var option;

			if (fieldInput != null) {
				previousValue = fieldInput.value;
				noValueOption = this.getNoValueOption(fieldInput);

				fieldInput.empty();

				if (noValueOption != null) {
					fieldInput.append(noValueOption);
				}

				for (i = 0; i < fieldEnum.length; i++) {
					data = fieldEnum[i];

					option = document.createElement('option');
					option.value = data.ID;
					option.innerText = data.VALUE;
					option.selected = (previousValue === data.VALUE);

					fieldInput.append(option);
				}
			}
		},

		getNoValueOption: function(select) {
			var option = select.children().get(0);
			var result;

			if (option && (option.tagName || '').toLowerCase() === 'option' && option.value === '') {
				result = option;
			}

			return result;
		},

		getSource: function() {
			var parentField = this.getParentField() || this;
			var parentElement = parentField.$el;

			return SkuField.Source.getInstance(parentElement);
		}

	}, {
		dataName: 'FieldSkuFieldRow',
		pluginName: 'YandexMarket.Field.SkuField.Row'
	});

})(BX, window);