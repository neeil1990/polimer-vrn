<?
use \Arturgolubev\Htmlcompressor\Unitools as UTools;

Class CArturgolubevHtmlcompressor 
{
	const MODULE_ID = 'arturgolubev.htmlcompressor';
	var $MODULE_ID = 'arturgolubev.htmlcompressor'; 
	
	function onBufferContent(&$bufferContent){
		if(UTools::checkStatus() && CModule::IncludeModule(self::MODULE_ID))
		{
			global $APPLICATION;
			
			$stop = 0;
			$cur = $APPLICATION->GetCurPage(false);
			
			if(UTools::getSetting('compression_off') == 'Y' || UTools::getSiteSetting('compression_off') == 'Y')
				$stop = 1;
			
			if(UTools::isAdmin() || $APPLICATION->GetUserRight("fileman") > "D")
				$stop = 1;
			
			if(!UTools::checkPageException(UTools::getSetting('page_exceptions')) || strstr($cur, '/bitrix/'))
				$stop = 1;
			
			if(!$stop && stripos(substr($bufferContent,0,512), '<!DOCTYPE') === false)
				$stop = 1;
			
			if (!$stop){
				$bufferContent = self::sanitize_output($bufferContent);
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
		
		$hide_script_type = UTools::getSetting('hide_script_type');
		if($hide_script_type != 'Y')
		{
			$search[] = '/ type=[\'|\"]text\/javascript[\'|\"]/sU';
			$replace[] = '';
			
			$search[] = '/ type=[\'|\"]text\/css[\'|\"]/sU';
			$replace[] = '';
		}
		
		$hide_pre = UTools::getSetting('hide_pre');
		if($hide_pre != 'Y')
		{
			$search[] = '/\<pre\>.*\<\/pre\>/sU';
			$replace[] = '';
		}
		
		$hide_html_comment = UTools::getSetting('hide_html_comment');
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
		$javascript_compression_on = UTools::getSetting('javascript_compression_on');
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