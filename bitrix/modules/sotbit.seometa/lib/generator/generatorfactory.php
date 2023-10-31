<?
namespace Sotbit\Seometa\Generator;

use \Bitrix\Main\Localization\Loc;
use Sotbit\Seometa\Helper\Settings;

Loc::loadMessages(__FILE__);

class GeneratorFactory {
    public static function create($filterType, $siteID)
    {
        if($filterType == 'default') {
            $filterType = Settings::getSettingsForSite($siteID)->FILTER_TYPE;
        }

        switch($filterType)
        {
            case 'misshop_chpu':
                return new MissShopChpuGenerator();
            case 'combox_not_chpu':
                return new ComboxGenerator();
            case 'combox_chpu':
                return new ComboxChpuGenerator();
            case 'bitrix_chpu':
                return new BitrixChpuGenerator();
            case 'bitrix_not_chpu':
                return new BitrixGenerator();
            case 'chpu':
                return new ChpuGenerator();
            default:
                throw new \Exception(str_replace('#filter_type#', $FilterType, Loc::getMessage('SEO_META_EXCEPTION_MESSAGE_FACTORY_UNKNOWN_FILTER_TYPE')));
        }
    }
}
?>