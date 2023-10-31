<?
namespace Sotbit\Seometa;
use Bitrix\Main\Entity;
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
}
