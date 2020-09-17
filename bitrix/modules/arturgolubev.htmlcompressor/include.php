<?
use \Arturgolubev\Htmlcompressor\Tools as Tools;
use \Arturgolubev\Htmlcompressor\Unitools as UTools;

Class CArturgolubevHtmlcompressor 
{
	const MODULE_ID = 'arturgolubev.htmlcompressor';
	var $MODULE_ID = 'arturgolubev.htmlcompressor'; 
	
	function onBufferContent(&$bufferContent){
		if(UTools::checkStatus() && CModule::IncludeModule(self::MODULE_ID))
		{			
			$stop = 0;
			
			if(UTools::getSetting('compression_off') == 'Y' || UTools::getSiteSetting('compression_off') == 'Y')
				$stop = 1;
			
			if(UTools::isAdmin() || Tools::GetUserRight() > "D")
				$stop = 1;
			
			if(!UTools::checkPageException(UTools::getSetting('page_exceptions')) || strstr(Tools::getCurPage(), '/bitrix/'))
				$stop = 1;
			
			if(!$stop && !UTools::isHtmlPage($bufferContent))
				$stop = 1;
			
			if (!$stop){
				if(UTools::getSetting('css_compress') == 'Y'){
					$bufferContent = self::compressCss($bufferContent);
				}
				
				$bufferContent = self::sanitize_output($bufferContent);
			}
		}
	}
	
	
	private function compressCss($content){
		preg_match_all('/\<link.*\>/sU', $content, $arLinks);
		if(!empty($arLinks[0]))
		{
			$arPregUrl = array(
				'/url\s?\([\"\']?((?!\'?\"?data:image)(?!http\:)(?!https\:)[\w\.]+.*)[\"\']?\)/sU'
			);
			
			$arReplace = array(
				"from" => array(),
				"to" => array(),
			);
			
			$css_unite = (UTools::getSetting('css_unite') == 'Y');
			$unite_id = '';
			$unite_ar_styles = array();
			
			foreach($arLinks[0] as $link){
				if(strstr($link, 'media="print"') || strstr($link, "media='print'") || !strstr($link, '.css')) continue;
				
				preg_match_all('/href=[\"\'](.*\.css).*[\"\']/sU', $link, $tmphref);
				$cssitem = $tmphref[1][0];
				if($cssitem){
					$path = dirname($cssitem).'/';
					$arPregUrlR = array("url('".$path."$1')");
					
					if(strstr($link, 'http://') || strstr($link, 'https://') || substr($cssitem, 0, 2) == '//'){
						continue;
					}elseif(file_exists($_SERVER['DOCUMENT_ROOT'].$cssitem)){
						$cssFileId = md5($cssitem.filemtime($_SERVER["DOCUMENT_ROOT"].$cssitem).'v1');
						
						if(!$css_unite)
						{
							$path = '/bitrix/cache/css/'.SITE_ID.'_arturgolubev.htmlcompressor/'.basename(str_replace('.css', '', $cssitem)).'/'.$cssFileId.'.css';
							$file = new \Bitrix\Main\IO\File($_SERVER["DOCUMENT_ROOT"] . $path);
							if(!$file->isExists()){
								$style = file_get_contents($_SERVER['DOCUMENT_ROOT'].$cssitem);
								$style = self::__cssOptimize($style, $arPregUrl, $arPregUrlR);
								$file->putContents($style);
							}
							
							$arReplace["from"][] = $link;
							$arReplace["to"][] = '<link href="'.$path.'" type="text/css"  rel="stylesheet" />';
						}
						else
						{
							$unite_id .= $cssFileId;
							$unite_ar_styles[] = array('css'=>$cssitem, 'ap'=>$arPregUrl, 'apr'=>$arPregUrlR);
							
							$arReplace["from"][] = $link;
							$arReplace["to"][] = '';
						}
					}
				}
			}
			
			if($css_unite){
				$unite_style = '';
				$path = '/bitrix/cache/css/'.SITE_ID.'_arturgolubev.htmlcompressor/united/'.md5($unite_id).'.css';
				$file = new \Bitrix\Main\IO\File($_SERVER["DOCUMENT_ROOT"] . $path);
				if(!$file->isExists()){
					foreach($unite_ar_styles as $item){
						$style = file_get_contents($_SERVER['DOCUMENT_ROOT'].$item['css']);						
						$unite_style .= '/* '.$item['css'].' */'.PHP_EOL;
						$unite_style .= self::__cssOptimize($style, $item["ap"], $item["apr"]). PHP_EOL;
					}
					
					$file->putContents($unite_style);
				}
				
				$arReplace["from"][] = '</head>';
				$arReplace["to"][] = '<link href="'.$path.'" type="text/css"  rel="stylesheet" />'. PHP_EOL .'</head>';
			}
			
			
			if(count($arReplace["from"]) > 0){
				$content = str_replace($arReplace["from"], $arReplace["to"], $content);
			}
		}
		
		return $content;
	}
	
	function __cssOptimize($style, $arDopSearch = array(), $arDopReplace = array()){
		$arOptiSearch = array();
		$arOptiReplace = array();

		$arOptiSearch[] = '/\/\*.*?\*\//si'; $arOptiReplace[] = "";
		$arOptiSearch[] = "/\n/"; $arOptiReplace[] = " ";
		$arOptiSearch[] = "/\t/"; $arOptiReplace[] = " ";
		$arOptiSearch[] = '/(\s)+/s'; $arOptiReplace[] = '\\1';
		$arOptiSearch[] = '/;\s+/'; $arOptiReplace[] = ';';
		$arOptiSearch[] = '/:\s+/'; $arOptiReplace[] = ':';
		$arOptiSearch[] = '/\s+\{\s+/'; $arOptiReplace[] = '{';
		$arOptiSearch[] = '/\{\s+/'; $arOptiReplace[] = '{';
		$arOptiSearch[] = '/\,\s+/'; $arOptiReplace[] = ',';
		$arOptiSearch[] = '/;*\}/'; $arOptiReplace[] = '}';
		
		$mergedSearch = array_merge($arOptiSearch, $arDopSearch);
		$mergedReplace = array_merge($arOptiReplace, $arDopReplace);
		
		if(!empty($mergedSearch))
			$style = preg_replace($mergedSearch, $mergedReplace, $style);
		
		return trim($style);
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