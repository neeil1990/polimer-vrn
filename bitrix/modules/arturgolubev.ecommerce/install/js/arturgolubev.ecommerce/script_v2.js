var agMetricRequestCount = 0;

BX.ready(function(){
	agMetricSetTimeout();
});

function agMetricSetTimeout(){
	// console.log('set timeout ' + agMetricRequestCount);
	
	if(agMetricRequestCount < 60)
	{
		setTimeout(function(){
			agMetricScriptRequest();
		}, 5000);
	}
	else
	{
		// console.log('maxCountSUccess');
	}
	
	agMetricRequestCount++;
}

function agMetricScriptRequest(){
	// console.log('send request');
	
	BX.ajax({   
		url: '/bitrix/tools/arturgolubev.ecommerce/getscripts_v2.php',
		data: {},
		method: 'POST',
		dataType: 'script',
		timeout: 30,
		async: true,
		processData: true,
		scriptsRunFirst: false,
		// emulateOnload: true,
		start: true,
		cache: false,
		onsuccess: function(data){
			// console.log('success');
			agMetricSetTimeout();
		},
		onfailure: function(){
			console.log('Ecommerce Metrics Error');
		}
	});
}