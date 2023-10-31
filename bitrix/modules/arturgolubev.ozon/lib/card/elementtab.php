<?
namespace Arturgolubev\Ozon\Card;

use \Bitrix\Main\Loader;
use \Bitrix\Main\Localization\Loc;

use \Arturgolubev\Ozon\Card\Tabhelper;
use \Arturgolubev\Ozon\Tools as Helper;
use \Arturgolubev\Ozon\Unitools as UTools;

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/arturgolubev.ozon/element_tab.php");

class ElementTab {
	const MODULE_ID = 'arturgolubev.ozon';
	
	const TABSET_ID = 'tab_arturgolubev_ozon';
	const TAB_RESULT = 'tab_arturgolubev_ozon_result';
	
	// base
    protected static $_instance = null;
    public static function getInstance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
 
        return self::$_instance;
    }
	
    static function onInit($params) {
		if(Loader::includeModule(self::MODULE_ID) && $params["ID"] && $_REQUEST["action"] != 'copy'){
			$showTab = 0;
			
			$rsSites = \CSite::GetList($by="sort", $order="asc", Array());
			while($arRes = $rsSites->Fetch()){
				$sett = Helper::getCatalogSettings($arRes["ID"]);
				if(is_array($sett["IBLOCKS"])){
					if(in_array($params["IBLOCK"]["ID"], array_keys($sett["IBLOCKS"]))){
						$showTab = 1;
					}
				}
			}
			
			if($showTab){
				return array(
					"TABSET" => self::TABSET_ID,
					"GetTabs" => array(__CLASS__, "tabs"),
					"ShowTab" => array(__CLASS__, "showtab"),
					"Action" => array(__CLASS__, "action"),
					"Check" => array(__CLASS__, "check"),
				);
			}
		}
    }
 
    static function action($params) {
        return true;
    }
 
    static function check($params) {
        return true;
    }
 
    static function tabs($arArgs) {
        return array(
            array(
                "DIV" => self::TAB_RESULT,
                "TAB" => Loc::getMessage("ARTURGOLUBEV_OZON_TAB_NAME"),
                "ICON" => "sale",
                "TITLE" => Loc::getMessage("ARTURGOLUBEV_OZON_TAB_TITLE"),
                "SORT" => 9999
            )
        );
    }
	
	
    static function showtab($divName, $params, $bVarsFromForm) {
        if ($divName == self::TAB_RESULT){
		   ?>
				<tr><td>
					<?self::addBaseStructure();?>
				</td></tr>
			<?
        }
    }
	
	
	static function addBaseStructure(){
		\CJSCore::Init(array("ag_ozon_card"));
	
		$iblock = $_GET['IBLOCK_ID'];
		$element = $_GET['ID'];
		
		$arActiveSites = array();
		$rsSites = \CSite::GetList($by="sort", $order="asc", Array());
		while($arRes = $rsSites->Fetch()){
			$sett = Helper::getCatalogSettings($arRes["ID"]);
			if(is_array($sett["IBLOCKS"])){
				if(in_array($iblock, array_keys($sett["IBLOCKS"]))){
					$arActiveSites[] = $arRes;
				}
			}
		}
		
		?>
		
			<?
			foreach($arActiveSites as $arSite){
				self::addSiteTabStructure($arSite, $iblock, $element);
			}
			?>
			
		
			<?/* <table border="0" cellpadding="0" cellspacing="0" width="100%" class="" id="agwb_proptable_system"></table>
			
			<div style="background: #ecec7a; padding: 4px 10px;"><?=Loc::getMessage("ARTURGOLUBEV_WILDBERRIES_TAB_API_RENEW")?></div><br/><br/>
			
			<div id="agwb_card_workarea" data-eid="<?=htmlspecialcharsbx($_GET["ID"])?>" data-ibid="<?=htmlspecialcharsbx($_GET["IBLOCK_ID"])?>">
				<?=Loc::getMessage("ARTURGOLUBEV_WILDBERRIES_JS_LOADING")?> <i class="fa fa-spinner fa-pulse fa-3x fa-fw" style="font-size: 16px;"></i>
			</div>
				
			<div class="agwb_debug_area_wrap" style="display: none;">
				<div class="agwb_debug_area_title"><?=Loc::getMessage("ARTURGOLUBEV_WILDBERRIES_DEBUG_LOG_TITLE")?></div>
				
				<div id="agwb_debug_area"></div>
			</div>
			
			<div id="after_error_note" class="note-unsable-work"></div>
			
			<script src="/bitrix/js/arturgolubev.wildberries/jquery-ui/jquery-ui.min.js"></script>
			
			<script>
				<?if($create):?>
					var cardType = 'gen_card_tabhtml';
				<?else:?>
					var cardType = 'gen_template_html';
				<?endif;?>
				
				BX.ready(function(){
					BX.agwbCreateCard.refreshHtml();
				});
			</script> */?>
			
			<script>
				BX.ready(function(){
					BX.agozCard.init();
				});
			</script>
		<?
	}
		static function addSiteTabStructure($arSite, $iblock, $element){
			$ozonID = Tabhelper::getElementOzonID($arSite["ID"], $iblock, $element);	
			$integrationName = UTools::getSetting($arSite["ID"].'_admin_name');
			?>
				<?if($integrationName):?>
					<div class="agoz_tab_title"><?=Loc::getMessage("ARTURGOLUBEV_OZON_TAB_SITE_TABNAME2", array('#sname#' => $integrationName))?></div>
				<?else:?>
					<div class="agoz_tab_title"><?=Loc::getMessage("ARTURGOLUBEV_OZON_TAB_SITE_TABNAME", array('#sname#' => $arSite["ID"]))?></div>
				<?endif;?>
				
				<?if($ozonID):?>
					<div class="agoz_tab_net_info"><?=Loc::getMessage('ARTURGOLUBEV_OZON_TAB_NET_FOUND', array('#ozonid#' => $ozonID))?></div>
					
					<div class="agoz_tab_net_info">
						<?if(Tabhelper::checkElementExportStatus($arSite["ID"], $iblock, $element)):?>
							<?=Loc::getMessage("ARTURGOLUBEV_OZON_TAB_PRODUCT_EXPORTED")?>
						<?else:?>
							<?=Loc::getMessage("ARTURGOLUBEV_OZON_TAB_PRODUCT_NOT_EXPORTED")?>
						<?endif;?>
					</div>
					
					<div class="agoz_tab_buttons">
						<span class="adm-btn" id="agoz_tab_button_getprices" data-sid="<?=$arSite["ID"]?>" data-element="<?=$element?>" data-iblock="<?=$iblock?>" data-ozonid="<?=$ozonID?>"><?=Loc::getMessage("ARTURGOLUBEV_OZON_TAB_BUTTON_GET_PRICETABLE")?></span>
					</div>
					
					
					<div class="agoz_tab_buttons_result agoz_tab_buttons_result_<?=$arSite["ID"]?>"></div>
				<?else:?>
					<div class=""><?=Loc::getMessage('ARTURGOLUBEV_OZON_TAB_NET_NOTFOUND')?></div>
				<?endif;?>
			<?
		}
}