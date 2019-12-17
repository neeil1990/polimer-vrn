<?
Class CArturgolubevHtmlcompressor 
{
	const MODULE_ID = 'arturgolubev.htmlcompressor';
	var $MODULE_ID = 'arturgolubev.htmlcompressor'; 
	
	function onBufferContent(&$bufferContent){
		$statusCheck = (!defined('ADMIN_SECTION') && $_SERVER['REQUEST_METHOD'] != 'POST' && strpos($_SERVER['PHP_SELF'], BX_ROOT.'/admin') !== 0);
		if($statusCheck && CModule::IncludeModule(self::MODULE_ID))
		{
			
			global $USER, $APPLICATION;
			if(!is_object($USER)) $USER = new CUser();
			
			$stop = 0;
			$cur = $APPLICATION->GetCurPage(false);
			
			if(COption::GetOptionString(self::MODULE_ID, 'compression_off') == 'Y' || COption::GetOptionString(self::MODULE_ID, 'compression_off_'.SITE_ID) == 'Y')
				$stop = 1;
			
			if($USER->IsAdmin() || $APPLICATION->GetUserRight("fileman") > "D")
				$stop = 1;
			
			$page_exceptions = trim(COption::GetOptionString(self::MODULE_ID, 'page_exceptions'));
			if($page_exceptions)
			{
				$ar_page_exceptions = explode("\n",$page_exceptions);
				foreach($ar_page_exceptions as $checkValue)
				{
					$checkValue = trim($checkValue);
					if(!$checkValue) continue;
					
					$pattern = '/^'.str_replace(array('/', '*'), array('\/', '.*'), $checkValue).'$/sU';
					if(preg_match($pattern, $cur))
					{
						$stop = 1;
					}
				}
			}
			
			if(strstr($cur, '/bitrix/')){
				$stop = 1;
			}
			
			if(!$stop && stripos($bufferContent, '<!DOCTYPE') === false)
				$stop = 1;
			
			if (!$stop){
				$bufferContent = CArturgolubevHtmlcompressor::sanitize_output($bufferContent);
			}
		}
	}
	
	function getMainReplaceParams(){
		$search = array();
		$replace = array();
		
		$search[] = '/\>\s+\</s';
		$replace[] = '><';
		
		$search[] = '/\s+/';
		$replace[] = ' ';
		
		$hide_script_type = COption::GetOptionString(self::MODULE_ID, 'hide_script_type');
		if($hide_script_type != 'Y')
		{
			$search[] = '/ type=[\'|\"]text\/javascript[\'|\"]/sU';
			$replace[] = '';
			
			$search[] = '/ type=[\'|\"]text\/css[\'|\"]/sU';
			$replace[] = '';
		}
		
		$hide_pre = COption::GetOptionString(self::MODULE_ID, 'hide_pre');
		if($hide_pre != 'Y')
		{
			$search[] = '/\<pre\>.*\<\/pre\>/sU';
			$replace[] = '';
		}
		
		$hide_html_comment = COption::GetOptionString(self::MODULE_ID, 'hide_html_comment');
		if($hide_html_comment != 'Y' && !file_exists($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/html_pages/.enabled"))
		{
			$search[] = '/\<\!--.*--\>/sU';
			$replace[] = '';
		}
		
		return array(
			'search' => $search,
			'replace' => $replace,
		);
	}
	
	function sanitize_output($buffer)
	{
		$javascript_compression_on = COption::GetOptionString(self::MODULE_ID, 'javascript_compression_on');
		$replaceParam = self::getMainReplaceParams();
		
		$replaceParamJs = array(
			'search' => array(
				// '/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\|\'|\")\/\/.*))/',
				"/\t+/",
				"/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", 
				"/  +/", 
			),
			'replace' => array(
				// '',
				" ",
				"\n",
				" ",
			),
		);

		$blocks = preg_split('/(<\/?script[^>]*>)/', $buffer, null, PREG_SPLIT_DELIM_CAPTURE);
		$buffer = '';
		foreach($blocks as $i => $block)
		{
			if($i % 4 == 2)
			{
				if($javascript_compression_on == 'Y')
				{
					$block = preg_replace($replaceParamJs['search'], $replaceParamJs['replace'], $block);
					$block = preg_replace("/\n /", "\n", $block);
				}
				
				$buffer .= $block;
				
			}
			else
			{
				$block = preg_replace($replaceParam['search'], $replaceParam['replace'], $block);
				$buffer .= $block;
			}
		}
		
		$scriptFixReplace = array(
			'search' => array(
				'/\<script\s+\>/s',
				'/\>\s+\<script/s',
				'/\>\s+\<\/script/s', 
				'/script\>\s+\</s', 
			),
			'replace' => array(
				'<script>',
				'><script',
				'></script',
				'script><',
			),
		);
		
		$buffer = trim(preg_replace($scriptFixReplace['search'], $scriptFixReplace['replace'], $buffer));
		
		unset($blocks);
		unset($scriptFixReplace);
		unset($replaceParamJs);
		unset($replaceParam);
		unset($javascript_compression_on);
		
		return $buffer;
	}
}
?>