(function(BX, $, window) {

	var Plugin = BX.namespace('YandexMarket.Plugin');
	var Input = BX.namespace('YandexMarket.Ui.Input');

	var constructor = Input.FilterInput = Input.TagInput.extend({

		createPluginOptions: function() {
			var result = this.callParent('createPluginOptions', constructor);

			if (this.options.autocomplete) {
				result = $.extend(true, result, this.getAjaxOptions());
			}

			return result;
		},

		getAjaxOptions: function() {
			var element = this.$el;

			return {
				minimumInputLength: 1,
				tags: false,
				ajax: {
					delay: 300,
					url: 'yamarket_filter_autocomplete.php',
					type: 'post',
					data: function (params) {
						return {
							QUERY: params.term,
							SOURCE_FIELD: element.data('sourceField'),
							IBLOCK_ID: element.data('iblockId')
						};
					},
					dataType: 'json',
					processResults: function (data, params) {
						var i;
						var result = {
							results: []
						};

						if (!$.isPlainObject(data)) {
							// not valid data
						} else if ('suggest' in data) {
							for (i = 0; i < data.suggest.length; i++) {
								result['results'].push({
									id: data.suggest[i]['ID'],
									text: data.suggest[i]['VALUE']
								});
							}
						}

						return result;
					}
				}
			};
		},

	}, {
		dataName: 'uiFilterAutocomplete'
	});

})(BX, jQuery, window);