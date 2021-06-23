(function() {

	var AdminList = BX.namespace('YandexMarket.AdminList');

	AdminList.LoadMore = function(element, options) {
		this.el = element;
		this.options = BX.merge({}, this.defaults, options);

		this.initVars();
		this.initialize();
	};

	BX.merge(AdminList.LoadMore.prototype, {

		defaults: {
			navigationElement: '.adm-navigation',
			activeState: 'adm-nav-page-active',
			loadingState: 'adm-nav-page-loading',
			grid: null,
			url: null,
		},

		initVars: function() {
			this._defaultContents = null;
			this._scrollPosition = null;
		},

		initialize: function() {
			this.bind();
		},

		destroy: function() {
			this.unbind();
		},

		bind: function() {
			this.handleClick(true);
		},

		unbind: function() {
			this.handleClick(false);
		},

		handleClick: function(dir) {
			this.el[dir ? 'addEventListener' : 'removeEventListener']('click', this.onClick.bind(this));
		},

		onClick: function(evt) {
			this.activate();
			evt.preventDefault();
		},

		activate: function() {
			if (this.isLoading()) { return; }

			this.showLoader();

			this.loadContents(
				this.activateEnd.bind(this),
				this.activateStop.bind(this)
			);
		},

		activateStop: function() {
			this.hideLoader();
		},

		activateEnd: function(contents) {
			this.hideLoader();
			this.insertContents(contents);
			this.remove();
		},

		isLoading: function() {
			return this.el.classList.contains(this.options.loadingState);
		},

		showLoader: function() {
			var contents = this.el.textContent;

			this.el.textContent = '';
			this.el.classList.add(this.options.activeState);
			this.el.classList.add(this.options.loadingState);

			this._defaultContents = contents;
		},

		hideLoader: function() {
			this.el.textContent = this._defaultContents;
			this.el.classList.remove(this.options.loadingState);
			this.el.classList.remove(this.options.activeState);
		},

		remove: function() {
			this.el.parentElement.removeChild(this.el);
			this.destroy();
		},

		loadContents: function(resolve, reject) {
			var adminList = this.getAdminList();
			var url = this.getUrl(adminList);

			BX.ajax({
				method: 'GET',
				dataType: 'json',
				url: url,
				onsuccess: resolve,
				onfailure: reject,
			});
		},

		insertContents: function(contents) {
			this.prepareInsert();
			this.insertRows(contents['rows']);
			this.insertNavigation(contents['navigation']);
			this.finishInsert();
		},

		prepareInsert: function() {
			var adminList = this.getAdminList();

			BX.adminPanel.closeWait();
			BX.onCustomEvent(adminList.TABLE.tHead, 'onFixedNodeChangeState', [false]); // restore checkAll checkbox id
			adminList.Destroy();
		},

		finishInsert: function() {
			var adminList = this.getAdminList();

			this.saveScrollPosition();
			adminList.ReInit();
			BX.defer(this.restoreScrollPosition, this)();
		},

		insertRows: function(rows) {
			var adminList = this.getAdminList();

			adminList.TABLE.tBodies[0].insertAdjacentHTML('beforeend', rows);
		},

		insertNavigation: function(navigation) {
			var adminList = this.getAdminList();
			var oldNavigation = adminList.LAYOUT.querySelector(this.options.navigationElement);
			var navigationParts;

			if (oldNavigation != null) {
				navigationParts = BX.processHTML(navigation, false);

				oldNavigation.insertAdjacentHTML('afterend', navigationParts['HTML']);
				oldNavigation.parentElement.removeChild(oldNavigation);

				BX.ajax.processScripts(navigationParts['SCRIPT'], false);
			}
		},

		saveScrollPosition: function() {
			this._scrollPosition = BX.GetWindowSize();
		},

		restoreScrollPosition: function() {
			var position = this._scrollPosition;

			if (position != null) {
				window.scrollTo(position.scrollLeft, position.scrollTop);
				this._scrollPosition = null;
			}
		},

		getUrl: function(adminList) {
			var url = this.options.url;
			var params = {
				mode: 'loadMore',
				table_id: BX.util.urlencode(adminList.table_id)
			};

			url = BX.util.add_url_param(url, params);

			return url;
		},

		getAdminList: function() {
			return window[this.options.grid];
		}

	});

})();