(function(BX, window) {

	var YandexMarket = BX.namespace('YandexMarket');

	// constructor

	YandexMarket.Dialog = function(arParams) {
		YandexMarket.Dialog.superclass.constructor.apply(this, arguments);
	};

	BX.extend(YandexMarket.Dialog, BX.CAdminDialog);

	YandexMarket.Dialog.prototype.SetContent = function(html) {
		var contents;
		var callback;
		var _this = this;

		YandexMarket.Dialog.superclass.SetContent.call(this, html);

		if (html != null) {
			contents = this.PARTS.CONTENT_DATA;
			callback = function() {
				_this.adjustSizeEx();
				BX.removeCustomEvent('onAjaxSuccessFinish', callback);
				BX.onCustomEvent(BX(contents), 'onYaMarketContentUpdate', [
					{ target: contents }
				]);
				BX.adminPanel && BX.adminPanel.modifyFormElements(contents);
			};

			BX.addCustomEvent('onAjaxSuccessFinish', callback);
		}
	};

	YandexMarket.Dialog.prototype.adjustSizeEx = function() {
		BX.defer(this.__adjustSizeEx, this)();
	};

	YandexMarket.Dialog.prototype.__adjustSizeEx = function() {
		var contentElement = this.PARTS.CONTENT_DATA;
		var contentHeight = contentElement.scrollHeight || contentElement.clientHeight;
		var contentWidth = contentElement.scrollWidth || contentElement.clientWidth;
		var windowWidth = (window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth) * 0.9;
		var windowHeight = (window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight) * 0.7;

		if (contentWidth > windowWidth) {
			contentWidth = windowWidth;
		}

		if (this.PARAMS.min_width > 0 && contentWidth < this.PARAMS.min_width) {
			contentWidth = this.PARAMS.min_width;
		} else if (this.PARAMS.max_width > 0 && contentWidth > this.PARAMS.max_width) {
			contentWidth = this.PARAMS.max_width;
		}

		if (contentHeight > windowHeight) {
			contentHeight = windowHeight;
		}

		if (this.PARAMS.min_height > 0 && contentHeight < this.PARAMS.min_height) {
			contentHeight = this.PARAMS.min_height;
		} else if (this.PARAMS.max_height > 0 && contentHeight > this.PARAMS.max_height) {
			contentHeight = this.PARAMS.max_height;
		}

		this.PARTS.CONTENT_DATA.style.width = contentWidth + 'px';
		this.PARTS.CONTENT_DATA.style.height = contentHeight + 'px';

		this.adjustPos();
	};

})(BX, window);