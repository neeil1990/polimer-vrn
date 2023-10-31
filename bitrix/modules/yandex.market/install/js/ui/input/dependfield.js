(function(BX, $, window) {

	var Plugin = BX.namespace('YandexMarket.Plugin');
	var Input = BX.namespace('YandexMarket.Ui.Input');

	var constructor = Input.DependField = Plugin.Base.extend({

		defaults: {
			depend: null,
			headingElement: '.heading',
			siblingElement: 'tr',
			formElement: 'form',
			inputElement: 'input, select, textarea',
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
			this.handleDependChange(true);
		},

		unbind: function() {
			this.handleDependChange(false);
		},

		handleDependChange: function(dir) {
			var fields = this.getDependElements();

			fields[dir ? 'on' : 'off']('change', $.proxy(this.onDependChange, this));
		},

		onDependChange: function() {
			this.update();
		},

		update: function() {
			var isMatch = this.resolveDependRules();

			if (this.alreadyView(isMatch)) { return; }

			this.toggleView(isMatch);
			this.toggleHeaderView(isMatch);
			this.fireChange();
		},

		alreadyView: function(isMatch) {
			return this.$el.hasClass('is--hidden') === !isMatch;
		},

		toggleView: function(isMatch) {
			this.$el.toggleClass('is--hidden', !isMatch);
		},

		toggleHeaderView: function(isMatch) {
			var heading = this.getHeading();
			var isHidden = heading.hasClass('is--hidden');
			var siblings;

			if (isHidden !== !isMatch) {
				siblings = this.getSiblingsUnderHeading(heading);
				siblings = siblings.not('.is--hidden');

				heading.toggleClass('is--hidden', siblings.length === 0);
			}
		},

		getHeading: function() {
			var heading = this.getElement('heading', this.$el, 'prevAll');

			return heading.first();
		},

		getSiblingsUnderHeading: function(heading) {
			var headerSelector = this.getElementSelector('heading');
			var fieldSelector = this.getElementSelector('sibling');
			var sibling = heading;
			var result = $();

			do {
				sibling = sibling.next();

				if (sibling.is(headerSelector)) { break; }

				if (sibling.is(fieldSelector)) {
					result = result.add(sibling);
				}
			} while (sibling.length !== 0);

			return result;
		},

		fireChange: function() {
			var input = this.getElement('input');

			input.trigger('change');
		},

		resolveDependRules: function() {
			var rules = this.options.depend;
			var rule;
			var fields = this.getDependFields();
			var fieldKey;
			var field;
			var fieldValue;
			var isDependAny = this.isDependAny();
			var result = !isDependAny;

			for (fieldKey in fields) {
				if (!fields.hasOwnProperty(fieldKey)) { continue; }

				field = fields[fieldKey];
				fieldValue = this.getFieldValue(field, fieldKey);
				rule = rules[fieldKey];

				if (this.isMatchRule(rule, fieldValue) === isDependAny) {
					result = isDependAny;
					break;
				}
			}

			return result;
		},

		getFieldValue: function(field, name) {
			var instance;
			var result;

			if (this.isHiddenField(field) && !$.contains(this.el, field[0])) { return null; }

			switch (this.getFieldType(field, name))
			{
				case 'plugin':
					instance = Plugin.manager.getInstance(field);
					result = !instance.isEmpty();
				break;

				case 'complex':
					result = this.getComplexValue(field, name);
				break;

				case 'hidden':
					if (field.length > 1) { // is checkbox sibling
						result = this.getFieldValue(field.slice(1));
					} else {
						result = field.val();
					}
				break;

				case 'checkbox':
					result = [];
					field.each(function() { if (this.checked) { result.push(this.value); } });
				break;

				case 'radio':
					field.each(function() { if (this.checked) { result = this.value; } });
				break;

				default:
					result = field.val();
				break;
			}

			return result;
		},

		isHiddenField: function(field) {
			var row = this.getElement('sibling', field, 'closest');

			return row.hasClass('is--hidden');
		},

		getFieldType: function(field, name) {
			var pluginName = field.data('plugin');
			var selfName = field.data('name');
			var plugin = pluginName && Plugin.manager.getPlugin(pluginName);
			var result = (field.prop('tagName') || '').toLowerCase();

			if (result === 'input') {
				result = (field.prop('type') || '').toLowerCase();
			}

			if (plugin && ('isEmpty' in plugin.prototype)) {
				result = 'plugin';
			} else if (selfName != null && selfName !== name && selfName.indexOf('[' + name + ']') === 0) {
				result = 'complex';
			}

			return result;
		},

		getComplexValue: function(field, baseName) {
			var childIndex;
			var child;
			var childFullName;
			var childName;
			var nameStart = '[' + baseName + ']';
			var result = {};

			for (childIndex = 0; childIndex < field.length; childIndex++) {
				child = field.eq(childIndex);
				childFullName = child.data('name');

				if (childFullName == null || childFullName.indexOf(nameStart) !== 0) { continue; }

				childName = childFullName.substring(nameStart.length);
				result[childName] = this.getFieldValue(child, childFullName);
			}

			return result;
		},

		isMatchRule: function(rule, value) {
			var isEmpty;
			var result = true;

			switch (rule['RULE']) {
				case 'EMPTY':
					isEmpty = this.testIsEmpty(value);
					result = (isEmpty === rule['VALUE']);
				break;

				case 'ANY':
					result = this.applyRuleAny(rule['VALUE'], value);
				break;

				case 'EXCLUDE':
					result = !this.applyRuleAny(rule['VALUE'], value);
				break;
			}

			return result;
		},

		testIsEmpty: function(value) {
			let result = true;

			if (Array.isArray(value)) {
				for (const one of value) {
					if (!this.testIsEmpty(one)) {
						result = false;
						break;
					}
				}
			} else if ($.isPlainObject(value)) {
				for (const key in value) {
					if (!value.hasOwnProperty(key)) { continue; }

					if (!this.testIsEmpty(value[key])) {
						result = false;
						break;
					}
				}
			} else {
				result = (!value || value === '0');
			}

			return result;
		},

		applyRuleAny: function(ruleValue, formValue) {
			var isRuleMultiple = Array.isArray(ruleValue);
			var isFormMultiple = Array.isArray(formValue);
			var formIndex;
			var formItem;
			var result = false;

			if (isFormMultiple && isRuleMultiple) {
				for (formIndex = formValue.length - 1; formIndex >= 0; --formIndex) {
					formItem = formValue[formIndex];

					if (this.testInArray(formItem, ruleValue)) {
						result = true;
						break;
					}
				}
			} else if (isFormMultiple) {
				result = this.testInArray(ruleValue, formValue);
			} else if (isRuleMultiple) {
				result = this.testInArray(formValue, ruleValue);
			} else {
				// noinspection EqualityComparisonWithCoercionJS
				result = (formValue == ruleValue);
			}

			return result;
		},

		testInArray: function(needle, haystack) {
			let result = false;

			for (let i = haystack.length - 1; i >= 0; i--) {
				// noinspection EqualityComparisonWithCoercionJS
				if (haystack[i] == needle) {
					result = true;
					break;
				}
			}

			return result;
		},

		getDependElements: function() {
			var fields = this.getDependFields();
			var fieldKey;
			var field;
			var result = $();

			for (fieldKey in fields) {
				if (!fields.hasOwnProperty(fieldKey)) { continue; }

				field = fields[fieldKey];
				result = result.add(field);
			}

			return result;
		},

		isDependAny: function() {
			return this.options.depend['LOGIC'] === 'OR';
		},

		getDependFields: function() {
			var keys = Object.keys(this.options.depend);
			var keyIndex;
			var key;
			var field;
			var fields = {};

			for (keyIndex = 0; keyIndex < keys.length; keyIndex++) {
				key = keys[keyIndex];

				if (key === 'LOGIC') { continue; }

				field = this.getField(key);

				if (field) {
					fields[key] = field;
				}
			}

			return fields;
		},

		getField: function(selector) {
			var result;

			if (selector.substr(0,1 ) === '#') {
				result = $(selector);
			} else {
				result = this.getFormField(selector);
			}

			return result;
		},

		getFormField: function(name) {
			var form = this.getElement('form', this.$el, 'closest');
			var isForm = form.is('form');
			var nameMultiple = name + '[]';
			var variants;
			var variantIndex;
			var variant;
			var result;

			if (isForm && form[0][name] != null) {
				result = $(form[0][name]);
			} else if (isForm && form[0][nameMultiple] != null) {
				result = $(form[0][nameMultiple]);
			} else {
				variants = [
					'[data-name="' + name + '"]',
					'[data-name^="[' + name + ']"]',
				];

				for (variantIndex = 0; variantIndex < variants.length; variantIndex++) {
					variant = variants[variantIndex];
					result = form.find(variant);

					if (result.length > 0) { break; }
				}
			}

			return result;
		},

	}, {
		dataName: 'UiInputDependField'
	});

})(BX, jQuery, window);