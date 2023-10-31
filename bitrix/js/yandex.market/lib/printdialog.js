(function(BX, window) {

	var YandexMarket = BX.namespace('YandexMarket');

	// constructor

	YandexMarket.PrintDialog = function(arParams) {
		YandexMarket.PrintDialog.superclass.constructor.apply(this, arguments);
	};

	BX.extend(YandexMarket.PrintDialog, YandexMarket.Dialog);

	// buttons

	YandexMarket.PrintDialog.prototype.btnSave = YandexMarket.PrintDialog.btnSave = {
		title: BX.message('YANDEX_MARKET_PRINT_DIALOG_SUBMIT'),
		id: 'savebtn',
		name: 'savebtn',
		className: 'adm-btn-save yamarket-dialog-btn',
		action: function () {
			this.disableUntilError();
			this.parentWindow.Submit();
		}
	};

	YandexMarket.PrintDialog.btnCancel = YandexMarket.PrintDialog.superclass.btnCancel;
	YandexMarket.PrintDialog.btnClose = YandexMarket.PrintDialog.superclass.btnClose;

	// submit

	Object.assign(YandexMarket.PrintDialog.prototype, {

		Submit: function() {
			const form = this.GetForm();
			const formData = new FormData(form);

			formData.append('ajax', 'Y');

			fetch(form.getAttribute('action'), {
				method: form.getAttribute('method') || 'POST',
				body: formData,
			})
				.then($.proxy(this.SubmitParse, this))
				.then($.proxy(this.SubmitEnd, this))
				.catch($.proxy(this.SubmitStop, this));
		},

		SubmitStop: function(error) {
			this.ShowError(error.message);
			BX.closeWait();
		},

		SubmitParse: function(response) {
			if (!response.ok) {
				throw new Error("HTTP error, status = " + response.status);
			}

			const contentType = (response.headers.get('Content-Type') || '').toLowerCase();

			if (contentType.indexOf('text/html') !== -1) {
				return response.arrayBuffer()
					.then((buffer) => {
						const encoding = /charset=(.*)$/.exec(contentType);
						const decoder = new TextDecoder(encoding[1]);

						return {
							type: contentType,
							content: decoder.decode(buffer),
						};
					});
			} else if (contentType.indexOf('application/json') !== -1) {
				return response.json();
			} else {
				return response.arrayBuffer().then((buffer) => {
					return {
						type: contentType,
						content: buffer,
					};
				});
			}
		},

		SubmitEnd: function(data) {
			if (data.error != null) {
				throw new Error(data.error);
			}

			if (data.type.indexOf('text/html') !== -1) {
				this.drawHtml(data.content);
			} else {
				this.drawFile(data.content, data.type);
			}

			this.Close();
			BX.closeWait();
		},

		drawHtml: function(html) {
			const newWindow = window.open(this.whiteboardUrl());

			if (newWindow == null) {
				throw new Error(BX.message('YANDEX_MARKET_PRINT_DIALOG_WINDOW_BLOCKED'));
			}

			newWindow.document.write(html);
		},

		whiteboardUrl: function() {
			const form = this.GetForm();
			const url = form.getAttribute('action') || '';

			return (
				url
				+ (url.indexOf('?') === -1 ? '?' : '&')
				+ '&view=whiteboard'
			);
		},

		drawFile: function(content, format) {
			const blob = new Blob([content], {type : format});
			const url = window.URL.createObjectURL(blob);
			const newWindow = window.open(url);

			if (newWindow == null) {
				throw new Error(BX.message('YANDEX_MARKET_PRINT_DIALOG_WINDOW_BLOCKED'));
			}
		}
		
	});

})(BX, window);