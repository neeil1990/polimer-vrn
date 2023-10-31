<?

use Bitrix\Main\DB\ArrayResult;
use Bitrix\Main\Entity\AddResult;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\UpdateResult;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Query\Result;

if (mb_strtolower(LANG_CHARSET) !== mb_strtolower(mb_internal_encoding())) {
    mb_internal_encoding(LANG_CHARSET);
    mb_regex_encoding(LANG_CHARSET);
}

Loader::registerAutoloadClasses('sotbit.seometa',
    [
        'CSeoMeta' => '/classes/general/seometa.php',
        'SMCondTree' => '/classes/general/seometa_cond.php',
        'CSeoMetaTags' => '/classes/general/seometa_tags.php',
        'CSeoMetaTagsProperty' => '/classes/general/seometa_tags_property.php',
        'prop_list' => '/classes/general/seometa_tags_property.php',
        'first_upper' => '/classes/general/seometa_tags_property.php',
        'nonfirst' => '/classes/general/seometa_tags_property.php',
        'iffilled' => '/classes/general/seometa_tags_property.php',
        'CSeoMetaTagsPrice' => '/classes/general/seometa_tags_price.php',
        'CSeoMetaScaner' => '/classes/general/seometa_scaner.php',
        'CSeoMetaAutogenerator' => '/classes/general/seometa_autogenerator.php',
        'CSeoMetaSitemap' => '/classes/general/seometa_sitemap.php',
        'CSeoMetaSitemapLight' => '/classes/general/seometa_sitemap_light.php',
        'CSeoMetaEvents' => '/classes/general/seometa_event_handler.php',
        'CSeoMetaOtherEvent' => '/classes/general/seometa_event_handler.php',
    ]);

IncludeModuleLangFile(__FILE__);
Loader::includeModule('catalog');

global $DB;

class CCSeoMeta
{
    const MODULE_ID = "sotbit.seometa";

    private static $DEMO = false;

    public function __construct(
    ) {
    }

    private static function setDemo(
    ) {
        static::$DEMO = CModule::IncludeModuleEx(self::MODULE_ID);
    }

    public function getDemo(
    ) {
        if (self::$DEMO === false) {
            self::setDemo();
        }
        return !(static::$DEMO == 0 || static::$DEMO == 3);
    }

    public static function ReturnDemo(
    ) {
        if (self::$DEMO === false) {
            self::setDemo();
        }
        return static::$DEMO;
    }

