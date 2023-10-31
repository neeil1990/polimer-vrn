(function() {
	JSeometaScaner = function()
	{
		this.actionUrl = '/bitrix/admin/sotbit.seometa_webmaster_list.php?lang=' + BX.message('LANGUAGE_ID');
		this.started = false;

		BX.ready(BX.delegate(this.onScanComplete, this));
	}

	JSeometaScaner.prototype.isStarted = function() {
		return this.started;
	}

	JSeometaScaner.prototype.initializeScaning = function() {
		this.results = [];
		this.started = true;
		this.setProgress(0);
	}

	JSeometaScaner.prototype.onScanStart = function() {
		BX.show(BX('status_bar'));
		BX.hide(BX('start_button'));
		BX.hide(BX('first_start'));
	}

	JSeometaScaner.prototype.onScanComplete = function() {
		BX.show(BX('start_container'));
		BX.show(BX('start_button'));
		BX.show(BX('first_start'));
		BX.hide(BX('status_bar'));
	}

	JSeometaScaner.prototype.setProgress = function(pProgress) {
		BX('progress_text').innerHTML = pProgress + '%';
		BX('progress_bar_inner').style.width = pProgress + '%';
	}

	JSeometaScaner.prototype.sendScanRequest = function(pAction, pData, pSuccessCallback, pFailureCallback) {
		var action = pAction || 'scan';
		var data = pData || {};
		var successCallback = pSuccessCallback || BX.delegate(this.processScaningResults, this);
		var failureCallback = pFailureCallback || function(){alert(BX.message('SEO_META_FINISH_ERROR_WAIT'));};
		data['action'] = action;
		data['sessid'] = BX.bitrix_sessid();
		data = BX.ajax.prepareData(data);

		return BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.actionUrl,
			data:  data,
			onsuccess: successCallback,
			onfailure: failureCallback
		});
	}

	JSeometaScaner.prototype.startStop = function() {
		if (this.isStarted()) {
			this.started = false;
			this.onScanComplete();
		} else {
			this.initializeScaning();
			this.sendScanRequest();
			this.onScanStart();
		}
	}

	JSeometaScaner.prototype.completeScaning = function() {
		this.onScanComplete();
		this.started = false;
		location.reload();
	}

	JSeometaScaner.prototype.onRequestFailure = function(pReason) {
		this.onScanComplete();
		this.started = false;
	}

	JSeometaScaner.prototype.processScaningResults = function(pResponce) {
		if(!this.isStarted()) {
			return;
		}

		if(pResponce == 'ok' || pResponce == 'error') {
			return;
		}

		if(pResponce['all_done'] == 'Y') {
			BX('first_start').innerHTML = BX.message('SEO_META_FINISH_SCAN');
			this.completeScaning();
		} else {
			this.sendScanRequest('scan', {lastID: pResponce['last']});
		}

		if(pResponce['percent']) {
			this.setProgress(pResponce['percent']);
		}
	}
})();