<?
namespace Sotbit\Seometa\Orm;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);
class SitemapSectionTable extends \DataManagerEx_SeoMeta
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_sotbit_seometa_section';
	}

	public static function getMap()
	{
		return array(
			new Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true,
			)),
			new Entity\StringField('NAME', array(
				'required' => true,
				'title' => Loc::getMessage('SEOMETA_NAME'),
			)),
			new Entity\BooleanField('ACTIVE', array(
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('SEOMETA_ACTIVE'),
			)),
			new Entity\IntegerField('SORT', array(
				'required' => true,
				'title' => Loc::getMessage('SEOMETA_SORT'),
			)),
			new Entity\DatetimeField('DATE_CREATE', array(
					'title' => Loc::getMessage('SEOMETA_DATE_CREATE'),
			)),
			new Entity\DatetimeField('DATE_CHANGE', array(
				'title' => Loc::getMessage('SEOMETA_DATE_CHANGE'),
			)),
			new Entity\TextField('DESCRIPTION', array(
					'title' => Loc::getMessage('SEOMETA_DESCRIPTION'),
			)),
			new Entity\IntegerField('PARENT_CATEGORY_ID', array(
					'title' => Loc::getMessage('SEOMETA_PARENT_CATEGORY_ID'),
					'required' => true,
			)),
		);
	}
    
    public static function deleteSection($ID){
        $result = false;
        $result = self::delete($ID);
        if($result->isSuccess()){
            $res = ConditionTable::GetList(array(
                'filter' => array('CATEGORY_ID'=>$ID),
            ));
            while($one = $res->fetch()){
                $result = ConditionTable::delete($one['ID']);    
            }
            $res = self::getList(array(
                'filter' =>array('PARENT_CATEGORY_ID'=>$ID),
            ));
            while($one = $res->fetch()){
                self::deleteSection($one['ID']);                
            }   
        }
        return $result;        
    }
}