    public static function PropMenu(
        $IBLOCK_ID
    ) {
        $return = '';
        $ProductIblock = $IBLOCK_ID;
        $OffersIblock = $IBLOCK_ID;

        // Find Iblocks product and offers
        $return .= '<input type="button" value="..." class="sotbit-seo-menu-button-custom" style="float:left;">
                    <div style="clear:both"></div>
                    <div class="navmenu-v metainform">';

        if (Loader::includeModule('catalog') && class_exists('CCatalogSku')) {
            $mxResult = CCatalogSKU::GetInfoByProductIBlock($IBLOCK_ID);
            if(!is_array($mxResult)) {
                $mxResult = CCatalogSKU::GetInfoByOfferIBlock($IBLOCK_ID);
            }

            if(is_array($mxResult)) {
                $ProductIblock = $mxResult['PRODUCT_IBLOCK_ID'];
                $OffersIblock = $mxResult['IBLOCK_ID'];
            }

            $return .= '
                        <div class="with-prop">' . GetMessage("MENU_SECTION_FIELDS") . '
                            <div class="metainform__item">
                                <div class="metainform__props-container">
                                    <div class="with-prop" data-prop="{=this.Name}">' . GetMessage("MENU_SECTION_FIELDS_NAME") . '</div>
                                    <div class="with-prop" data-prop="{=lower this.Name}">' . GetMessage("MENU_SECTION_FIELDS_LOWER_NAME") . '</div>
                                    <div class="with-prop" data-prop="{=this.Code}">' . GetMessage("MENU_SECTION_FIELDS_CODE") . '</div>
                                    <div class="with-prop" data-prop="{=this.PreviewText}">' . GetMessage("MENU_SECTION_FIELDS_PREVIEW") . '</div>
                                 </div>
                            </div>
                        </div>
                        <div class="with-prop">' . GetMessage("MENU_PARENT_FIELDS") . '
                            <div class="metainform__item">
                                <div class="metainform__props-container">
                                    <div class="with-prop" data-prop="{=parent.Name}">' . GetMessage("MENU_PARENT_FIELDS_NAME") . '</div>
                                    <div class="with-prop" data-prop="{=parent.Code}">' . GetMessage("MENU_PARENT_FIELDS_CODE") . '</div>
                                    <div class="with-prop" data-prop="{=parent.PreviewText}">' . GetMessage("MENU_PARENT_FIELDS_PREVIEW") . '</div>
                                </div>
                             </div>
                        </div>
                        <div class="with-prop">' . GetMessage("MENU_IBLOCK_FIELDS") . '
                            <div class="metainform__item">
                                <div class="metainform__props-container">
                                    <div class="with-prop" data-prop="{=iblock.Name}">' . GetMessage("MENU_IBLOCK_FIELDS_NAME") . '</div>
                                    <div class="with-prop" data-prop="{=iblock.Code}">' . GetMessage("MENU_IBLOCK_FIELDS_CODE") . '</div>
                                    <div class="with-prop" data-prop="{=iblock.PreviewText}">' . GetMessage("MENU_IBLOCK_FIELDS_PREVIEW") . '</div>
                                </div>
                            </div>
                        </div>
                        ';
        }

        $rsProperty = CIBlockProperty::GetList(
            [
                'NAME' => 'asc'
            ],
            [
                "IBLOCK_ID" => $ProductIblock
            ],
            [
                'NAME',
                'CODE'
            ]);

        $arrProperty = [];
        while ($property = $rsProperty->fetch()) {
            $arrProperty[] = $property;
        }

        $return .= '<div class="with-prop">' . GetMessage("MENU_PROPERTIES") . '
                            <div class="metainform__item">';

        if (count($arrProperty) > 10) {
            $return .= '<div class="metainform__search-props">' .
                '<input class="metainform__search-input" type="text">' .
                '</div>';
        }

        $return .= '<div class="metainform__props-container">';

        foreach ($arrProperty as $property) {
            $return .= "<div class='with-prop' data-prop='{=concat {=ProductProperty \"" . $property['CODE'] . "\" } \", \"}'>" . htmlentities($property['NAME']) . "</div>";
        }

        $return .= '
                            </div>
                        </div>
                    </div>';

        if (Loader::includeModule('catalog')) {
            $rsProperty = CIBlockProperty::GetList(
                [
                    'NAME' => 'asc'
                ],
                [
                    "IBLOCK_ID" => $OffersIblock
                ],
                [
                    'NAME',
                    'CODE'
                ]
            );

            $arrProperty = [];
            while ($property = $rsProperty->fetch()) {
                $arrProperty[] = $property;
            }

            $return .= '<div class="with-prop">' . GetMessage("MENU_OFFERS_PROPERTIES");
            $return .= '<div class="metainform__item">';
            if (count($arrProperty) > 10) {
                $return .= '<div class="metainform__search-props">' .
                    '<input class="metainform__search-input" type="text">' .
                    '</div>';
            }
            $return .= '<div class="metainform__props-container">';

            foreach ($arrProperty as $property) {
                $return .= "<div class='with-prop' data-prop='{=concat {=OfferProperty \"" . $property['CODE'] . "\" } \", \"}'>" . htmlentities($property['NAME']) . "</div>";
            }

            $return .= '
                                </div>
                            </div>
                        </div>
                        <div class="with-prop">' . GetMessage("MENU_STORES") . '
                            <div class="metainform__item"><div class="metainform__props-container">';


            $rsStore = CCatalogStore::GetList(
                [
                    'NAME' => 'asc'
                ],
                [
                    'ACTIVE' => 'Y'
                ],
                false,
                false,
                [
                    'ID',
                    'TITLE'
                ]
            );
            while ($store = $rsStore->fetch()) {
                $return .= '<div class="with-prop" data-prop="{=catalog.store.' . $store['ID'] . '.name}">' . htmlentities($store['TITLE']) . '</div>';
            }
            $return .= '
                            </div>
                            </div>
                        </div>';


            $rsPriceType = CCatalogGroup::GetList(
                [
                    "NAME" => "ASC"
                ],
                []
            );
            while ($PriceType = $rsPriceType->Fetch()) {
                $return .= "
                        <div class='with-prop'>[" . $PriceType['NAME'] . "] " . $PriceType['NAME_LANG'] . "
                            <div class='metainform__item'>
                            <div class='metainform__props-container'>
                                <div class='with-prop' data-prop='{=Price \"MIN\" \"" . $PriceType['NAME'] . "\"}'>" . GetMessage("MENU_PRICES_MIN") . "</div>
                                <div class='with-prop' data-prop='{=Price \"MAX\" \"" . $PriceType['NAME'] . "\"}'>" . GetMessage("MENU_PRICES_MAX") . "</div>
                                <div class='with-prop' data-prop='{=Price \"MIN_FILTER\" \"" . $PriceType['NAME'] . "\"}'>" . GetMessage("MENU_PRICES_FILTER_MIN") . "</div>
                                <div class='with-prop' data-prop='{=Price \"MAX_FILTER\" \"" . $PriceType['NAME'] . "\"}'>" . GetMessage("MENU_PRICES_FILTER_MAX") . "</div>
                            </div>
                            </div>
                        </div>";
            }

            $return .= "
                    <div class='with-prop'>" . GetMessage("MENU_ADD") . "
                        <div class='metainform__item'>
                        <div class='metainform__props-container'>
                            <div class='with-prop' data-prop='{=concat this.sections.name this.name \" / \"}'>" . GetMessage("MENU_ADD_PATH") . "</div>
                            <div class='with-prop' data-prop='{=concat catalog.store \", \"}'>" . GetMessage("MENU_ADD_STORES") . "</div>
                        </div>
                        </div>
                    </div>";
        }

        $rsUserFields = CUserTypeEntity::GetList(
            [
                'NAME' => 'ASC'
            ],
            [
                "ENTITY_ID" => "IBLOCK_".$ProductIblock."_SECTION",
            ]
        );

        $arUserField = [];
        while ($property = $rsUserFields->fetch()) {
            $arUserField[] = $property;
        }
        if($arUserField){
            $return .= "<div class='with-prop'>" . GetMessage("MENU_USER_FIELD") . "<div class='metainform__item'>";
            if (count($arUserField) > 10) {
                $return .= '<div class="metainform__search-props">' .
                    '<input class="metainform__search-input" type="text">' .
                    '</div>';
            }

            $return .= '<div class="metainform__props-container">';
            foreach ($arUserField as $UserField) {
                $return .= "<div class='with-prop' data-prop='#" . $UserField['FIELD_NAME'] . "#'>[" . $UserField['ID'] . "] [" . $UserField['ENTITY_ID'] . "] " . $UserField['FIELD_NAME'] . "</div>";
            }

            $return .= "</div></div></div>";
        }

        if (Loader::includeModule('sotbit.regions') && !\SotbitRegions::isDemoEnd()) {
            $tags = SotbitRegions::getTags();
            $str = '';
            foreach ($tags as $tag) {
                $str .= "<div class='with-prop' data-prop='" . SotbitRegions::genCodeVariable($tag['CODE']) . "'>" . $tag['NAME'] . "</div>";
            }
            $return .= '<div class="with-prop">' . Loc::getMessage('SOTBIT_REGIONS_UL') . '<div class="metainform__item"><div class="metainform__props-container">' . $str . '</div></div></div>';
        }

        $return .= "</div>";

        return $return;
    }

