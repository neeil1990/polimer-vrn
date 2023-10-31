var agozCard = BX.namespace("agozCard");

agozCard.init = function(){
	var bGP = document.querySelectorAll('#agoz_tab_button_getprices');
	if(bGP.length){
		bGP.forEach(function(item){
			item.addEventListener('click', agoz_get_pricetable);
		});
	}
};


/* click event */
function agoz_get_pricetable(){
	var ds = this.dataset;
	
	BX.ajax({
		method: 'POST',
		url: '/bitrix/tools/arturgolubev.ozon/ajax.php?action=get_price_table',
		data: {eid: this.dataset.element, sid: this.dataset.sid, ibid: this.dataset.iblock, ozonid: this.dataset.ozonid},
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
			if(data.error){
				document.querySelector('.agoz_tab_buttons_result_'+ds.sid).innerHTML = data.error_message;
			}else{
				document.querySelector('.agoz_tab_buttons_result_'+ds.sid).innerHTML = data.html;
			}
		},
		onfailure: function(){
			agOzWindows.simple_info(BX.message("ARTURGOLUBEV_OZON_JS_ERRORT"), BX.message("ARTURGOLUBEV_OZON_JS_CRITICAL_ERROR"));
		},
	});
}