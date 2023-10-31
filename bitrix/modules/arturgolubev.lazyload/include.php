<?
use \Arturgolubev\Lazyload\Tools as Tools;
use \Arturgolubev\Lazyload\Unitools as UTools;

if (!class_exists('phpQuery') && class_exists('DOMDocument')){
	require('lib/phpQuery-onefile.php'); 
}

Class CArturgolubevLazyload 
{
	const MODULE_ID = 'arturgolubev.lazyload';
	var $MODULE_ID = 'arturgolubev.lazyload';
	
	function chechEnable(){
		return (UTools::getSetting('enable') == 'Y' && UTools::getSiteSetting('disabled') != 'Y' && class_exists('DOMDocument')) ? 1 : 0;
	}
	
	function getBackgroundClass(){
		$class = 'agll0708';
		$disable_preloader = (UTools::getSetting('disable_preloader') == "Y");
		
		if(!$disable_preloader)
		{
			$class .= ' agll0708bg';
			$class .= ' ag-'.UTools::getSetting('preloader_image', 'loading');
			$class .= ' ag-'.UTools::getSetting('preloader_image_size', 'pw64');
			
			if(UTools::getSetting('preloader_for_all', 'N') == 'Y')
				$class .= ' ag-sts';
		}
		
		return $class;
	}
	
	function onBufferContent(&$bufferContent){
		global $APPLICATION;
		
		if(UTools::checkStatus() && UTools::checkAjax() && CModule::IncludeModule(self::MODULE_ID))
		{
			$stop = 0;
			
			if(!self::chechEnable())
				$stop = 1;
			
			if(!$stop && stripos($bufferContent, '<!DOCTYPE') === false)
				$stop = 1;
			
			if(!$stop)
			{
				$cur = $APPLICATION->GetCurPage(false);
				$curParams = $APPLICATION->GetCurPageParam();
				
				$page_exceptions = UTools::getSiteSettingEx('page_exceptions');
				if($page_exceptions)
				{
					$ar_page_exceptions = explode("\n",$page_exceptions);
					foreach($ar_page_exceptions as $checkValue)
					{
						$checkValue = trim($checkValue);
						if(!$checkValue) continue;
						
						$pattern = '/^'.str_replace(array('/', '*'), array('\/', '.*'), $checkValue).'$/sU';
						
						if(preg_match($pattern, $cur) || preg_match($pattern, $curParams))
						{
							$stop = 1;
						}
					}
				}
			}
			
			if(!$stop)
			{
				$option = array(
					'default_image_path' => '/bitrix/images/arturgolubev.lazyload/pixel.gif',
					'default_iframe_path' => '/bitrix/images/arturgolubev.lazyload/pixel.html',
					'iframe' => (UTools::getSiteSetting('enable_iframe') == 'Y' ? 1 : 0),
					'auto_loading' => UTools::getSetting('auto_loading', "disable"),
					'effect_type' => UTools::getSetting('effect_type', "fadeIn"),
					'effect_speed' => UTools::getSetting('effect_speed', "500"),
					'preloading' => IntVal(UTools::getSetting('preloading', "1")),
					'selectors_setting' => UTools::getSiteSettingEx('selectors'),
					'preload_background_class' => self::getBackgroundClass(),
				);
				
				if($option['effect_type'] == 'show') $option['effect_speed'] = 0;
				if($option['preloading'] <= 0) $option['preloading'] = 1;
				
				$option['selectors'] = ($option["selectors_setting"]) ? '.agllimage, '.$option["selectors_setting"] : '.agllimage';
												
				$debug_mode = (UTools::getSetting('debug', "N") == 'Y');
				
				$arSearch = array(); $arReplace = array();
				$arSearch2 = array(); $arReplace2 = array();
				
				$doc = phpQuery::newDocumentHTML($bufferContent);
				
				$images = $doc->find($option['selectors']);
				foreach ($images as $image) {
					$pqImage = pq($image);
					
					$imageSrc = $APPLICATION->ConvertCharset($pqImage->attr('src'), "UTF-8", LANG_CHARSET);
					if($imageSrc)
					{
						$arSearch[] = array(
							"src" => $imageSrc,
							"class" => $APPLICATION->ConvertCharset($pqImage->attr('class'), "UTF-8", LANG_CHARSET),
							"id" => $APPLICATION->ConvertCharset($pqImage->attr('id'), "UTF-8", LANG_CHARSET),
						);
						
						$imageClass = $pqImage->attr('class');
						$pqImage->attr('class', $imageClass.' '.$option['preload_background_class']);
						$pqImage->attr('data-src', $imageSrc);
						$pqImage->attr('src', $option["default_image_path"]);
						
						$arReplace[] = $pqImage->htmlOuter();
					}
				}
				phpQuery::unloadDocuments();
				unset($images); unset($doc);
				
				
				if(!empty($arSearch))
				{
					preg_match_all('/\<img.*\>/sU', $bufferContent, $fullImageList);
					foreach($arSearch as $searchKey=>$searchItem)
					{
						foreach($fullImageList[0] as $imageItem)
						{
							$replace = 1;
							foreach($searchItem as $sKey=>$sItem)
							{
								if(!$sItem || !$replace) continue;
								
								if(!strstr($imageItem, $sKey."='".$sItem."'") && !strstr($imageItem, $sKey.'="'.$sItem.'"'))
									$replace = 0;
							}
							
							if($replace)
							{
								$arSearch2[] = $imageItem;
								$arReplace2[] = $arReplace[$searchKey];
							}
						}
					}
				}
				unset($fullImageList); unset($arSearch); unset($arReplace);
				
				
				
				if($option['iframe']){
					preg_match_all('/\<iframe.*\>/sU', $bufferContent, $frames);
					if(!empty($frames[0]))
					{
						// global $USER; if($USER->IsAdmin()){
							// echo '<pre>'; print_r($frames); echo '</pre>';
						// }
						
						$newSrcTemplate = 'src="'.$option["default_iframe_path"].'" data-src';
						
						foreach($frames[0] as $frame){
							if(strstr($frame, 'www.googletagmanager.com')) continue;
							
							$newFrame = $frame;
							if(strstr($frame, 'class'))
							{
								preg_match_all('/class=[\",\'](.*)[\",\']/sU', $frame, $class_match);
								if($class_match[1][0])
									$newFrame = str_replace(array($class_match[1][0], "src"), array($class_match[1][0]." agll0708", $newSrcTemplate), $newFrame);
								elseif($class_match[0][0])
									$newFrame = str_replace(array($class_match[0][0], "src"), array('class="agll0708"', $newSrcTemplate), $newFrame);
							}
							else
							{
								$newFrame = str_replace("src", 'class="agll0708" '.$newSrcTemplate, $newFrame);
							}
							
							if($frame != $newFrame)
							{
								$arSearch2[] = $frame;
								$arReplace2[] = $newFrame;
							}
						}
					}
					unset($frames); unset($class_match);
				}
				
				// echo '<pre>'; print_r($option); echo '</pre>';
				// echo '<pre>'; print_r($arSearch2); echo '</pre>';
				// echo '<pre>'; print_r($arReplace2); echo '</pre>';
				
				if(!empty($arSearch2))
					$bufferContent = str_replace($arSearch2, $arReplace2, $bufferContent);
				
				$n = "\n";
				$s = $n.'<script type="text/javascript" src="/bitrix/js/'.self::MODULE_ID.'/jq.lazy.min.js"></script>'.$n;
				
				if($option['iframe'])
					$s .= $n.'<script type="text/javascript" src="/bitrix/js/'.self::MODULE_ID.'/jquery.lazy.iframe.min.js"></script>'.$n;
				
				$s .= '<script type="text/javascript">'.$n;
					$s .= 'function initAgLazyLoad(){';
						$s .= 'var $lazyElements = $(".agll0708").not(".llInitSuccess");';
						$s .= 'if($lazyElements.length > 0){';
							if($debug_mode) $s .= ' console.log("Lazy Init Length = "+ $lazyElements.length);';
						
							$s .= '$lazyElements.lazy({';
								$s .= 'effect: "'.$option['effect_type'].'",';
								$s .= 'effectTime: '.$option['effect_speed'].',';
								$s .= 'threshold: '.$option['preloading'].',';
								$s .= 'visibleOnly: false,';
								
								if($option['auto_loading'] == 'enabled')
									$s .= 'delay: 500,';
								
								$s .= 'afterLoad: function(element) {';
									if($debug_mode) $s .= 'console.log("Lazy: image has loaded");';
									$s .= 'element.removeClass("agll0708bg").addClass("imageLoaded");';
								$s .= '},';
								if($debug_mode) $s .= 'beforeLoad: function(element) {';
									if($debug_mode) $s .= 'console.log("Lazy: start load image");';
								if($debug_mode) $s .= '},';
							$s .= '});';
							
							$s .= '$lazyElements.addClass("llInitSuccess");';
						$s .= '};';
					$s .= '};'.$n;
					
					$s .= 'function initAgChecker(){';
						$s .= 'setTimeout(function(){';
							if($debug_mode) $s .= 'console.log("Lazy: checks");';
							
							$s .= 'var noLoaded = $(".agll0708.llInitSuccess").not(".imageLoaded");';
							$s .= 'if(noLoaded.length > 0) {';
								$s .= 'var instance = noLoaded.data("plugin_lazy"); if(typeof instance == "object"){';
									if($debug_mode) $s .= 'console.log("Lazy: image pixel length = " + noLoaded.length);';
									$s .= 'instance.update();';
								$s .= '}';
							$s .= '}';
							
							$s .= 'initAgLazyLoad();';
							
							$s .= 'initAgChecker();';
						$s .= '}, 1500);';
					$s .= '};'.$n;
					
					if($debug_mode) $s .= 'console.debug("jQuery "+ (jQuery ? $().jquery : "NOT") +" loaded");';
					
					$s .= 'if(window.frameCacheVars !== undefined){';
						$s .= 'BX.addCustomEvent("onFrameDataReceived", function(json){';
							if($debug_mode) $s .= 'console.log("Lazy Init Type = composite");';
							$s .= 'initAgLazyLoad(); initAgChecker();';
						$s .= '});';
					$s .= '}else{';
						$s .= '$(function(){';
							if($debug_mode) $s .= 'console.log("Lazy Init Type = ready");';
							$s .= 'initAgLazyLoad(); initAgChecker();';
						$s .= '});';
					$s .= '}'.$n;
					
				$s .= '</script>'.$n;
				
				$bufferContent = UTools::addBodyScript($s, $bufferContent);
				
				unset($arSearch2);unset($arReplace2);
				unset($s);unset($option);
			}
		}
	}
	
	function onEpilog(){
		if(UTools::checkStatus() && UTools::checkAjax() && CModule::IncludeModule(self::MODULE_ID))
		{
			if(self::chechEnable())
			{
				$jquery = (UTools::getSiteSetting('jquery') == 'Y' || UTools::getSetting('jquery') == 'Y');
				if($jquery)
					CJSCore::Init(array("jquery"));
				
				if(UTools::getSetting('disable_preloader') != 'Y')
				{
					UTools::addCss("/bitrix/css/".self::MODULE_ID."/style.css");
				}
			}
		}
	}
}
?>