    public static function PropMenuTemplate(
        $IBLOCK_ID
    ) {
        $return = '';

        if (!CModule::IncludeModule("catalog")) {
            return $return;
        }

        $return .= '
            <input type="button" value="..." class="sotbit-seo-menu-button">
            <div style="clear:both"></div>
            <ul class="navmenu-v">
                <li>' . GetMessage("MENU_SECTION_FIELDS_SECTION") . '
                    <ul>
                        <li class="with-prop" data-prop="#SECTION_ID#">' . GetMessage("MENU_SECTION_FIELDS_SECTION_ID") . '</li>
                        <li class="with-prop" data-prop="#SECTION_CODE#">' . GetMessage("MENU_SECTION_FIELDS_SECTION_CODE") . '</li>
                        <li class="with-prop" data-prop="#SECTION_CODE_PATH#">' . GetMessage("MENU_SECTION_FIELDS_SECTION_CODE_PATH") . '</li>
                    </ul>
                </li>
                <li>' . GetMessage("MENU_SECTION_FIELDS_PROP") . '
                    <ul>
                        <li class="with-prop" data-prop="#PROPERTY_ID#">' . GetMessage("MENU_SECTION_FIELDS_PROPERTY_ID") . '</li>
                        <li class="with-prop" data-prop="#PROPERTY_CODE#">' . GetMessage("MENU_SECTION_FIELDS_PROPERTY_CODE") . '</li>
                    </ul>
                </li>
                <li>' . GetMessage("MENU_SECTION_FIELDS_PROP_VALUE") . '
                    <ul>
                        <li class="with-prop" data-prop="#PROPERTY_VALUE#">' . GetMessage("MENU_SECTION_FIELDS_PROPERTY_VALUE_CODE") . '</li>
                    </ul>
                </li>
                <li>' . GetMessage("MENU_SECTION_FIELDS_ELSE") . '
                    <ul>
                        <li class="with-prop" data-prop="#FILTER#">' . GetMessage("MENU_SECTION_FIELDS_PROPERTY_FILTER") . '</li>
                        <li class="with-prop" data-prop="#PRICES#">' . GetMessage("MENU_SECTION_FIELDS_PRICES") . '</li>
                    </ul>
                </li>
            </ul>
        ';

        return $return;
    }

