(function(BX, $, window) {

	const Plugin = BX.namespace('YandexMarket.Plugin');
	const Input = BX.namespace('YandexMarket.Ui.Input');

	const constructor = Input.CalendarGlue = Input.Calendar.extend({

		defaults: {
			glue: ',',
			autoclose: false,
		},

		getCalendar: function() {
			return Input.Internals.getCalendarMultiple();
		},

		getCalendarOptions: function() {
			return Object.assign(this.callParent('getCalendarOptions', constructor), {
				values: this.getValues(),
			});
		},

		redrawCalendar: function() {
			const values = this.getValues();

			this.getCalendar().ymPassValues(values);
		},

		setValue: function(value) {
			const fieldValue = this.stringify(value);
			const values = this.getFieldValues();
			const existIndex = values.indexOf(fieldValue);

			if (existIndex === -1) {
				values.push(fieldValue);
			} else {
				values.splice(existIndex, 1);
			}

			this.setFieldValues(values);
			this.redrawCalendar();
		},

		getValue: function() {
			const values = this.getValues();

			return values[0];
		},

		getValues: function() {
			const result = [];
			let value;
			let date;

			for (value of this.getFieldValues()) {
				date = this.parse(value);

				if (date != null) {
					result.push(date);
				}
			}

			return result;
		},

		setFieldValues: function(values) {
			const field = this.getField();
			const sortedValues = this.sortFieldValues(values);

			field.val(sortedValues.join(this.options.glue));
			field.trigger('change');
		},

		getFieldValues: function() {
			const valueGlued = this.getField().val() || '';
			let values = valueGlued.split(this.options.glue);

			values = values.map(function(value) { return value.trim(); });
			values = values.filter(function(value) { return value !== ''; });

			return values;
		},

		sortFieldValues: function(values) {
			values.sort(function(a, b) {
				const aParts = a.split(/\D+/);
				const bParts = b.split(/\D+/);
				const length = Math.min(3, aParts.length);

				for (let index = length - 1; index >= 0; --index) {
					const aPart = aParts[index];
					const bPart = bParts[index];

					if (aPart === bPart) { continue; }

					return aPart < bPart ? -1 : 1;
				}

				return 0;
			});

			return values;
		}

	}, {
		dataName: 'UiInputCalendarGlue'
	});

})(BX, jQuery, window);