(function(BX, window) {

	var Reference = BX.namespace('YandexMarket.Field.Reference');
	var Fieldset = BX.namespace('YandexMarket.Field.Fieldset');
	var utils = BX.namespace('YandexMarket.Utils');

	var constructor = Fieldset.Summary = Reference.Summary.extend({

		defaults: {
			modalElement: '.js-fieldset-summary__edit-modal',
			fieldElement: '.js-fieldset-summary__field',
			textElement: '.js-fieldset-summary__text',
			summary: null,

			modalWidth: 500,
			modalHeight: 300,

			lang: {},
			langPrefix: 'YANDEX_MARKET_FIELD_FIELDSET_'
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
			this.handleTextClick(true);
		},

		unbind: function() {
			this.handleTextClick(false);
		},

		handleTextClick: function(dir) {
			var textElement = this.getElement('text');

			textElement[dir ? 'on' : 'off']('click', $.proxy(this.onTextClick, this));
		},

		onTextClick: function(evt) {
			this.openEditModal();

			evt.preventDefault();
		},

		refreshSummary: function() {
			var template = this.options.summary;
			var displayValues = this.getDisplayValue();
			var groupedValues = this.groupValues(displayValues);
			var summaryValues = this.summaryValues(groupedValues);
			var textElement = this.getElement('text');
			var text;

			if (template) {
				text = this.renderTemplate(template, summaryValues);
			} else {
				text = this.joinValues(summaryValues);
			}

			textElement.html(text);
		},

		groupValues: function(values) {
			var key;
			var keyParts;
			var keyPartLength;
			var keyPartIndex;
			var keyPart;
			var valueChain;
			var result = {};

			for (key in values) {
				if (!values.hasOwnProperty(key)) { continue; }

				keyParts = key.split('[');
				valueChain = result;

				if (keyParts[0] === '') { keyParts.shift(); }

				keyPartLength = keyParts.length;

				for (keyPartIndex = 0; keyPartIndex < keyPartLength; ++keyPartIndex) {
					keyPart = keyParts[keyPartIndex];
					keyPart = keyPart.replace(']', '');

					if (keyPartIndex + 1 === keyPartLength) { // is last
						valueChain[keyPart] = values[key];
					} else {
						if (!(keyPart in valueChain)) {
							valueChain[keyPart] = {};
						}

						valueChain = valueChain[keyPart];
					}
				}
			}

			return result;
		},

		summaryValues: function(values) {
			var key;
			var template;
			var unitOption;
			var unit;

			for (key in values) {
				if (!values.hasOwnProperty(key)) { continue; }
				if (typeof values[key] !== 'object' || values[key] == null) { continue; }

				template = this.getFieldSummaryTemplate(key);
				unitOption = this.getFieldSummaryUnit(key);

				if (template != null) {
					values[key] = this.renderTemplate(template, values[key]);
				}

				if (unitOption != null) {
					unit = this.formatUnit(values[key], unitOption);

					if (unit != null) {
						values[key] = '' + values[key] + ' ' + unit;
					}
				}
			}

			return values;
		},

		getFieldSummaryTemplate: function(key) {
			return this.getFieldOption(key, 'summary');
		},

		getFieldSummaryUnit: function(key) {
			var result = this.getFieldOption(key, 'unit');

			if (result != null && result.indexOf('|') !== -1) {
				result = result.split('|');
			}

			return result;
		},

		formatUnit: function(value, unit) {
			var numberMatch;
			var number;
			var result;

			if (typeof value === 'number') {
				number = parseInt(value, 10);
			} else if (typeof value === 'string') {
				numberMatch = /(\d+([.,]\d+)?)\D*$/.exec(value); // extract last number

				if (numberMatch) {
					number = parseInt(numberMatch[1], 10);
				}
			}

			if (number != null && !isNaN(number)) {
				result = Array.isArray(unit) ? utils.sklon(number, unit) : unit;
			}

			return result;
		},

		getFieldOption: function(key, type) {
			var optionKey =
				'field'
				+ key.substring(0, 1).toUpperCase()
				+ key.substring(1).toLowerCase()
				+ type.substring(0, 1).toUpperCase()
				+ type.substring(1).toLowerCase();

			return this.options[optionKey];
		},

		renderTemplate: function(template, values) {
			var usedKeys = this.getTemplateUsedKeys(template);
			var replaces = this.getTemplateReplaces(values, usedKeys);
			var result = template;

			result = this.applyTemplateRemoveVariables(result, usedKeys, replaces);
			result = this.applyTemplateReplaceVariables(result, replaces);

			return result;
		},

		getTemplateReplaces: function(values, keys) {
			var result = {};
			var keyIndex;
			var key;

			for (keyIndex = 0; keyIndex < keys.length; keyIndex++) {
				key = keys[keyIndex];

				if (values[key]) {
					result[key] = values[key];
				}
			}

			return result;
		},

		applyTemplateRemoveVariables: function(template, keys, replaces) {
			var result = template;
			var keyIndex;
			var key;

			for (keyIndex = 0; keyIndex < keys.length; keyIndex++) {
				key = keys[keyIndex];

				if (!(key in replaces)) {
					result = this.removeTemplateVariable(result, key);
				}
			}

			return result;
		},

		applyTemplateReplaceVariables: function(template, replaces) {
			var result = template;
			var key;

			for (key in replaces) {
				if (!replaces.hasOwnProperty(key)) { continue; }

				result = this.replaceTemplateVariable(result, key, replaces[key]);
			}

			return result;
		},

		replaceTemplateVariable: function(template, key, value) {
			return template.replace('#' + key + '#', value);
		},

		removeTemplateVariable: function(template, key) {
			var search = '#' + key + '#';
			var searchLength = search.length;
			var searchPosition;
			var before;
			var after;
			var result = template;

			while ((searchPosition = result.indexOf(search)) !== -1) {
				before = result.substring(0, searchPosition);
				before = this.trimRightPart(before);
				after = result.substring(searchPosition + searchLength);
				after = this.trimLeftPart(after);

				if (after[0] === ',' && before[before.length - 1] === '.') {
					after = after.substring(0, after.length - 1);
				}

				result = before + after;
			}

			return result;
		},

		trimLeftPart: function(part) {
			return part.replace(/^[^#.,]+/, '');
		},

		trimRightPart: function(part) {
			return part.replace(/,?[^#.,]+$/, '');
		},

		getTemplateUsedKeys: function(template) {
			var pattern = /#([A-Z0-9_]+?)#/g;
			var match;
			var result = [];

			while (match = pattern.exec(template)) {
				result.push(match[1]);
			}

			return result;
		},

		joinValues: function(values) {
			return Object.values(values).join(', ');
		},

		getFieldPlugin: function() {
			return Fieldset.Row;
		}

	}, {
		dataName: 'FieldFieldsetSummary',
		pluginName: 'YandexMarket.Field.Fieldset.Summary'
	});

})(BX, window);