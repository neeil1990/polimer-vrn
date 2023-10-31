<?
namespace Sotbit\Seometa\Orm;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
class SitemapTable extends \DataManagerEx_SeoMeta
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_sotbit_seometa_sitemaps';
	}

	public static function getMap() 
	{
		return array(
			'ID' => array( 
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'TIMESTAMP_CHANGE' => array(
				'data_type' => 'datetime'
			),
			'SITE_ID' => array(
				'data_type' => 'string',
				'required' => true,
			),
			'NAME' => array(
				'data_type' => 'string',
				'required' => true,
				'title' => Loc::getMessage('SITEMAP_NAME_TITLE'),
			),
			'DATE_RUN' => array(
				'data_type' => 'datetime',
			),
			'SETTINGS' => array(
				'data_type' => 'text',
			),
		);
	}

    public static function delete($ID)
    {
        $dbSitemap = SitemapTable::getById( $ID );
        $arSitemap = $dbSitemap->fetch();
        $arSitemap['SETTINGS'] = unserialize( $arSitemap['SETTINGS'] );
        $rsSites = \CSite::GetById( $arSitemap['SITE_ID'] );
        $arSite = $rsSites->Fetch();

        $seometaUrlCollection = SeometaUrlTable::getList([
            'select' => [
                'ID',
                'IN_SITEMAP',
                'SITE_ID',
            ],
            'filter' => ['SITE_ID' => $arSitemap['SITE_ID'], 'IN_SITEMAP' => 'Y'],
        ])->fetchCollection();

        $elements = $seometaUrlCollection->getAll();
        $chpuCount = count($elements);
        foreach ($elements as $element) {
            $element->set('IN_SITEMAP', false);
        }
        $seometaUrlCollection->save();

        $seometaSitemap = new \CSeoMetaSitemapLight();
        $seometaSitemap->deleteOldSeometaSitemaps($arSite['ABS_DOC_ROOT'] . $arSite['DIR']);

        if (file_exists( $arSite['ABS_DOC_ROOT'] . $arSite['DIR'] . $arSitemap['SETTINGS']['FILENAME_INDEX'] ))
        {
            $SiteUrl = "";
            if (isset( $arSitemap['SETTINGS']['PROTO'] ) && $arSitemap['SETTINGS']['PROTO'] == 1)
            {
                $SiteUrl .= 'https://';
            }
            elseif (isset( $arSitemap['SETTINGS']['PROTO'] ) && $arSitemap['SETTINGS']['PROTO'] == 0)
            {
                $SiteUrl .= 'http://';
            }
            if (isset( $arSitemap['SETTINGS']['DOMAIN'] ) && !empty( $arSitemap['SETTINGS']['DOMAIN'] ))
                $SiteUrl .= $arSitemap['SETTINGS']['DOMAIN'];

            $xml = simplexml_load_file( $arSite['ABS_DOC_ROOT'] . $arSite['DIR'] .
                $arSitemap['SETTINGS']['FILENAME_INDEX'] );
            for($i = 0; $i < count($xml->sitemap ); $i++)
            {
                if (isset( $xml->sitemap[$i]->loc ) && mb_strpos($xml->sitemap[$i]->loc, $SiteUrl . $arSite['DIR'] . "sitemap_seometa_") !== false)
                {
                    unset($xml->sitemap[$i]);
                    $i--;
                }
            }
            file_put_contents( $arSite['ABS_DOC_ROOT'] . $arSite['DIR'] . $arSitemap['SETTINGS']['FILENAME_INDEX'], $xml->asXML() );
        }
        self::deleteAgentAction($ID);
        return parent::delete($ID);
    }

    public static function deleteAgent($id)
    {
        \CModule::IncludeModule('main');
        $xmlWriterAgentChpuWithRegenerate = \CAgent::GetList(array(), array("NAME"=>"\Sotbit\Seometa\Agent::xmlWriterAgentChpuWithRegenerate({$id});"))->Fetch();
        $xmlWriterAgentChpuNotRegenerate = \CAgent::GetList(array(), array("NAME"=>"\Sotbit\Seometa\Agent::xmlWriterAgentChpuNotRegenerate($id);"))->Fetch();
        if($xmlWriterAgentChpuWithRegenerate){
            \CAgent::Delete($xmlWriterAgentChpuWithRegenerate["ID"]);
        }
        if ($xmlWriterAgentChpuNotRegenerate){
            \CAgent::Delete($xmlWriterAgentChpuNotRegenerate["ID"]);
        }
    }

    public static function deleteAgentAction($id)
    {
        is_array($id) ? array_map(fn($item) => self::deleteAgent($item), $id) : self::deleteAgent($id);
    }
}
