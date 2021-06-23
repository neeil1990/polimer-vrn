(function(BX, $, window) {

	var Plugin = BX.namespace('YandexMarket.Plugin');
	var Input = BX.namespace('YandexMarket.Ui.Input');

	var constructor = Input.CheckToggle = Plugin.Base.extend({

		defaults: {
			inputElement: null,
			targetElement: null,
			inverse: false,
			value: null,

			lang: {},
		},

		initialize: function() {
			this.bind();
		},

		destroy: function() {
			this.unbind();
			this.callParent('destroy', constructor);
		},

		bind: function() {
			this.handleChange(true);
		},

		unbind: function() {
			this.handleChange(false);
		},

		handleChange: function(dir) {
			var input = this.getInput();

			input[dir ? 'on' : 'off']('change', $.proxy(this.onChange, this));
		},

		onChange: function() {
			this.toggle();
		},

		isActive: function() {
			var input = this.getInput();
			var result;

			if (this.options.value === null) {
				result = !!input.prop('checked');
			} else {
				result = (input.val() === this.options.value);
			}

			return result;
		},

		activate: function() {
			this.toggle(true);
		},

		deactivate: function() {
			this.toggle(false);
		},

		toggle: function(dir) {
			var dirNormalized = (dir != null ? !!dir : this.isActive());

			this.toggleTarget(dirNormalized);
		},

		toggleTarget: function(dir) {
			var target = this.getElement('target', $(document));
			var classDir = !dir;

			if (this.options.inverse) {
				classDir = !classDir;
			}

			target.toggleClass('is--hidden', classDir);
		},

		getInput: function() {
			var inputSelector = this.getElementSelector('input');
			var result;

			if (inputSelector) {
				result = this.$el.find(inputSelector);
			} else {
				result = this.$el;
			}

			return result;
		}

	}, {
		dataName: 'uiCheckToggle'
	});

})(BX, jQuery, window);