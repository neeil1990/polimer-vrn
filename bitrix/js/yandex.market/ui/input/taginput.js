(function(BX, $, window) {

	var Plugin = BX.namespace('YandexMarket.Plugin');
	var Input = BX.namespace('YandexMarket.Ui.Input');

	var constructor = Input.TagInput = Plugin.Base.extend({

		defaults: {
			width: 200,
			tags: true,
			dataAdapter: null,
			data: null,
			ajax: null,
			lazy: false,

			lang: {},
			langPrefix: 'YANDEX_MARKET_CHOSEN_'
		},

		initVars: function() {
			this.callParent('initVars', constructor);
			this._isPluginReady = false;
		},

		initialize: function() {
			this.clearClone();
			this.callParent('initialize', constructor);

			if (this.options.lazy) {
				this.bindLazy();
			} else {
				this.createPlugin();
			}
		},

		destroy: function() {
			this.destroyPlugin();
			this.unbindLazy();
			this.callParent('destroy', constructor);
		},

		bindLazy: function() {
			this.handleFocus(true);
			this.handleMouseDown(true);
		},

		unbindLazy: function() {
			this.handleFocus(false);
			this.handleMouseDown(false);
		},

		handleFocus: function(dir) {
			this.$el[dir ? 'on' : 'off']('focus', $.proxy(this.onFocus, this));
		},

		handleMouseDown: function(dir) {
			this.$el[dir ? 'on' : 'off']('mousedown', $.proxy(this.onMouseDown, this));
		},

		onFocus: function() {
			var _this = this;

			this.unbindLazy();

			setTimeout(function() {
				_this.createPlugin();
			});
		},

		onMouseDown: function(evt) {
			var _this = this;

			this.unbindLazy();

			setTimeout(function() {
				_this.createPlugin();
				_this.openDropdown();
			});

			evt.preventDefault();
		},

		clearClone: function() {
			var pluginContainer = this.$el.next();

			this.$el.removeClass('select2-hidden-accessible').removeAttr('aria-hidden').removeAttr('tabindex');

			if (pluginContainer.hasClass('select2')) {
				pluginContainer.remove();
			}
		},

		refreshPlugin: function() {
			this.destroyPlugin();
			this.createPlugin();
		},

		openDropdown: function() {
			this.$el.select2('open');
		},

		createPlugin: function() {
			if (this._isPluginReady) { return; }

			var options = this.createPluginOptions();

			this._isPluginReady = true;

			this.$el.select2(options);
		},

		createPluginOptions: function() {
			return $.extend(true, {
				width: this.options.width,
				tags: this.options.tags,
				dataAdapter: this.options.dataAdapter,
				data: this.options.data,
				ajax: this.options.ajax,
				dropdownParent: this.$el.parent(),
			}, this.getLanguageOptions());
		},

		getLanguageOptions: function() {
			var _this = this;

			return {
				placeholder: _this.getLang('PLACEHOLDER'),
				language: {
					errorLoading: function () {
						return _this.getLang('LOAD_ERROR');
					},
					inputTooLong: function (t) {
						return _this.getLang('TOO_LONG', {
							'LIMIT': t.maximum
						});
					},
					inputTooShort: function (t) {
						return _this.getLang('TOO_SHORT', {
							'LIMIT': t.minimum
						});
					},
					loadingMore: function () {
						return _this.getLang('LOAD_PROGRESS');
					},
					maximumSelected: function (t) {
						return _this.getLang('MAX_SELECT', {
							'LIMIT': t.maximum
						});
					},
					noResults: function () {
						return _this.getLang('NO_RESULTS');
					},
					searching: function () {
						return _this.getLang('SEARCHING');
					}
				}
			};
		},

		destroyPlugin: function() {
			if (!this._isPluginReady) { return; }

			this._isPluginReady = false;
			this.$el.select2('destroy');
		}

	}, {
		dataName: 'uiTagInput'
	});

})(BX, jQuery, window);