<?
use \Arturgolubev\Cssinliner\Unitools as UTools;

IncludeModuleLangFile(__FILE__);

Class CArturgolubevCssinliner 
{
	const MODULE_ID = 'arturgolubev.cssinliner';
	var $MODULE_ID = 'arturgolubev.cssinliner';
	
	function onBufferContent(&$bufferContent){
		if(!UTools::checkStatus() || !CModule::IncludeModule(self::MODULE_ID) || defined('LOCK_CSSINLINER')) return false;

		if(UTools::getSetting('disable') == 'Y' || UTools::getSetting('disabled_'.SITE_ID) == 'Y') return false;
		
		if(!$stop && stripos($bufferContent, '<!DOCTYPE') === false) return false;
			
		$admin_debug = (UTools::getSetting('admin_debug') == "Y");
		if($admin_debug)
		{
			$bufferContent = self::showDebug($bufferContent);
		}
		
		if(!UTools::isAdmin())
		{
			$bufferContent = self::replace($bufferContent);
		}
	}
	
	function showDebug($bufferContent){
		preg_match_all('/\<link.*\>/sU', $bufferContent, $arLinks);
		$ds = '<script>';
			$ds .= 'console.log("'.GetMessage("ARTURGOLUBEV_CSSINLINER_DEBUG_TITLE").'");';
			foreach($arLinks[0] as $link){
				if(!strstr($link, '.css'))
					continue;
				
				preg_match_all('/href=[\"\'](.*\.css).*[\"\']/sU', $link, $tmphref);
				$cssitem = $tmphref[1][0];
				if($cssitem)
				{
					$ds .= 'console.log("'.$cssitem.'");';
				}
			}
		$ds .= '</script>';
		
		$bufferContent = str_replace('</body>', $ds.'</body>', $bufferContent);
		
		return $bufferContent;
	}
	
	function baseOptimize($style, $useOptimize, $arDopSearch = array(), $arDopReplace = array()){
		$arOptiSearch = array();
		$arOptiReplace = array();

		if($useOptimize == 'Y')
		{
			$arOptiSearch[] = '/\/\*.*?\*\//si';
			$arOptiReplace[] = "";
			
			$arOptiSearch[] = '/(\s)+/s';
			$arOptiReplace[] = '\\1';
			
			$arOptiSearch[] = "/\n/";
			$arOptiReplace[] = "";
			
			$arOptiSearch[] = '/;\s+/';
			$arOptiReplace[] = ';';
			
			$arOptiSearch[] = '/:\s+/';
			$arOptiReplace[] = ':';
			
			$arOptiSearch[] = '/\s+\{\s+/';
			$arOptiReplace[] = '{';
			
			$arOptiSearch[] = '/;*\}/';
			$arOptiReplace[] = '}';
		}
		
		$mergedSearch = array_merge($arOptiSearch, $arDopSearch);
		$mergedReplace = array_merge($arOptiReplace, $arDopReplace);
		
		if(!empty($mergedSearch))
			$style = preg_replace($mergedSearch, $mergedReplace, $style);
		
		return $style;
	}
	
	function _getOuterStyle($url){
		if(extension_loaded('curl')){
			try{
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
				curl_setopt($ch, CURLOPT_HEADER, 0);
				$style = curl_exec($ch);
				curl_close($ch);
			}catch(Exception $e){
				$style = file_get_contents($url);
			}
		}else{
			$style = file_get_contents($url);
		}
		
		return trim($style);
	}
	
	function getOuterCss($url, $useOptimize = 'N', $arDopSearch = array(), $arDopReplace = array()){
		if(substr($url,0,2) == '//') $url = 'https:'.$url;
		
		$obCache = new CPHPCache();
		
		$timeSeconds = 86400;
		$cacheId = md5($url.$useOptimize.serialize($arDopSearch).serialize($arDopReplace));
		$cachePath = '/'.SITE_ID.'/arturgolubev.cssinliner/outer/';
		
		if($obCache->InitCache($timeSeconds, $cacheId, $cachePath))
		{
			$vars = $obCache->GetVars();
			$style = $vars['style'];
		}
		elseif($obCache->StartDataCache())
		{
			$style = self::_getOuterStyle($url);
			$style = self::baseOptimize($style, $useOptimize, $arDopSearch, $arDopReplace);
			
			$obCache->EndDataCache(array('style' => $style));
		}
		
		return $style;
	}
	
	function getInnerCss($url, $useOptimize = 'N', $arDopSearch = array(), $arDopReplace = array()){
		$obCache = new CPHPCache();
		
		$timeSeconds = 3600;
		$cacheId = md5($url.$useOptimize.serialize($arDopSearch).serialize($arDopReplace));
		$cachePath = '/'.SITE_ID.'/arturgolubev.cssinliner/'.basename($url);
		
		if($obCache->InitCache($timeSeconds, $cacheId, $cachePath))
		{
			$vars = $obCache->GetVars();
			$style = $vars['style'];
		}
		elseif($obCache->StartDataCache())
		{
			$style = trim(file_get_contents($_SERVER['DOCUMENT_ROOT'].$url));
			$style = self::baseOptimize($style, $useOptimize, $arDopSearch, $arDopReplace);
			$obCache->EndDataCache(array('style' => $style));
		}
		
		return $style;
	}
	
	function replace($bufferContent){
		preg_match_all('/\<link.*\>/sU', $bufferContent, $arLinks);
		if(!empty($arLinks[0]))
		{
			/* get setting */
			$use_compress = UTools::getSetting('use_compress');
			$inline_max_weight = IntVal(UTools::getSetting('inline_max_weight'));
			$google_fonts_inline = UTools::getSetting('google_fonts_inline');
			$outer_style_inline = UTools::getSetting('outer_style_inline');
			$exceptions = UTools::getSetting('exceptions');
			
			/* set default setting */
			if($inline_max_weight <= 0) $inline_max_weight = 128;
			if($exceptions) $exceptions = explode("\n",$exceptions);
			
			$arLinksWork = array();
			foreach($arLinks[0] as $link)
			{
				if(strstr($link, 'media="print"') || strstr($link, "media='print'")) continue;
				
				if(is_array($exceptions) && count($exceptions)>0){
					foreach($exceptions as $eLink){
						$eLink = trim($eLink);
						if($eLink && strstr($link, $eLink)) continue(2);
					}
				}
				
				if($google_fonts_inline == 'Y' && strstr($link, '//fonts.googleapis.com/css'))
				{					
					preg_match_all('/href=[\"\'](.*)[\"\']/sU', $link, $tmphref);
					$cssItemHref = $tmphref[1][0];
					
					if($cssItemHref)
					{
						$style = self::getOuterCss($cssItemHref, $use_compress);
						
						if($style)
							$bufferContent = str_replace($link, '<style type="text/css">'.$style.'</style>', $bufferContent);
					}
				}
				
				if(!strstr($link, '.css'))
					continue;
				
				if($outer_style_inline != 'Y')
				{
					if(strstr($link, 'http://') || strstr($link, 'https://'))
						continue;
				}
				
				$arLinksWork[] = $link;
			}
			
			if(count($arLinksWork))
			{
				$arPregUrl = array(
					'/url\s?\([\"\']?((?!\'?\"?data:image)(?!http\:)(?!https\:)[\w\.]+.*)[\"\']?\)/sU'
				);
				foreach($arLinksWork as $link){
					unset($style);
					
					preg_match_all('/href=[\"\'](.*\.css).*[\"\']/sU', $link, $tmphref);
					$cssitem = $tmphref[1][0];
					if($cssitem)
					{
						$path = dirname($cssitem).'/';
						$arPregUrlR = array("url('".$path."$1')");
						
						if(strstr($link, 'http://') || strstr($link, 'https://') || substr($cssitem, 0, 2) == '//')
						{
							$style = self::getOuterCss($cssitem, $use_compress, $arPregUrl, $arPregUrlR);
						}
						elseif(file_exists($_SERVER['DOCUMENT_ROOT'].$cssitem))
						{
							$size = (filesize($_SERVER['DOCUMENT_ROOT'].$cssitem) / 1024);
							if($size < $inline_max_weight)
							{
								$style = self::getInnerCss($cssitem, $use_compress, $arPregUrl, $arPregUrlR);
							}
						}
						
						if($style)
							$bufferContent = str_replace($link, '<style type="text/css">'.$style.'</style>', $bufferContent);
					}
				}
			}
		}
		unset($arLinks);
		unset($arLinksWork);
		
		return $bufferContent;
	}
}
?>
