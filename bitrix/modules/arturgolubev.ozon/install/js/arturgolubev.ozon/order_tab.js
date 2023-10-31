var agozOrderTab = BX.namespace("agozOrderTab");

agozOrderTab.init = function(sid, orderID){
	BX.agozOrderTab.getOrderInfo(sid, orderID);
	
	document.querySelector('.agoz_js_ozon_orderinfo_refresh').addEventListener('click', function(){
		BX.agozOrderTab.getOrderInfo(sid, orderID);
	});
};

agozOrderTab.getOrderInfo = function(sid, orderID){
	BX.ajax({
		method: 'POST',
		url: '/bitrix/tools/arturgolubev.ozon/ajax.php?action=get_order_tab_html',
		data: {sid: sid, oid: orderID},
		// dataType: "html",
		dataType: 'json',
		timeout: 10,
		async: true,
		processData: true,
		scriptsRunFirst: false,
		emulateOnload: false,
		start: true,
		cache: false,
		onsuccess: function(data){
			document.querySelector('#ag_oz_order_tab_content_paste').innerHTML = data.html;
		},
		onfailure: function(){
			console.log('Error. Contact module administrator');
		},
	});
};

agozOrderTab.getOrderStickers = function(orders){	
	BX.ajax({
		method: 'POST',
		url: '/bitrix/tools/arturgolubev.ozon/ajax.php?action=get_order_stickers',
		data: {orders: orders},
		// dataType: "html",
		dataType: 'json',
		timeout: 10,
		async: true,
		processData: true,
		scriptsRunFirst: false,
		emulateOnload: false,
		start: true,
		cache: false,
		onsuccess: function(data){
			// console.log(data);
			
			if(data.error){
				BX.agOzWindows.simple_info(BX.message("ARTURGOLUBEV_OZON_OTAB_STICKER_ERR_TITLE"), data.error_message);
			}else{
				if(data.file_name){
					// document.location.href = data.file_name;
					window.open(data.file_name, "_blank");
				}
			}
		},
		onfailure: function(){
			console.log('Error. Contact module administrator');
		},
	});
};

agozOrderTab.setOrderAwaitingDeliver = function(orders){	
	BX.ajax({
		method: 'POST',
		url: '/bitrix/tools/arturgolubev.ozon/ajax.php?action=set_status_awaiting_deliver',
		data: {orders: orders},
		// dataType: "html",
		dataType: 'json',
		timeout: 10,
		async: true,
		processData: true,
		scriptsRunFirst: false,
		emulateOnload: false,
		start: true,
		cache: false,
		onsuccess: function(data){
			// console.log(data);
			BX.agOzWindows.simple_info(BX.message("ARTURGOLUBEV_OZON_OTAB_STATUS_RESULT_TITLE"), data.result_html);
		},
		onfailure: function(){
			console.log('Error. Contact module administrator');
		},
	});
};

agozOrderTab.setOrderCancelledConfirm = function(orders){
	var agoz_alert = new BX.PopupWindow("agoz_cancel_confirm", null, {
		overlay: {backgroundColor: 'black', opacity: '80' },
		draggable: false,
		zIndex: 1,
		closeIcon : false,
		closeByEsc : true,
		lightShadow : false,
		autoHide : false,
		className: "agwb_simple_window",
		content: BX.message("ARTURGOLUBEV_OZON_OTAB_STATUS_CANCEL_CONFIRM_BODY"),
		titleBar: BX.message("ARTURGOLUBEV_OZON_OTAB_STATUS_CANCEL_CONFIRM_TITLE"),
		offsetTop : 1,
		offsetLeft : 0,
		buttons: [
			new BX.PopupWindowButton({
				text: BX.message("ARTURGOLUBEV_OZON_JS_NEXT_STEP"),
				className: "webform-button-link-cancel",
				events: {click: function(){
					var cancel_reason_id = document.querySelector('#agoz_cancel_reason_id').value;
					var cancel_reason_message = document.querySelector('#agoz_cancel_reason_message').value;
					
					agozOrderTab.setOrderCancelled(orders, cancel_reason_id, cancel_reason_message);
					
					this.popupWindow.close();
					this.popupWindow.destroy();
				}}
			}),
			new BX.PopupWindowButton({
				text: BX.message("ARTURGOLUBEV_OZON_JS_CANCEL"),
				className: "webform-button-link-cancel",
				events: {click: function(){
					this.popupWindow.close();
					this.popupWindow.destroy();
				}}
			})
		]
	});
	agoz_alert.show();
}

