(function(BX, $, window) {

	var Plugin = BX.namespace('YandexMarket.Plugin');
	var Reference = BX.namespace('YandexMarket.Field.Reference');

	var constructor = Reference.Complex = Reference.Base.extend({

		defaults: {
			childElement: null
		},

		initialize: function() {
			this.callParent('initialize', constructor);
			this.setParentForChild();
			this.setBaseNameChild();
		},

		cloneInstance: function(newInstance) {
			var baseName = this.getBaseName();
			var index = this.getIndex();
			var newChildInstanceMap = newInstance.getChildInstanceMap();
			var rawValues = this.getRawValue();

			newInstance.setBaseName(baseName);
			newInstance.setIndex(index);
			newInstance.setRawValue(rawValues);

			this.callChildList(function(childInstance) {
				var childName = childInstance.getName();
				var newChildInstance = newChildInstanceMap[childName];

				childInstance.cloneInstance(newChildInstance);
				newChildInstance.setParentField(newInstance);
			});
		},

		initEdit: function() {
			var result = this.callParent('initEdit', constructor);

			if (!result) {
				this.callChildList(function(instance) {
					if (!result) {
						result = instance.initEdit();
					}
				});
			}

			return result;
		},

		setParentForChild: function() {
			var parent = this;

			this.callChildList(function(instance) {
				instance.setParentField(parent);
			});
		},

		setBaseName: function(baseName) {
			this.callParent('setBaseName', [baseName], constructor);
			this.setBaseNameChild();
		},

		setBaseNameChild: function() {
			var baseName = this.getBaseName();

			this.callChildList(function(instance) {
				var name = instance.getName();
				var childName = baseName + (name.indexOf('[') !== -1 ? name : '[' + name + ']');

				instance.setBaseName(childName);
			});
		},

		clear: function() {
			this.callParent('clear', constructor);
			this.callChildList('clear');
		},

		updateName: function() {
			this.callParent('updateName', constructor);
			this.callChildList('updateName');
		},

		unsetName: function() {
			this.callParent('unsetName', constructor);
			this.callChildList('unsetName');
		},

		setValue: function(valueList) {
			this.setRawValue(valueList);
			this.callChildList(function(instance) {
				var childName = instance.getName();
				var childValue = valueList[childName];

				instance.setValue(childValue);
			});
		},

		setRawValue: function(valueList) {
			this.callParent('setValue', [valueList], constructor);
		},

		applyDefaults: function() {
			this.callParent('applyDefaults', constructor);
			this.callChildList('applyDefaults');
		},

		getDefaultValues: function() {
			const result = this.callParent('getDefaultValues', constructor);

			this.callChildList(function(instance) {
				result[instance.getName()] = instance.getDefaultValues();
			});

			return result;
		},

		getValue: function() {
			var result = this.getRawValue();

			this.callChildList(function(instance) {
				var childName = instance.getName();

				result[childName] = instance.getValue();
			});

			return result;
		},

		getRawValue: function() {
			return this.callParent('getValue', constructor);
		},

		getDisplayValue: function() {
			var result = this.callParent('getDisplayValue', constructor);

			this.callChildList(function(instance) {
				var childName = instance.getName();

				result[childName] = instance.getDisplayValue();
			});

			return result;
		},

		callChildList: function(method, args) {
			var childList = this.getElement('child');
			var child;
			var childIndex;

			for (childIndex = 0; childIndex < childList.length; childIndex++) {
				child = childList.eq(childIndex);

				if (!child.hasClass('is--hidden')) { // is not placeholder
					this.callChild(child, method, args);
				}
			}
		},

		callChild: function(element, method, args) {
			var instance = this.getChildInstance(element);

			if (typeof method === 'string') {
				instance[method].apply(instance, args);
			} else {
				method(instance);
			}
		},

		getChildInstanceMap: function() {
			var result = {};

			this.callChildList(function(instance) {
				result[instance.getName()] = instance;
			});

			return result;
		},

		getChildInstance: function(element) {
			var pluginName = element.data('plugin');
			var plugin = Plugin.manager.getPlugin(pluginName);

			return plugin.getInstance(element);
		},

		getChildField: function(fieldName) {
			var map = this.getChildInstanceMap();

			return fieldName in map ? map[fieldName] : null;
		}

	});

})(BX, jQuery, window);
