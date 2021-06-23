(function(BX, $, window) {

	const Plugin = BX.namespace('YandexMarket.Plugin');
	const Input = BX.namespace('YandexMarket.Ui.Input');

	const constructor = Input.Calendar = Plugin.Base.extend({

		defaults: {
			fieldElement: 'input, textarea',
			time: false,
			format: null,
			autoclose: true,
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
			this.handleClick(true);
			this.handleFieldClick(true);
		},

		unbind: function() {
			this.handleClick(false);
			this.handleFieldClick(false);
		},

		handleClick: function(dir) {
			this.$el[dir ? 'on' : 'off']('click', $.proxy(this.onClick, this));
		},

		handleFieldClick: function(dir) {
			const field = this.getField();

			field[dir ? 'on' : 'off']('click', $.proxy(this.onFieldClick, this));
		},

		onClick: function(evt) {
			this.open();
			evt.preventDefault();
		},

		onFieldClick: function(evt) {
			this.open();
			evt.preventDefault();
		},

		onCalendarSelect: function(date) {
			this.setValue(date);
			this.options.autoclose && this.close();

			return false;
		},

		open: function() {
			const options = this.getCalendarOptions();
			const calendar = this.getCalendar();

			if (calendar.popup && calendar.popup.isShown()) {
				calendar.Close();
			}

			calendar.Show(options);
		},

		close: function() {
			this.getCalendar().Close();
		},

		getCalendar: function() {
			return BX.calendar.get();
		},

		getCalendarOptions: function() {
			return {
				node: this.el,
				value: this.getValue(),
				bTime: this.options.time,
				bHideTime: true,
				callback: $.proxy(this.onCalendarSelect, this),
			};
		},

		isTimeOpen: function() {
			const calendar = BX.calendar.get();

			return BX.hasClass(calendar.PARTS.TIME, 'bx-calendar-set-time-opened');
		},

		setValue: function(value) {
			const fieldValue = this.stringify(value);
			const field = this.getField();

			field.val(fieldValue);
			field.trigger('change');
		},

		getValue: function() {
			const value = this.getField().val();

			return this.parse(value);
		},

		parse: function(value) {
			const now = new Date();
			let hasYear = true;
			let format = this.getFormat();
			let result;

			if (value && format && format.indexOf('YYYY') === -1) {
				hasYear = false;
				format += '.YYYY';
				value += '.' + now.getFullYear();
			}

			result = BX.parseDate(value, true, format, format);

			if (result && !hasYear && now.getMonth() > result.getMonth()) {
				result.setFullYear(result.getFullYear() + 1);
			}

			return result;
		},

		stringify: function(value) {
			const format = this.getFormat();

			return BX.date.format(BX.date.convertBitrixFormat(format), value, null, false);
		},

		getFormat: function() {
			let result;

			if (this.options.format) {
				result = this.options.format;
			} else if (this.options.time && this.isTimeOpen()) {
				result = BX.message('FORMAT_DATETIME');
			} else {
				result = BX.message('FORMAT_DATE');
			}

			return result;
		},

		getField: function() {
			return this.getElement('field', this.$el, 'prev');
		},

	}, {
		dataName: 'UiInputCalendar'
	});

})(BX, jQuery, window);