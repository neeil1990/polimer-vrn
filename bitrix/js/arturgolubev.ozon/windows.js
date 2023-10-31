var agOzWindows = BX.namespace("agOzWindows");
agOzWindows.simple_info = function(title, data){
	var agoz_alert = new BX.PopupWindow("agoz_simple_alert", null, {
		overlay: {backgroundColor: 'black', opacity: '80' },
		draggable: false,
		zIndex: 1,
		closeIcon : false,
		closeByEsc : true,
		lightShadow : false,
		autoHide : false,
		className: "agoz_simple_window",
		content: data,
		titleBar: title,
		offsetTop : 1,
		offsetLeft : 0,
		buttons: [
			new BX.PopupWindowButton({
				text: BX.message("ARTURGOLUBEV_OZON_JS_OK"),
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

agOzWindows.initTools = function(){
	agOzWindows.initSpoler();
};

agOzWindows.initSpoler = function(){
	var els = document.querySelectorAll('.agoz_js_spoiler');
	if(els.length){
		els.forEach(function(item){
			item.addEventListener('click', function(){
				var t = this, b = t.nextElementSibling;
				var st = getComputedStyle(b);
				if(st.display == 'none'){
					b.style.display = 'block';
				}else{
					b.style.display = 'none';
				}
			});
		});
	}
};



agOzWindows.sleepjs = function(sec, callback){
	BX.ajax({
		method: 'POST',
		url: '/bitrix/tools/arturgolubev.ozon/ajax.php?action=sleep',
		data: {sec: sec},
		dataType: 'json',
		timeout: 10,
		async: true,
		processData: true,
		scriptsRunFirst: false,
		emulateOnload: false,
		start: true,
		cache: false,
		onsuccess: function(data){
			callback();
		},
		onfailure: function(){
			alert('Error. Contact module administrator');
		},
	});
}

agOzWindows.set_dbugarea = function (t){
	var wrap = document.querySelector('.agoz_debug_area_wrap');
	if(wrap){
		var st = getComputedStyle(wrap);
		if(st.display == 'none'){
			wrap.style.display = 'block';
		}
		var area = document.querySelector('.agoz_debug_area');
		area.innerHTML = t;
	}
}
agOzWindows.add_dbugarea = function (t){
	var wrap = document.querySelector('.agoz_debug_area_wrap');
	if(wrap){
		var st = getComputedStyle(wrap);
		if(st.display == 'none'){
			wrap.style.display = 'block';
		}
		
		var area = document.querySelector('.agoz_debug_area');
		var dv = '<div>'+t+'</div>';
		area.innerHTML = area.innerHTML + dv;
	}
}
agOzWindows.clear_dbugarea = function (){
	var wrap = document.querySelector('.agoz_debug_area_wrap');
	if(wrap){
		var st = getComputedStyle(wrap);
		if(st.display == 'none'){
			wrap.style.display = 'block';
		}
		
		var area = document.querySelector('.agoz_debug_area');
		area.innerHTML = '';
	}
}

/* agOzWindows.agoz_fast_message = function (obj, message){
	var $t = $(obj), os = $t.offset(), ow = $t.outerWidth();
	
	if($t.length < 1) return;
	
	var tm = Math.floor(Date.now() / 1000);
	
	var div = '<div class="marktime'+tm+' agoz_fastmessage" style="top: '+(os.top - 20)+'px; left: '+(os.left + ow + 5)+'px;">'+message+'</div>';
	
	$('body').append(div);
	
	setTimeout(function(){
		$('.marktime'+tm).remove();
	}, 1500);
} */