agozOrderTab.setOrderCancelled = function(orders, cancel_reason_id, cancel_reason_message){	
	BX.ajax({
		method: 'POST',
		url: '/bitrix/tools/arturgolubev.ozon/ajax.php?action=set_status_cancelled',
		data: {orders: orders, cancel_reason_id: cancel_reason_id, cancel_reason_message: cancel_reason_message},
		// dataType: "html",
		dataType: 'json',
		timeout: 10,
		async: true,
		processData: true,
		scriptsRunFirst: false,
		emulateOnload: false,
		start: true,
		cache: false,
		onsuccess: function(data){
			// console.log(data);
			BX.agOzWindows.simple_info(BX.message("ARTURGOLUBEV_OZON_OTAB_STATUS_RESULT_TITLE"), data.result_html);
		},
		onfailure: function(){
			console.log('Error. Contact module administrator');
		},
	});
};




/* move to acts */
agozOrderTab.initActs = function(sid){
	document.querySelector('#button_get_act_request').addEventListener('click', function(){
		var storeid = document.querySelector('#select_get_act_request').value;
		agozOrderTab.getActs(sid, storeid);
	});
}

agozOrderTab.getActs = function(sid, storeid){
	BX.ajax({
		method: 'POST',
		url: '/bitrix/tools/arturgolubev.ozon/ajax.php?action=get_act_request',
		data: {sid: sid, storeid: storeid},
		// dataType: "html",
		dataType: 'json',
		timeout: 10,
		async: true,
		processData: true,
		scriptsRunFirst: false,
		emulateOnload: false,
		start: true,
		cache: false,
		onsuccess: function(data){
			// console.log(data);
			
			if(data.error){
				BX.agOzWindows.simple_info(BX.message("ARTURGOLUBEV_OZON_OTAB_ACT_ERR_TITLE"), data.error_message);
			}else{
				agOzWindows.add_dbugarea(BX.message("ARTURGOLUBEV_OZON_OTAB_ACT_GET_PID_SUCCESS") + data.process_id + '. ' + BX.message("ARTURGOLUBEV_OZON_JS_WAIT"));
				agozOrderTab.checkActs(sid, data.process_id);
			}
		},
		onfailure: function(){
			alert('Error. Contact module administrator');
		},
	});
}

agozOrderTab.checkActs = function(sid, process_id){
	BX.ajax({
		method: 'POST',
		url: '/bitrix/tools/arturgolubev.ozon/ajax.php?action=get_act_check',
		data: {sid: sid, process_id: process_id},
		// dataType: "html",
		dataType: 'json',
		timeout: 10,
		async: true,
		processData: true,
		scriptsRunFirst: false,
		emulateOnload: false,
		start: true,
		cache: false,
		onsuccess: function(data){
			// console.log(data);
			
			if(data.error){
				BX.agOzWindows.simple_info(BX.message("ARTURGOLUBEV_OZON_OTAB_ACT_ERR_TITLE"), data.error_message);
			}else{
				if(data.status == 'ready'){
					agOzWindows.add_dbugarea(BX.message("ARTURGOLUBEV_OZON_OTAB_ACT_GET_PID_STATUS") + ': ' + BX.message("ARTURGOLUBEV_OZON_OTAB_ACT_PID_STATUS_R") + '. ' + BX.message("ARTURGOLUBEV_OZON_JS_WAIT"));
					agozOrderTab.saveActs(sid, process_id);
				}else{
					agOzWindows.add_dbugarea(BX.message("ARTURGOLUBEV_OZON_OTAB_ACT_GET_PID_STATUS") + ': ' + BX.message("ARTURGOLUBEV_OZON_OTAB_ACT_PID_STATUS_NR") + data.status + '. ' + BX.message("ARTURGOLUBEV_OZON_JS_WAIT"));
					
					agOzWindows.sleepjs(5, function(){
						agozOrderTab.checkActs(sid, process_id);
					});
				}
			}
		},
		onfailure: function(){
			alert('Error. Contact module administrator');
		},
	});
}

agozOrderTab.saveActs = function(sid, process_id){
	BX.ajax({
		method: 'POST',
		url: '/bitrix/tools/arturgolubev.ozon/ajax.php?action=get_act_save',
		data: {sid: sid, process_id: process_id},
		// dataType: "html",
		dataType: 'json',
		timeout: 10,
		async: true,
		processData: true,
		scriptsRunFirst: false,
		emulateOnload: false,
		start: true,
		cache: false,
		onsuccess: function(data){
			// console.log(data);
			
			if(data.error){
				BX.agOzWindows.simple_info(BX.message("ARTURGOLUBEV_OZON_OTAB_ACT_ERR_TITLE"), data.error_message);
			}else{
				agOzWindows.add_dbugarea(BX.message("ARTURGOLUBEV_OZON_OTAB_ACT_READY_SAVE")+' <a target="_blank" href="'+data.file_name+'">'+BX.message("ARTURGOLUBEV_OZON_OTAB_ACT_SAVE_BTN")+'</a>');
			}
		},
		onfailure: function(){
			alert('Error. Contact module administrator');
		},
	});
}


