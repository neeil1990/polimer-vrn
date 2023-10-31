(function(BX, $, window) {

	const Input = BX.namespace('YandexMarket.Ui.Input');

	const constructor = Input.Autocomplete = Input.TagInput.extend({

		defaults: {
			minLength: 1,
			paging: false,
		},

		createPluginOptions: function() {
			return $.extend(
				this.callParent('createPluginOptions', constructor),
				this.getInputOptions(),
				this.getAjaxOptions()
			);
		},

		getInputOptions: function() {
			return {
				minimumInputLength: this.options.minLength
			};
		},

		getAjaxOptions: function() {
			return {
				ajax: {
					delay: 500,
					url: this.options.url,
					type: 'post',
					data: $.proxy(this.makeAjaxData, this),
					dataType: 'json',
					processResults: $.proxy(this.processResults, this),
				}
			};
		},

		makeAjaxData: function(params) {
			return {
				q: params.term,
				page: this.options.paging && params.page || 1,
			};
		},

		processResults: function(data, params) {
			const response = {
				results: [],
				pagination: {
					more: false,
				},
			};
			const groups = {};
			let group;
			let onlyEmptyGroup = true;

			if (data.status === 'ok') {
				for (const option of data.enum) {
					if (option['GROUP']) {
						group = option['GROUP'];
						onlyEmptyGroup = false;
					} else {
						group = 'empty';
					}

					if (!groups.hasOwnProperty(group)) {
						groups[group] = {
							'text': option['GROUP'] || '',
							'children': [],
						};
					}

					groups[group].children.push({
						id: option['ID'],
						text: option['VALUE'],
					});
				}

				if (!onlyEmptyGroup) {
					response.results = Object.values(groups);
				} else if (groups.hasOwnProperty('empty')) {
					response.results = groups['empty'].children;
				}

				response.pagination.more = !!data.hasNext;
			}

			return response;
		},

	}, {
		dataName: 'uiAutocompleteInput'
	});

})(BX, jQuery, window);