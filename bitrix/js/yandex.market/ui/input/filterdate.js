(function(BX, $, window) {

	var Plugin = BX.namespace('YandexMarket.Plugin');
	var Input = BX.namespace('YandexMarket.Ui.Input');

	var constructor = Input.FilterDate = Input.TagInput.extend({

		defaults: {
			tags: true,
			time: false,

			iconTemplate: '<span class="adm-calendar-icon"></span>',
			iconElement: '.js-filter-date__icon'
		},

		initialize: function() {
			this.callParent('initialize', constructor);
			this.renderDateIcon();
			this.bind();
		},

		destroy: function() {
			this.unbind();
			this.removeDateIcon();
			this.callParent('destroy', constructor);
		},

		clearClone: function() {
			this.removeDateIcon();
			this.callParent('clearClone', constructor);
		},

		bind: function() {
			this.handleDateIconClick(true);
		},

		unbind: function() {
			this.handleDateIconClick(false);
		},

		handleDateIconClick: function(dir) {
			var icon = this.getDateIcon();

			icon[dir ? 'on' : 'off']('click', $.proxy(this.onDateIconClick, this));
		},

		onDateIconClick: function(evt) {
			this.openCalendar(evt.target);
		},

		removeDateIcon: function() {
			this.getDateIcon().remove();
		},

		renderDateIcon: function() {
			var iconTemplate = this.getTemplate('icon');
			var iconSelector = this.getElementSelector('icon');
			var iconClassName = iconSelector.substring(1);
			var icon = $(iconTemplate).addClass(iconClassName);

			icon.insertAfter(this.$el);
		},

		getDateIcon: function() {
			return this.getElement('icon', this.$el, 'next');
		},

		openCalendar: function(icon) {
			var _this = this;

			BX.calendar({
				node: icon,
				field: this.el,
				bTime: this.options.time,
				callback: function(date) {
					var withTime = this.params.bTime && BX.hasClass(this.PARTS.TIME, 'bx-calendar-set-time-opened');

					_this.selectDate(date, withTime);
					this.popup.close();

					return false;
				}
			});
		},

		selectDate: function(date, withTime) {
			var format = this.options.time && withTime ? BX.message('FORMAT_DATETIME') : BX.message('FORMAT_DATE');
			var dateString = BX.calendar.ValueToStringFormat(date, format);
			var option = $('<option />');

			option.text(dateString);
			option.attr('data-manual', true);
			option.prop('selected', true);

			this.$el.find('option[data-manual]').remove();
			this.$el.append(option);
			this.$el.trigger('change');
		}

	}, {
		dataName: 'uiFilterDate'
	});

})(BX, jQuery, window);