/* CRM */
agozOrderTab.initCrmScripts = function(orderinfo){	
	// orderinfo.sid
	orderinfo.wb_status = parseInt(orderinfo.wb_status);
	orderinfo.bx_orderid = parseInt(orderinfo.bx_orderid);
	
	BX.ready(function(){
		$('.pagetitle-container.crm-pagetitle-btn-box').append('<button class="ui-btn ui-btn-primary-dark crm-btn-agwildberries">'+BX.message("ARTURGOLUBEV_OZON_JS_CRM_ORDER_BUTTON_MAIN")+'</button>');
		agozOrderTab.initCrmWindow(orderinfo);
	});
};
	agozOrderTab.initCrmWindow = function(orderinfo){
		$('.crm-btn-agwildberries').click(function(){
			var $t = $(this);
			
			var title = BX.message("ARTURGOLUBEV_OZON_JS_CRM_ORDER_WINDOW_TITLE") + ' ' + orderinfo.oz_posting_number;
			var data = '<div>';
				// info
				data = data + '<div>'+BX.message("ARTURGOLUBEV_OZON_JS_CRM_ORDER_STATUS_NOW")+BX.message("ARTURGOLUBEV_OZON_JS_CRM_ORDER_STATUS_" + orderinfo.oz_status)+'</div><br>';
				
				// print
				if(orderinfo.oz_status == 'awaiting_deliver'){
					data = data + '<div><b>'+BX.message("ARTURGOLUBEV_OZON_JS_CRM_ORDER_STICKER_BLOCK")+'</b></div><br>';
					
					data = data + '<button class="ui-btn ui-btn-light-border crm-btn-agoz" onclick="agozOrderTab.getOrderStickers(['+orderinfo.bx_orderid+']);">'+BX.message("ARTURGOLUBEV_OZON_JS_CRM_PODBOR_PRINT")+'</button>';
					
					data = data + '<br/><br/>';
				}
				
				// status
				if(orderinfo.oz_status == 'awaiting_deliver' || orderinfo.oz_status == 'awaiting_packaging'){
					data = data + '<div><b>'+BX.message("ARTURGOLUBEV_OZON_JS_CRM_ORDER_STATUS_BLOCK")+'</b></div><br>';
				}
				
				if(orderinfo.oz_status == 'awaiting_deliver'){
					data = data + '<button class="ui-btn ui-btn-light-border crm-btn-agoz" onclick="agozOrderTab.setOrderAwaitingDeliver(['+orderinfo.bx_orderid+']);">'+BX.message("ARTURGOLUBEV_OZON_JS_CRM_ORDER_STATUS_awaiting_deliver")+'</button>';
				}
				
				if(orderinfo.oz_status == 'awaiting_deliver' || orderinfo.oz_status == 'awaiting_packaging'){
					data = data + '<button class="ui-btn ui-btn-light-border crm-btn-agoz" onclick="agozOrderTab.setOrderCancelledConfirm(['+orderinfo.bx_orderid+']);">'+BX.message("ARTURGOLUBEV_OZON_JS_CRM_ORDER_STATUS_cancelled")+'</button>';
					data = data + '<br>';
				}
				
			data = data + '</div>';
			
			var agwb_alert = new BX.PopupWindow("agwb_crm_workwindow", null, {
				overlay: {backgroundColor: 'black', opacity: '80' },
				draggable: false,
				zIndex: 1,
				closeIcon : false,
				closeByEsc : true,
				lightShadow : false,
				autoHide : false,
				className: "agwb_simple_window",
				content: data,
				titleBar: title,
				offsetTop : 1,
				offsetLeft : 0,
				buttons: [
					new BX.PopupWindowButton({
						text: BX.message("ARTURGOLUBEV_WILDBERRIES_JS_CLOSE"),
						className: "webform-button-link-cancel",
						events: {click: function(){
							this.popupWindow.close();
							this.popupWindow.destroy();
							// agwb_alert.remove();
						}}
					})
				]
			});
			agwb_alert.show();
			
		});
	};