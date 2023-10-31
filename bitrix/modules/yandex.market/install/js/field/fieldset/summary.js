(function(BX, window) {

	var Reference = BX.namespace('YandexMarket.Field.Reference');
	var Fieldset = BX.namespace('YandexMarket.Field.Fieldset');
	var utils = BX.namespace('YandexMarket.Utils');

	var constructor = Fieldset.Summary = Reference.Summary.extend({

		defaults: {
			modalElement: '.js-fieldset-summary__edit-modal',
			fieldElement: '.js-fieldset-summary__field',
			clearElement: '.js-fieldset-summary__clear',
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
			this.handleClearClick(true);
		},

		unbind: function() {
			this.handleTextClick(false);
			this.handleClearClick(false);
		},

		handleTextClick: function(dir) {
			var textElement = this.getElement('text');

			textElement[dir ? 'on' : 'off']('click', $.proxy(this.onTextClick, this));
		},

		handleClearClick: function(dir) {
			var clearElement = this.getElement('clear');

			clearElement[dir ? 'on' : 'off']('click', $.proxy(this.onClearClick, this));
		},

		onTextClick: function(evt) {
			this.openEditModal();

			evt.preventDefault();
		},

		onClearClick: function(evt) {
			this.clear();

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

			if (text === '') {
				text = this.getLang('PLACEHOLDER');
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
			let key;
			let value;
			let displayValueParts;
			let displayValue;
			let template;
			let unitOption;

			for (key in values) {
				if (!values.hasOwnProperty(key)) { continue; }
				if (typeof values[key] !== 'object' || values[key] == null) { continue; }

				value = values[key];
				template = this.getFieldSummaryTemplate(key);
				unitOption = this.getFieldSummaryUnit(key);

				if (Array.isArray(value)) {
					displayValueParts = value.map((valueItem) => this.summaryValueTemplate(valueItem, template, unitOption));
					displayValue = displayValueParts.join(', ');
				} else {
					displayValue = this.summaryValueTemplate(value, template, unitOption);
				}

				values[key] = displayValue;
			}

			return values;
		},

		summaryValueTemplate: function(value, template, unitOption) {
			let result = value;
			let unit;

			if (template != null) {
				result = this.renderTemplate(template, result);
			}

			if (unitOption != null) {
				unit = this.formatUnit(result, unitOption);

				if (unit != null) {
					result = '' + result + ' ' + unit;
				}
			}

			return result;
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
			var chain;
			var chainIndex;
			var chainKey;
			var level;

			for (keyIndex = 0; keyIndex < keys.length; keyIndex++) {
				key = keys[keyIndex];
				chain = key.split('.');
				level = values;

				for (chainIndex = 0; chainIndex < chain.length; chainIndex++) {
					chainKey = chain[chainIndex];

					if (level[chainKey] == null) { break; }

					if (chainIndex < chain.length - 1) {
						level = level[chainKey];
					} else if (level[chainKey]) {
						result[key] = level[chainKey];
					}
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
			var beforeParentheses;
			var beforeOuter;
			var beforeInner;
			var after;
			var afterParentheses;
			var afterOuter;
			var afterInner;
			var result = template;

			while ((searchPosition = result.indexOf(search)) !== -1) {
				before = result.substring(0, searchPosition);
				after = result.substring(searchPosition + searchLength);
				beforeParentheses = before.indexOf('(');
				afterParentheses = after.indexOf(')');

				if (beforeParentheses !== -1 && afterParentheses !== -1) { // inside parentheses
					beforeOuter = before.substring(0, beforeParentheses + 1);
					beforeInner = before.substring(beforeParentheses + 1);
					afterOuter = after.substring(afterParentheses);
					afterInner = after.substring(0, afterParentheses);
					beforeInner = this.trimRightPart(beforeInner);
					afterInner = this.trimLeftPart(afterInner, beforeInner);

					if (beforeInner === '' && afterInner === '') { // then remove parentheses
						before = beforeOuter.substring(0, beforeParentheses);
						after = afterOuter.substring(1);
					} else {
						before = beforeOuter + beforeInner;
						after = afterInner + afterOuter;
					}
				} else {
					before = this.trimRightPart(before);
					after = this.trimLeftPart(after, before);
				}

				if (after[0] === '(') {
					after = ' ' + after;
				}

				result = before + after;
			}

			return result;
		},

		trimLeftPart: function(part, before) {
			var result = part.replace(/^[^#.,()]+/, '');

			if (result[0] === ',' && (before === '' || before[before.length - 1] === '.')) {
				result = result.substring(1);

				if (before === '') {
					result = result.trim();
				}
			}

			return result;
		},

		trimRightPart: function(part) {
			return part.replace(/,?[^#.,()]*$/, '');
		},

		getTemplateUsedKeys: function(template) {
			var pattern = /#([A-Z0-9_.]+?)#/g;
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