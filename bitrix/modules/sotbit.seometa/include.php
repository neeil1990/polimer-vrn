<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Sotbit\Seometa\ConditionTable;

if(strtolower(LANG_CHARSET) !== strtolower(mb_internal_encoding())) {
    mb_internal_encoding(LANG_CHARSET);
    mb_regex_encoding(LANG_CHARSET);
}

\Bitrix\Main\Loader::registerAutoloadClasses('sotbit.seometa',
	array(
		'CSeoMeta' => '/classes/general/seometa.php',
		'SMCondTree' => '/classes/general/seometa_cond.php',
		'CSeoMetaTags' => '/classes/general/seometa_tags.php',
		'CSeoMetaTagsProperty' => '/classes/general/seometa_tags_property.php',
		'CSeoMetaTagsPrice' => '/classes/general/seometa_tags_price.php',
		'CSeoMetaScaner' => '/classes/general/seometa_scaner.php',
		'CSeoMetaAutogenerator' => '/classes/general/seometa_autogenerator.php',
		//'CSeoMetaExcel' => '/classes/general/seometa_excel.php',
		'CSeoMetaSitemap' => '/classes/general/seometa_sitemap.php',
		'CSeoMetaEvents' => '/classes/general/seometa_event_handler.php',
		'CSeoMetaOtherEvent' => '/classes/general/seometa_event_handler.php',
		'CommonGenerator' => '/lib/generator/common.php',
	));

IncludeModuleLangFile( __FILE__ );
Loader::includeModule('catalog');

global $DB;

class CCSeoMeta
{
    const MODULE_ID = "sotbit.seometa";

    private static $DEMO = false;

    public function __construct(){
    }

    private static function setDemo()
    {
        static::$DEMO = CModule::IncludeModuleEx( self::MODULE_ID );
    }

    public function getDemo()
    {
        if(self::$DEMO === false)
            self::setDemo();
        return !(static::$DEMO == 0 || static::$DEMO == 3);
    }

	public function ReturnDemo()
	{
        if(self::$DEMO === false)
            self::setDemo();
		return static::$DEMO;
	}

