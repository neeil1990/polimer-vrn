<?
/*
ALTER TABLE b_sotbit_seometa
MODIFY COLUMN SECTIONS TEXT;
*/

namespace Sotbit\Seometa;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\Type;
use Sotbit\Seometa\Helper\Filter;
use Sotbit\Seometa\Helper\Mask;
use Sotbit\Seometa\Generator\CommonGenerator;

Loc::loadMessages( __FILE__ );
require_once $_SERVER["DOCUMENT_ROOT"] . '/bitrix/modules/sotbit.seometa/classes/general/seometa_sitemap.php';
require_once $_SERVER["DOCUMENT_ROOT"] . '/bitrix/modules/iblock/classes/general/iblocksection.php';
class ConditionTable extends \DataManagerEx_SeoMeta
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_sotbit_seometa';
	}

	public static function getMap()
	{
		return array(
			new Entity\IntegerField( 'ID', array (
					'primary' => true,
					'autocomplete' => true
			) ),
			new Entity\StringField( 'NAME', array (
					'required' => true,
					'title' => Loc::getMessage( 'SEOMETA_NAME' )
			) ),
			new Entity\BooleanField( 'ACTIVE', array (
					'values' => array (
							'N',
							'Y'
					),
					'title' => Loc::getMessage( 'SEOMETA_ACTIVE' )
			) ),
			new Entity\BooleanField( 'SEARCH', array (
					'values' => array (
							'N',
							'Y'
					),
					'title' => Loc::getMessage( 'SEOMETA_SEARCH' )
			) ),
			new Entity\IntegerField( 'SORT', array (
					'required' => true,
					'title' => Loc::getMessage( 'SEOMETA_SORT' )
			) ),
			new Entity\DatetimeField( 'DATE_CHANGE', array (
					'title' => Loc::getMessage( 'SEOMETA_DATE_CHANGE' )
			) ),
			new Entity\TextField( 'SITES', array (
					'title' => Loc::getMessage( 'SEOMETA_SITES' )
			) ),
			new Entity\StringField( 'TYPE_OF_CONDITION', array (
					'title' => Loc::getMessage( 'SEOMETA_TYPE_OF_CONDITION' )
			) ),
			new Entity\StringField( 'FILTER_TYPE', array (
					'title' => Loc::getMessage( 'SEOMETA_TYPE_OF_FILTER_TYPE' )
			) ),
			new Entity\StringField( 'TYPE_OF_INFOBLOCK', array (
					'title' => Loc::getMessage( 'SEOMETA_TYPE_OF_INFOBLOCK' )
			) ),
			new Entity\StringField( 'INFOBLOCK', array (
					'title' => Loc::getMessage( 'SEOMETA_INFOBLOCK' )
			) ),
			new Entity\TextField( 'SECTIONS', array (
					'title' => Loc::getMessage( 'SEOMETA_SECTIONS' )
			) ),
			new Entity\TextField( 'RULE', array (
					'title' => Loc::getMessage( 'SEOMETA_RULE' )
			) ),
			new Entity\StringField( 'META', array (
					'title' => Loc::getMessage( 'SEOMETA_META' )
			) ),
			new Entity\BooleanField( 'NO_INDEX', array (
					'values' => array (
							'N',
							'Y'
					),
					'title' => Loc::getMessage( 'SEOMETA_NO_INDEX' )
			) ),
			new Entity\BooleanField( 'STRONG', array (
					'values' => array (
							'N',
							'Y'
					),
					'title' => Loc::getMessage( 'SEOMETA_STRONG' )
			) ),
			new Entity\FloatField( 'PRIORITY', array (
					'title' => Loc::getMessage( 'SEOMETA_PRIORITY' )
			) ),
			new Entity\FloatField( 'CHANGEFREQ', array (
					'title' => Loc::getMessage( 'SEOMETA_CHANGEFREQ' )
			) ),
			new Entity\IntegerField( 'CATEGORY_ID', array (
					'required' => true,
					'title' => Loc::getMessage( 'SEOMETA_CATEGORY_ID' )
			) ),
			new Entity\TextField( 'TAG', array (
					'title' => Loc::getMessage( 'SEOMETA_TAG' )
			) ),
			new Entity\TextField( 'CONDITION_TAG', array (
					'title' => Loc::getMessage( 'SEOMETA_CONDITION_TAG' )
			) ),
			new Entity\TextField( 'STRICT_RELINKING', array (
					'title' => Loc::getMessage( 'SEOMETA_STRICT_RELINKING' )
			) ),
            new Entity\BooleanField( 'GENERATE_AJAX', array (
                'values' => array (
                    'N',
                    'Y'
                ),
                'title' => Loc::getMessage( 'SEOMETA_GENERATE_AJAX' )
            ) ),
		);
	}

	/**
	 * return conditions for sections
	 *
	 * @param array $Sections
	 * @return array
	 */
	public static function GetConditionsBySections($Sections = array())
	{
		$return = array();

		$Conditions = ConditionTable::getList(array(
			'filter' => array(
				'ACTIVE' => 'Y'
			),
			'order' => array('SORT' => 'asc'),
			'select' => array(
				'ID',
				'SITES',
				'SECTIONS',
				'RULE',
				'TAG',
				'FILTER_TYPE',
				'INFOBLOCK',
				'STRICT_RELINKING',
			)
		));
		while($Condition = $Conditions->fetch())
		{
			$Sites = unserialize($Condition['SITES']);
			if(!in_array(SITE_ID, $Sites))
			{
				continue;
			}

			$ConditionSections = unserialize($Condition['SECTIONS']);
			if(!$ConditionSections)
			{
				$NeedSection = $Sections;
			}
			else
			{
				$NeedSection = array_intersect($Sections, $ConditionSections);
			}

			if($NeedSection)
			{
				$Condition['SECTIONS'] = $NeedSection;
				$return[$Condition['ID']] = $Condition;
			}
		}
		unset( $NeedSection );
		unset( $Sites );
		unset( $Sections );
		unset( $Conditions );
		unset( $ConditionSections );
		unset( $Condition );
		return $return;
	}

	public static function generateUrlForCondition($id, $sectionId = false, $isProgress = false, $isError = false)
	{
        @set_time_limit(0);
		if ($id == 0)
			return array();

        if ($isError && is_array($sectionId))
            \Sotbit\Seometa\SeometaUrlTable::deleteBySectionId($sectionId[0]);

        $writer = \Sotbit\Seometa\Link\ChpuWriter::getInstance($id, $isProgress);
		$link = \Sotbit\Seometa\Helper\Link::getInstance();
        $link->Generate($id, $writer, $sectionId);
		return $writer->getData();
	}

	public static function getSectionList($id, $mode = false){
        $link = \Sotbit\Seometa\Helper\Link::getInstance();
	    return $link->getSectionList($id, $mode);
    }


	/**
	 * get linked conditions
	 * @param array $WorkingConditions
	 * @return array
	 */
	public static function GetConditionsFromWorkingConditions($WorkingConditions = array())
	{
		$return = array();
		if($WorkingConditions)
		{
			$idConditions = array();
			$Conditions = ConditionTable::getList(array(
					'filter' => array(
						'ID' => $WorkingConditions
					),
					'order' => array(
						'SORT' => 'asc'
					),
					'select' => array(
						'CONDITION_TAG'
					)
			));
			while($Condition = $Conditions->fetch())
			{
				if($Condition['CONDITION_TAG'])
				{
					$arCond = unserialize($Condition['CONDITION_TAG']);
					if(is_array($arCond))
					{
						$idConditions = array_merge($idConditions,$arCond);
					}
				}
			}

			if($idConditions)
			{
				$NeedSection = array();

				$Conditions = ConditionTable::getList(array(
					'filter' => array(
						'ID' => $idConditions
					),
					'order' => array(
						'SORT' => 'asc'
					),
					'select' => array(
						'ID',
						'SITES',
						'SECTIONS',
						'RULE',
						'TAG',
						'FILTER_TYPE',
						'INFOBLOCK',
					)
				));
				while($Condition = $Conditions->fetch())
				{
					if(!$Condition['TAG'])
					{
						continue;
					}

					$Condition['SECTIONS'] = unserialize($Condition['SECTIONS']);
					$return[$Condition['ID']] = $Condition;
				}
			}
		}

		return $return;
	}

	public static function delete($ID)
	{
		SeometaUrlTable::deleteByConditionId($ID);
		return parent::delete($ID);
	}

	public static function getItemForExportToExcel($id)
	{
		$res = self::getList(array(
			'select' => array('ID', 'NAME', 'META'),
			'filter' => array('ID' => $id),
			'order' => array('ID'),
			'limit' => 1
		));

		return $res->fetch();
	}
}
