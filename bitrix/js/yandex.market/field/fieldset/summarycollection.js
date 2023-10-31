(function(BX, window) {

	const Fieldset = BX.namespace('YandexMarket.Field.Fieldset');

	const constructor = Fieldset.SummaryCollection = Fieldset.Collection.extend({

		handleModalSave: function(childInstance, dir) {
			childInstance.$el[dir ? 'on' : 'off']('uiModalSave', $.proxy(this.onModalSave, this));
		},

		handleModalClose: function(childInstance, dir) {
			childInstance.$el[dir ? 'on' : 'off']('uiModalClose', $.proxy(this.onModalClose, this));
		},

		onItemAddClick: function(evt) {
			const instance = this.addItem();

			instance
				.openEditModal()
				.applyDefaults();

			this.handleModalSave(instance, true);
			this.handleModalClose(instance, true);

			evt.preventDefault();
		},

		onModalSave: function(evt) {
			const target = evt.currentTarget;
			const instance = this.getItemInstance(target);

			this.handleModalSave(instance, false);
			this.handleModalClose(instance, false);
		},

		onModalClose: function(evt) {
			const target = evt.currentTarget;
			const instance = this.getItemInstance(target);

			this.handleModalSave(instance, false);
			this.handleModalClose(instance, false);

			this.deleteItem(instance.$el, true);
		},

		getItemAddButton: function() {
			return this.getElement('itemAdd');
		},

		getItemPlugin: function() {
			return Fieldset.Summary;
		}

	}, {
		dataName: 'FieldFieldsetSummaryCollection',
		pluginName: 'YandexMarket.Field.Fieldset.SummaryCollection'
	});

})(BX, window);