	public function PropMenu($IBLOCK_ID)
	{
	    /*if(!Loader::includeModule( 'catalog' ))
	     return self::PropMenuIBlock($IBLOCK_ID);
	     */
	    
	    $return = '';
	    
	    $ProductIblock = $IBLOCK_ID;
	    $OffersIblock = $IBLOCK_ID;
	    // Find Iblocks product and offers
	    $return .= '<input type="button" value="..." id="SotbitSeoMenuButton" style="float:left;">
                    <div style="clear:both"></div>
                    <ul class="navmenu-v">';
        
	    if(Loader::includeModule( 'catalog' ))
	    {
	        $mxResult = CCatalogSKU::GetInfoByProductIBlock( $IBLOCK_ID );
	        if (is_array( $mxResult ))
	        {
	            $ProductIblock = $mxResult['PRODUCT_IBLOCK_ID'];
	            $OffersIblock = $mxResult['IBLOCK_ID'];
	        }
	        else
	        {
	            $mxResult = CCatalogSKU::GetInfoByOfferIBlock( $IBLOCK_ID );
	            if (is_array( $mxResult ))
	            {
	                $ProductIblock = $mxResult['PRODUCT_IBLOCK_ID'];
	                $OffersIblock = $mxResult['IBLOCK_ID'];
	            }
	        }
	        
	        $return .= '
                        <li>' . GetMessage( "MENU_SECTION_FIELDS" ) . '
                            <ul>
                                <li class="with-prop" data-prop="{=this.Name}">' . GetMessage( "MENU_SECTION_FIELDS_NAME" ) . '</li>
                                <li class="with-prop" data-prop="{=lower this.Name}">' . GetMessage( "MENU_SECTION_FIELDS_LOWER_NAME" ) . '</li>
                                <li class="with-prop" data-prop="{=this.Code}">' . GetMessage( "MENU_SECTION_FIELDS_CODE" ) . '</li>
                                <li class="with-prop" data-prop="{=this.PreviewText}">' . GetMessage( "MENU_SECTION_FIELDS_PREVIEW" ) . '</li>
                            </ul>
                        </li>
                        <li>' . GetMessage( "MENU_PARENT_FIELDS" ) . '
                            <ul>
                                <li class="with-prop" data-prop="{=parent.Name}">' . GetMessage( "MENU_PARENT_FIELDS_NAME" ) . '</li>
                                <li class="with-prop" data-prop="{=parent.Code}">' . GetMessage( "MENU_PARENT_FIELDS_CODE" ) . '</li>
                                <li class="with-prop" data-prop="{=parent.PreviewText}">' . GetMessage( "MENU_PARENT_FIELDS_PREVIEW" ) . '</li>
                            </ul>
                        </li>
                        <li>' . GetMessage( "MENU_IBLOCK_FIELDS" ) . '
                            <ul>
                                <li class="with-prop" data-prop="{=iblock.Name}">' . GetMessage( "MENU_IBLOCK_FIELDS_NAME" ) . '</li>
                                <li class="with-prop" data-prop="{=iblock.Name}">' . GetMessage( "MENU_IBLOCK_FIELDS_CODE" ) . '</li>
                                <li class="with-prop" data-prop="{=iblock.Name}">' . GetMessage( "MENU_IBLOCK_FIELDS_PREVIEW" ) . '</li>
                            </ul>
                        </li>
                        ';
	    }
	    
	    $return .= '<li>' . GetMessage( "MENU_PROPERTIES" ) . '
                            <ul>';
	    $rsProperty = CIBlockProperty::GetList( array (
	        'NAME' => 'asc'
	    ), array (
	        "IBLOCK_ID" => $ProductIblock
	    ), array (
	        'NAME',
	        'CODE'
	    ) );
	    while ( $property = $rsProperty->fetch() )
	    {
	        $return .= "<li class='with-prop' data-prop='{=concat {=ProductProperty \"" . $property['CODE'] . "\" } \", \"}'>" . $property['NAME'] . "</li>";
        }
        
	    $return .= '
                        </ul>
                    </li>';
	    
	    if(Loader::includeModule( 'catalog' ))
	    {
	        $return .= '
                        <li>' . GetMessage( "MENU_OFFERS_PROPERTIES" ) . '
                            <ul>';
	        $rsProperty = CIBlockProperty::GetList( array (
	            'NAME' => 'asc'
	        ), array (
	            "IBLOCK_ID" => $OffersIblock
	        ), array (
	            'NAME',
	            'CODE'
	        ) );
	        while ( $property = $rsProperty->fetch() )
	        {
	            $return .= "<li class='with-prop' data-prop='{=concat {=OfferProperty \"" . $property['CODE'] . "\" } \", \"}'>" . $property['NAME'] . "</li>";
	        }
	        $return .= '
                            </ul>
                        </li>
                        <li>' . GetMessage( "MENU_STORES" ) . '
                            <ul>';
            
            
	        $rsStore = CCatalogStore::GetList( array (
	            'NAME' => 'asc'
	        ), array (
	            'ACTIVE' => 'Y'
	        ), false, false, array (
	            'ID',
	            'TITLE'
	        ) );
	        while ( $store = $rsStore->fetch() )
	        {
	            $return .= '<li class="with-prop" data-prop="{=catalog.store.' . $store['ID'] . '.name}">' . $store['TITLE'] . '</li>';
	        }
	        $return .= '
                            </ul>
                        </li>';
            
            
	        $rsPriceType = CCatalogGroup::GetList( array (
	            "NAME" => "ASC"
	        ), array () );
	        while ( $PriceType = $rsPriceType->Fetch() )
	        {
	            $return .= "
                        <li>[" . $PriceType['NAME'] . "] " . $PriceType['NAME_LANG'] . "
                            <ul>
                                <li class=\"with-prop\" data-prop='{=Price \"MIN\" \"" . $PriceType['NAME'] . "\"}'>" . GetMessage( "MENU_PRICES_MIN" ) . "</li>
                                <li class=\"with-prop\" data-prop='{=Price \"MAX\" \"" . $PriceType['NAME'] . "\"}'>" . GetMessage( "MENU_PRICES_MAX" ) . "</li>
                                <li class=\"with-prop\" data-prop='{=Price \"MIN_FILTER\" \"" . $PriceType['NAME'] . "\"}'>" . GetMessage( "MENU_PRICES_FILTER_MIN" ) . "</li>
                                <li class=\"with-prop\" data-prop='{=Price \"MAX_FILTER\" \"" . $PriceType['NAME'] . "\"}'>" . GetMessage( "MENU_PRICES_FILTER_MAX" ) . "</li>
                            </ul>
                        </li>";
            }
            
            $return .= "
                    <li>" . GetMessage( "MENU_ADD" ) . "
                        <ul>
                            <li class='with-prop' data-prop='{=concat this.sections.name this.name \" / \"}'>" . GetMessage( "MENU_ADD_PATH" ) . "</li>
                            <li class='with-prop' data-prop='{=concat catalog.store \", \"}'>" . GetMessage( "MENU_ADD_STORES" ) . "</li>
                        </ul>
                    </li>";
        }
        
	    $return .= "
                    <li>" . GetMessage( "MENU_USER_FIELD" ) . "<ul>";
	    $rsUserFields = CUserTypeEntity::GetList( array (
	        'NAME' => 'ASC'
	    ), array () );

	    while ( $UserField = $rsUserFields->GetNext() )
	    {
	        $return .= "<li class='with-prop' data-prop='#" . $UserField['FIELD_NAME'] . "#'>[" . $UserField['ID'] . "] [" . $UserField['ENTITY_ID'] . "] " . $UserField['FIELD_NAME'] . "</li>";
	    }
	    $return .= "</ul></li>";

	    if(Loader::includeModule('sotbit.regions') && !\SotbitRegions::isDemoEnd())
	    {
	    	$tags = SotbitRegions::getTags();
	    	$str = '';
	    	foreach($tags as $tag)
	    	{
	    		$str .= "<li class='with-prop' data-prop='".SotbitRegions::genCodeVariable($tag['CODE'])."'>".$tag['NAME']."</li>";
	    	}
	    	$return .= '<li>'.Loc::getMessage('SOTBIT_REGIONS_UL').'<ul>'.$str.'</ul></li>';
	    }
	    
	    
	    $return .= "</ul>";

	    return $return;
	}

