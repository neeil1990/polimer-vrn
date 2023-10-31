function getWarehouseIDs(sid){
	$.ajax({
		type: 'POST',
		url: '/bitrix/tools/arturgolubev.ozon/ajax.php?action=get_warehouse_ids',
		data: {sid: sid},
		dataType: 'json',
		async: true,
		success: function(data){
			var viewHtml = '';
			
			if(data.error){
				viewHtml = data.error_message;
			}else{
				for(item of data.warehouse_list){
					viewHtml = viewHtml + '<div>' + item.name + ': ID - ' + item.warehouse_id + '</div><br>';
				}
			}
			
			agOzWindows.simple_info(BX.message("ARTURGOLUBEV_OZON_WAREHOUSE_LIST_TITLE"), viewHtml);
		},
		error: function() {
			stop_process = 1;
			alert(BX.message("ARTURGOLUBEV_OZON_JS_CRITICAL_ERROR"));
		}
	});
}

function getAddCardLimit(sid){
	$.ajax({
		type: 'POST',
		url: '/bitrix/tools/arturgolubev.ozon/ajax.php?action=get_card_limit',
		data: {sid: sid},
		dataType: 'json',
		async: true,
		success: function(data){
			
			var viewHtml = '';
			
			if(data.error){
				viewHtml = data.error_message;
			}else{
				viewHtml = viewHtml + '<div>' + data.limit.remaining + ' / ' + data.limit.value + '</div><br>';
			}
			
			agOzWindows.simple_info(BX.message("ARTURGOLUBEV_OZON_CARD_LIMIT_TITLE"), viewHtml);
		},
		error: function() {
			stop_process = 1;
			alert(BX.message("ARTURGOLUBEV_OZON_JS_CRITICAL_ERROR"));
		}
	});
}

