(function(BX, $, window) {

	const Plugin = BX.namespace('YandexMarket.Plugin');
	const Ui = BX.namespace('YandexMarket.Ui');
	const Utils = BX.namespace('YandexMarket.Utils');

	const constructor = Ui.Checker = Plugin.Base.extend({

		defaults: {
			startElement: '.js-checker__start',
			stopElement: '.js-checker__stop',

			progressElement: '.js-checker__progress',
			progressIndicatorElement: '.js-checker__progress-indicator',
			progressPercentElement: '.js-checker__progress-percent',

			wrapperElement: '.js-checker__result-wrapper',
			tableTemplate: '<table class="internal" width="100%"></table>',
			groupTemplate: '<tr class="heading"><td class="align-left" colspan="2">#GROUP#</td></tr>',

			rowElement: '.js-checker__row',
			rowTemplate: '<tr class="js-checker__row">' +
				'<td class="sc_row_label">#TITLE#</td>' +
				'<td>' +
					'<div class="sc_row_result">' +
						'<div class="sc_icon sc_icon_#STATUS#"></div>' +
						'<div class="sc_row_message">' +
							'<span class="sc_#STATUS#">#MESSAGE#</span>' +
							'#RESOLVE#' +
						'</div>' +
						'#DESCRIPTION#' +
					'</div>' +
				'</td>' +
			'</tr>',

			fixElement: '.js-checker__fix',
			fixTemplate: '<br /><br /><a class="js-checker__fix" href="#">#TITLE#</a>',

			descriptionElement: '.js-checker__description',
			descriptionTemplate: '<div class="sc_help_link js-checker__description" tabindex="0"></div>',
			descriptionOpenerTemplate: '<br /><br /><a class="js-checker__description" href="#">#TITLE#</a>',

			dialogContentTemplate: '<div style="font-size:1.2em;">#CONTENT#</div>',

			total: 0,
			autostart: false,

			lang: {},
			langPrefix: 'YANDEX_MARKET_CHECKER_',
		},

		initVars: function() {
			this._table = null;
			this._group = null;
			this._offset = 0;
		},

		initialize: function() {
			this.initVars();
			this.bind();

			if (this.options.autostart) {
				this.start();
			}
		},

		destroy: function() {
			this.unbind();
		},

		bind: function() {
			this.handleStartClick(true);
			this.handleStopClick(true);
			this.handleFixClick(true);
			this.handleDescriptionClick(true);
		},

		unbind: function() {
			this.handleStartClick(false);
			this.handleStopClick(false);
			this.handleFixClick(false);
			this.handleDescriptionClick(false);
		},

		handleStartClick: function(dir) {
			let button = this.getElement('start');

			button[dir ? 'on' : 'off']('click', $.proxy(this.onStartClick, this));
		},

		handleStopClick: function(dir) {
			let button = this.getElement('stop');

			button[dir ? 'on' : 'off']('click', $.proxy(this.onStopClick, this));
		},

		handleFixClick: function(dir) {
			let selector = this.getElementSelector('fix');

			this.$el[dir ? 'on' : 'off']('click', selector, $.proxy(this.onFixClick, this));
		},

		handleDescriptionClick: function(dir) {
			let selector = this.getElementSelector('description');

			this.$el[dir ? 'on' : 'off']('click', selector, $.proxy(this.onDescriptionClick, this));
		},

		onStartClick: function(evt) {
			this.start();
			evt.preventDefault();
		},

		onStopClick: function(evt) {
			this.stop();
			evt.preventDefault();
		},

		onFixClick: function(evt) {
			let button = $(evt.currentTarget);
			let row = this.getElement('row', button, 'closest');
			let offset = row.data('offset');

			this.showLoading();
			this.fix(offset);

			evt.preventDefault();
		},

		onDescriptionClick: function(evt) {
			let button = $(evt.currentTarget);
			let row = this.getElement('row', button, 'closest');
			let data = row.data('testData');

			this.showDescription(data);

			evt.preventDefault();
		},

		start: function() {
			this.resetOffset();
			this.toggleButtonDisable('start', true);
			this.toggleButtonDisable('stop', false);
			this.showLoading();
			this.showProgress();
			this.setProgress(0);
			this.createTable();
			this.test();
		},

		stop: function() {
			this.toggleButtonDisable('start', false);
			this.toggleButtonDisable('stop', true);
			this.hideProgress();
			this.hideLoading();
		},

		fix: function(offset) {
			let data = {
				action: 'fix',
				offset: offset,
			};

			this.query(
				data,
				$.proxy(this.fixEnd, this, offset),
				$.proxy(this.fixStop, this)
			);
		},

		fixStop: function(message) {
			let notify = this.getLang('FIX_ERROR', { 'MESSAGE': message });

			alert(notify);
			this.hideLoading();
		},

		fixEnd: function(offset, data) {
			if (this.isErrorResponse(data)) {
				this.testStop(data.error);
				return;
			}

			this.updateResult(offset, data);
			this.hideLoading();
		},

		test: function() {
			let data = {
				action: 'test',
				offset: this.getOffset(),
			};

			this.query(
				data,
				$.proxy(this.testEnd, this),
				$.proxy(this.testStop, this)
			);
		},

		testStop: function(message) {
			let notify = this.getLang('TEST_ERROR', { 'MESSAGE': message });

			alert(notify);
			this.stop();
		},

		testEnd: function(data) {
			if (this.isErrorResponse(data)) {
				this.testStop(data.error);
				return;
			}

			this.renderResult(data);
			this.testFinally();
		},

		testFinally: function() {
			this.increaseOffset();
			this.updateProgress();

			if (this.hasNext()) {
				this.test();
			} else {
				this.commit();
				this.stop();
			}
		},

		commit: function() {
			this.query({
				action: 'commit'
			});
		},

		query: function(data, resolve, reject) {
			BX.ajax({
				url: this.options.url,
				method: 'POST',
				data: $.extend(true, {}, data, {
					sessid: BX.bitrix_sessid(),
				}),
				dataType: 'json',
				onsuccess: resolve,
				onfailure: reject,
			});
		},

		isErrorResponse: function(data) {
			return data.error && !data.status;
		},

		toggleButtonDisable: function(type, dir) {
			let button = this.getElement(type);

			button.prop('disabled', !!dir);
		},

		showLoading: function() {
			ShowWaitWindow();
		},

		hideLoading: function() {
			CloseWaitWindow();
		},

		showProgress: function() {
			let progress = this.getElement('progress');

			progress.css('visibility', '');
		},

		hideProgress: function() {
			let progress = this.getElement('progress');

			progress.css('visibility', 'hidden');
		},

		updateProgress: function() {
			let offset = this.getOffset();
			let total = this.getTotal();

			if (total > 0) {
				this.setProgress(offset / total);
			} else {
				this.setProgress(1);
			}
		},

		setProgress: function(percent) {
			let indicatorElement = this.getElement('progressIndicator');
			let percentElement = this.getElement('progressPercent');
			let percentString = (Math.round(percent * 100) || 0) + '%';

			indicatorElement.css('width', percentString);
			percentElement.text(percentString);
		},

		createTable: function() {
			let wrapper = this.getElement('wrapper');
			let template = this.getTemplate('table');
			let table = $(template);

			wrapper.empty().append(table);

			this._table = table;
			this._group = null;
		},

		getTable: function() {
			return this._table;
		},

		updateResult: function(offset, data) {
			this.updateRow(offset, data);
		},

		renderResult: function(data) {
			this.renderGroup(data);
			this.renderRow(data);
		},

		renderGroup: function(data) {
			if (!data.group || data.group === this._group) { return; }

			let table = this.getTable();
			let template = this.getTemplate('group');
			let html = Utils.compileTemplate(template, { 'GROUP': data.group });
			let heading = $(html);

			heading.appendTo(table);
			this._group = data.group;
		},

		getRow: function(offset) {
			let rows = this.getRows();
			let row;
			let index;
			let result;

			for (index = 0; index < rows.length; ++index) {
				row = rows.eq(index);

				if (row.data('offset') === offset) {
					result = row;
					break;
				}
			}

			return result;
		},

		getRows: function() {
			return this.getElement('row');
		},

		updateRow: function(offset, data) {
			let oldRow = this.getRow(offset);
			let newRow = this.buildRow(data);

			newRow.data('offset', offset);
			newRow.data('testData', data);
			newRow.replaceAll(oldRow);
		},

		renderRow: function(data) {
			let table = this.getTable();
			let row = this.buildRow(data);

			row.data('offset', this.getOffset());
			row.data('testData', data);
			row.appendTo(table);
		},

		buildRow: function(data) {
			let template = this.getTemplate('row');
			let variables = this.makeResultTemplateVariables(data);
			let html = Utils.compileTemplate(template, variables);

			return $(html);
		},

		makeResultTemplateVariables: function(data) {
			const result = {
				'STATUS': data.status,
				'TITLE': data.title,
				'MESSAGE': data.message,
				'RESOLVE': '',
				'DESCRIPTION': '',
			};

			if (data.description) {
				result['DESCRIPTION'] = this.renderResultDescription();
				result['RESOLVE'] = this.renderResultDescriptionOpener();
			}

			if (data.fixable) {
				result['RESOLVE'] = this.renderResultFix();
			}

			return result;
		},

		renderResultFix: function() {
			let template = this.getTemplate('fix');

			return Utils.compileTemplate(template, {
				'TITLE': this.getLang('FIX'),
			});
		},

		renderResultDescription: function() {
			let template = this.getTemplate('description');

			return Utils.compileTemplate(template);
		},

		renderResultDescriptionOpener: function() {
			let template = this.getTemplate('descriptionOpener');

			return Utils.compileTemplate(template, {
				'TITLE': this.getLang('DESCRIPTION_OPEN'),
			});
		},

		showDescription: function(data) {
			let contentTemplate = this.getTemplate('dialogContent');
			let content = Utils.compileTemplate(contentTemplate, { 'CONTENT': data.description });
			let dialog = new BX.CAdminDialog({
				'title': data.title,
				'content': content,
				'draggable': true,
				'resizable': true,
				'buttons': [BX.CAdminDialog.btnClose]
			});

			dialog.Show();
		},

		getOffset: function() {
			return this._offset;
		},

		resetOffset: function() {
			this._offset = 0;
		},

		increaseOffset: function() {
			++this._offset;
		},

		getTotal: function() {
			return this.options.total;
		},

		hasNext: function() {
			return this.getOffset() < this.getTotal();
		}

	}, {
		dataName: 'uiChecker'
	});

})(BX, jQuery, window);