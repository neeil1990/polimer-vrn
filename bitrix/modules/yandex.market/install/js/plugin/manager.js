(function(BX, $, window) {

	var YandexMarket = BX.namespace('YandexMarket');
	var Plugin = BX.namespace('YandexMarket.Plugin');

	Plugin.Manager = Plugin.Base.extend({

		defaults: {
			pluginElement: '.js-plugin',
			clickPluginElement: '.js-plugin-click',
			delayedPluginElement: '.js-plugin-delayed'
		},

		initialize: function() {
			this.bind();
		},

		bind: function() {
			this.handleDocumentReady();
			this.handleAjaxSuccessFinish(true);
			this.handleContentUpdate(true);
			this.handlePluginClick(true);
		},

		unbind: function() {
			this.handleAjaxSuccessFinish(false);
			this.handleContentUpdate(false);
			this.handlePluginClick(false);
		},

		handleDocumentReady: function() {
			if (document.readyState !== 'complete') {
				$(document).ready($.proxy(this.onDocumentReady, this));
			}
		},

		handleAjaxSuccessFinish: function(dir) {
			BX[dir ? 'addCustomEvent' : 'removeCustomEvent']('onAjaxSuccessFinish', BX.proxy(this.onAjaxSuccessFinish, this));
		},

		handleContentUpdate: function(dir) {
			BX[dir ? 'addCustomEvent' : 'removeCustomEvent']('onYaMarketContentUpdate', BX.proxy(this.onContentUpdate, this));
		},

		handlePluginClick: function() {
			var selector = this.getElementSelector('clickPlugin');

			$(document).on('click', selector, $.proxy(this.onClickPlugin, this));
		},

		onDocumentReady: function() {
			this.initializeContext(document, false);
			this.handleAjaxSuccessFinish(false);
		},

		onAjaxSuccessFinish: function() {
			this.initializeContext(document);
			this.handleAjaxSuccessFinish(false);
		},

		onContentUpdate: function(evt) {
			this.initializeContext(evt.target);
		},

		onClickPlugin: function(evt) {
			var target = $(evt.currentTarget);
			var pluginList = this.initializeElement(target);
			var instance = pluginList.length > 0 ? pluginList[0] : null;

			if (instance) {
				instance.activate();
			}
		},

		initializeContext: function(context, includeDelayed) {
			this.callElementList('initializeElement', context, includeDelayed);
		},

		destroyContext: function(context, includeDelayed) {
			this.callElementList('destroyElement', context, includeDelayed);
		},

		callElementList: function(method, context, includeDelayed) {
			var elementList = this.getContextPluginElementList(context, includeDelayed);
			var element;
			var i;

			for (i = 0; i < elementList.length; i++) {
				element = elementList.eq(i);
				this[method](element);
			}
		},

		initializeElement: function(element) {
			var pluginList = (element.data('plugin') || '').split(',');
			var pluginIndex;
			var pluginName;
			var plugin;
			var result = [];

			for (pluginIndex = 0; pluginIndex < pluginList.length; pluginIndex++) {
				pluginName = pluginList[pluginIndex].trim();

				if (pluginName === '') { continue; }

				plugin = this.getPlugin(pluginName);

				if (plugin == null) { continue; }

				result.push(plugin.getInstance(element));
			}

			return result;
		},

		destroyElement: function(element) {
			var pluginList = (element.data('plugin') || '').split(',');
			var pluginIndex;
			var pluginName;
			var plugin;
			var instance;

			for (pluginIndex = 0; pluginIndex < pluginList.length; pluginIndex++) {
				pluginName = pluginList[pluginIndex].trim();

				if (pluginName === '') { continue; }

				plugin = this.getPlugin(pluginName);
				instance = plugin.getInstance(element, true);

				if (instance) {
					instance.destroy();
				}
			}
		},

		getInstance: function(element) {
			var plugins = this.initializeElement(element);

			return plugins[0];
		},

		getPlugin: function(name) {
			var nameParts = name.split('.');
			var nameNamespace;
			var pluginNamespace;
			var pluginName;

			if (nameParts.length > 1) {
				nameNamespace = nameParts.slice(0, -1).join('.');
				pluginNamespace = BX.namespace('YandexMarket.' + nameNamespace);
				pluginName = nameParts[nameParts.length - 1];
			} else {
				pluginNamespace = YandexMarket;
				pluginName = nameParts[0];
			}

			return pluginNamespace[pluginName];
		},

		getContextPluginElementList: function(contextNode, includeDelayed) {
			var context = contextNode instanceof $ ? contextNode : $(contextNode);
			var pluginSelector = this.getElementSelector('plugin');
			var delayedSelector;
			var delayedElements;
			var result = context.filter(pluginSelector).add(context.find(pluginSelector));

			if (includeDelayed == null || includeDelayed) {
				delayedSelector = this.getElementSelector('delayedPlugin');
				delayedElements = context.filter(delayedSelector).add(context.find(delayedSelector));

				if (delayedElements.length > 0) {
					result = result.add(delayedElements);
				}
			}

			return result;
		},

	});

	Plugin.manager = new Plugin.Manager();

})(BX, jQuery, window);