    public function AllCombinationsOfArrayElements(
        $array
    ) {
        $return = [];
        $col_el = count($array);
        $col_zn = pow(2, $col_el) - 1;
        for ($i = 1; $i <= $col_zn; $i++) {
            $dlina_i_bin = decbin($i);
            $zap_str = str_pad($dlina_i_bin, $col_el, "0", STR_PAD_LEFT);
            $zap_dop = strrev($zap_str);
            $dooh = [];
            for ($j = 0; $j < $col_el; $j++) {
                $dooh[] = $zap_dop[$j];
            }

            $d = 0;
            $a = "";
            foreach ($dooh as $v) {
                if ($v == 1) {
                    $a[] .= $array[$d];
                }
                $d++;
            }
            $return[] = $a;
        }

        return $return;
    }
}

class DataManagerEx_SeoMeta extends
    DataManager
{

    public static function getList(
        array $parameters = []
    ) {
        $module = new CCSeoMeta();
        return !$module->getDemo() ? new Result(parent::query(), new ArrayResult([])) : parent::getList($parameters);
    }

    public static function getById(
        $id = ""
    ) {
        $module = new CCSeoMeta();
        return !$module->getDemo() ? new \CDBResult : parent::getById($id);
    }

    public static function add(
        array $arr = []
    ) {
        $module = new CCSeoMeta();
        return !$module->getDemo() ? new AddResult() : parent::add($arr);
    }

    public static function update(
        $id = "",
        array $arr = []
    ) {
        $module = new CCSeoMeta();
        return !$module->getDemo() ? new UpdateResult() : parent::update($id, $arr);
    }

    public static function saveFile(
        array $arFile
    ) {
        $arFile[key($arFile)] = \CIBlock::makeFileArray($arFile[key($arFile)],
            ${key($arFile)} === "Y",
            $_REQUEST[key($arFile)]['name']
        );

        return CFile::SaveFile($arFile[key($arFile)], "seometa");
    }
}

?>