	public function PropMenuTemplate($IBLOCK_ID)
	{
	    if(!CModule::IncludeModule("catalog"))
	        return;
	    
        $return = '';
        
        $ProductIblock = $IBLOCK_ID;
        $OffersIblock = $IBLOCK_ID;

        // Find Iblocks product and offers
        $mxResult = CCatalogSKU::GetInfoByProductIBlock( $IBLOCK_ID );
        if (is_array( $mxResult ))
        {
            $ProductIblock = $mxResult['PRODUCT_IBLOCK_ID'];
            $OffersIblock = $mxResult['IBLOCK_ID'];
        }
        else
        {
            $mxResult = CCatalogSKU::GetInfoByOfferIBlock( $IBLOCK_ID );
            if (is_array( $mxResult ))
            {
                $ProductIblock = $mxResult['PRODUCT_IBLOCK_ID'];
                $OffersIblock = $mxResult['IBLOCK_ID'];
            }
        }
        
        $return .= '
            <input type="button" value="..." id="SotbitSeoMenuButton">
            <div style="clear:both"></div>
            <ul class="navmenu-v">
                <li>' . GetMessage( "MENU_SECTION_FIELDS_SECTION" ) . '
                    <ul>
                        <li class="with-prop" data-prop="#SECTION_ID#">' . GetMessage( "MENU_SECTION_FIELDS_SECTION_ID" ) . '</li>
                        <li class="with-prop" data-prop="#SECTION_CODE#">' . GetMessage( "MENU_SECTION_FIELDS_SECTION_CODE" ) . '</li>
                        <li class="with-prop" data-prop="#SECTION_CODE_PATH#">' . GetMessage( "MENU_SECTION_FIELDS_SECTION_CODE_PATH" ) . '</li>
                    </ul>
                </li>
                <li>' . GetMessage( "MENU_SECTION_FIELDS_PROP" ) . '
                    <ul>
                        <li class="with-prop" data-prop="#PROPERTY_ID#">' . GetMessage( "MENU_SECTION_FIELDS_PROPERTY_ID" ) . '</li>
                        <li class="with-prop" data-prop="#PROPERTY_CODE#">' . GetMessage( "MENU_SECTION_FIELDS_PROPERTY_CODE" ) . '</li>
                    </ul>
                </li>
                <li>' . GetMessage( "MENU_SECTION_FIELDS_PROP_VALUE" ) . '
                    <ul>
                        <li class="with-prop" data-prop="#PROPERTY_VALUE#">' . GetMessage( "MENU_SECTION_FIELDS_PROPERTY_VALUE_CODE" ) . '</li>
                    </ul>
                </li>
                <li>' . GetMessage( "MENU_SECTION_FIELDS_ELSE" ) . '
                    <ul>
                        <li class="with-prop" data-prop="#FILTER#">' . GetMessage( "MENU_SECTION_FIELDS_PROPERTY_FILTER" ) . '</li>
                        <li class="with-prop" data-prop="#PRICES#">' . GetMessage( "MENU_SECTION_FIELDS_PRICES" ) . '</li>
                    </ul>
                </li>
            </ul>
        ';

        return $return;
	}

