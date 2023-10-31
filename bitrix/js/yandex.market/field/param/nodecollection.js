(function(BX, $, window) {

	var Reference = BX.namespace('YandexMarket.Field.Reference');
	var Param = BX.namespace('YandexMarket.Field.Param');
	var Source = BX.namespace('YandexMarket.Source');

	var constructor = Param.NodeCollection = Param.TagCollection.extend({

		defaults: {
			itemElement: '.js-param-node-collection__item',
			itemAddHolderElement: '.js-param-node-collection__item-add-holder',
			itemAddElement: '.js-param-node-collection__item-add',
			itemDeleteElement: '.js-param-node-collection__item-delete'
		},

		bind: function() {
			this.callParent('bind', constructor);
			this.handleLinkedChange(true);
		},

		unbind: function() {
			this.handleLinkedChange(false);
			this.callParent('unbind', constructor);
		},

		handleLinkedChange: function(dir) {
			this.$el[dir ? 'on' : 'off']('FieldParamNodeLinkedChange', $.proxy(this.onLinkedChange, this));
		},

		onLinkedChange: function(evt, data) {
			this.callItemList(function(itemInstance) {
				if (itemInstance === data.node) { return; }

				itemInstance.syncLinked(data.source, data.field);
			});
		},

		toggleItemDeleteView: function() {
			// nothing
		},

		preselect: function() {
			this.callItemList('preselect');
		},

		getItemPlugin: function() {
			return Param.Node;
		}

	}, {
		dataName: 'FieldParamNode'
	});

})(BX, jQuery, window);