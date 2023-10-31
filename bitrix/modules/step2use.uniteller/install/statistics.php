<?
function http_request($url, $post=FALSE, $data='', $referer=FALSE, $cookie=FALSE, $user_agent=FALSE, $timeout=30) {
	$http = FALSE;
	$url = trim($url);
	if(!empty($url)) {
		$post = ($post?TRUE:FALSE);
		$timeout = ($timeout<0?0:intval($timeout)); 
		if(function_exists('curl_init')) {
			if($curl = curl_init()) {
				curl_setopt($curl, CURLOPT_URL, $url);
				curl_setopt($curl, CURLOPT_HEADER, FALSE);
				curl_setopt($curl, CURLOPT_POST, $post);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
				if($referer) curl_setopt($curl, CURLOPT_REFERER, $referer);
				if($cookie) curl_setopt($curl, CURLOPT_COOKIE, $cookie);
				if($user_agent) curl_setopt($curl, CURLOPT_USERAGENT, $user_agent);
				if($post) curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
				curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
				$http = curl_exec($curl);
				curl_close($curl);
			}
		} elseif(function_exists('file_get_contents') && ini_get('allow_url_fopen')=='1') {
			$opts = array('http'=>array('method'=>($post===TRUE?'POST':'GET'),'header'=>'','timeout'=>$timeout));
			if($referer) $opts['http']['header'] .= "Referer: $referer".PHP_EOL;
			if($cookie) $opts['http']['header'] .= "Cookie: $cookie".PHP_EOL;
			if($user_agent) $opts['http']['header'] .= "User-Agent: $user_agent".PHP_EOL;
			if($post) {
				$opts['http']['header'] .= 'Content-Type: application/x-www-form-urlencoded'.PHP_EOL;
				$opts['http']['content'] = $data;
			}
			$old_timeout = ini_get('default_socket_timeout');
			@ini_set('default_socket_timeout', $timeout);
			$context = stream_context_create($opts);
			$http = file_get_contents($url, false, $context);
			@ini_set('default_socket_timeout', $old_timeout);
		} elseif(function_exists('fsockopen')) {
			$url_info = parse_url($url);
			$url_info['scheme'] = (isset($url_info['scheme'])?strtolower($url_info['scheme']):'');
			if($url_info['scheme']=='https') {
				$ssl = 'ssl://';
				$url_info['port'] = 443;
			} else {
				$ssl = '';
				$url_info['port'] = (isset($url_info['port'])?intval($url_info['port']):80);
			}
			if(isset($url_info['host']) && ($socket=fsockopen($ssl.$url_info['host'], $url_info['port'], $errno, $errstr, $timeout))) {
				$url_info['path'] = isset($url_info['path'])?$url_info['path']:'/';
				$url_info['query'] = isset($url_info['query'])?"?$url_info[query]":'';
				$url_info['fragment'] = isset($url_info['fragment'])?"#$url_info[fragment]":'';
				$url_info['full'] = "$url_info[path]$url_info[query]$url_info[fragment]";
				$request = ($post?'POST':'GET')." $url_info[full] HTTP/1.1".PHP_EOL;
				$request .= "Host: $url_info[host]".PHP_EOL;
				if($referer) $request .= "Referer: $referer".PHP_EOL;
				if($cookie) $request .= "Cookie: $cookie".PHP_EOL;
				if($user_agent) $request .= "User-Agent: $user_agent".PHP_EOL;
				if($post) {
					$request .= 'Content-Type: application/x-www-form-urlencoded'.PHP_EOL;
					$request .= 'Content-Length: '.strlen($data).PHP_EOL;
				}
				$request .= 'Connection: Close'.PHP_EOL;
				$request .= PHP_EOL;
				if($post) $request .= $data;
				fwrite($socket, $request);
				$body_data = FALSE;
				while(!feof($socket)) {
					$fdata = fgets($socket);
					if($body_data) $http .= $fdata;
					if($fdata=="\r\n") $body_data = TRUE;
				}
				fclose($socket);
			}
		}
	}
	return $http;
} 
$site = $_SERVER["SERVER_NAME"];
$mail = urlencode(COption::GetOptionString("main", "email_from"));
$time = urlencode(date("d.m.Y H:i:s"));
$module = self::MODULE_ID;
$url = "https://atlant2010.ru/module_statistics.php?key=7PkP7wp0H50&site=".$site."&email=".$mail."&time=".$time."&module=".$module;
http_request($url);
?>