	public function AllCombinationsOfArrayElements($array)
	{
		$col_el = count( $array );
		$col_zn = pow( 2, $col_el ) - 1;
		for($i = 1; $i <= $col_zn; $i ++)
		{
			$dlina_i_bin = decbin( $i );
			$zap_str = str_pad( $dlina_i_bin, $col_el, "0", STR_PAD_LEFT );
			$zap_dop = strrev( $zap_str );
			$dooh = array ();
			for($j = 0; $j < $col_el; $j ++)
			{
				$dooh[] = $zap_dop[$j];
			}
			$d = 0;
			$a = "";
			foreach ( $dooh as $k => $v )
			{
				if ($v == 1)
				{
					$a[] .= $array[$d];
				}
				$d ++;
			}
			$return[] = $a;
		}
		return $return;
	}
}

class DataManagerEx_SeoMeta extends Bitrix\Main\Entity\DataManager {

    public static function getList(array $parameters = array())
    {
        $module = new CCSeoMeta();
        if(!$module->getDemo())
            return new Bitrix\Main\ORM\Query\Result(parent::query(), new \Bitrix\Main\DB\ArrayResult(array()));
        else
            return parent::getList($parameters);
    }

    public static function getById($id = "")
    {
        $module = new CCSeoMeta();
        if(!$module->getDemo())
            return new \CDBResult;
        else
            return parent::getById($id);
    }

    public static function add(array $arr = array())
    {
        $module = new CCSeoMeta();
        if(!$module->getDemo())
            return new \Bitrix\Main\Entity\AddResult();
        else
            return parent::add($arr);
    }

    public static function update($id = "", array $arr = array())
    {
        $module = new CCSeoMeta();
        if(!$module->getDemo())
            return new \Bitrix\Main\Entity\UpdateResult();
        else
            return parent::update($id, $arr);
    }
}

?>