$(window).load = function()
{
	var xhr = new XMLHttpRequest();
	//xhr.open("POST", '/bitrix/components/sotbit/seo.meta/statistics.php');
	xhr.open("POST", '/bitrix/tools/sotbit.seometa/statistics.php');
	xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	xhr.send('from='+document.referrer+'&to='+window.location.href);
};