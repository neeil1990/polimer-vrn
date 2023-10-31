<?
/*
ALTER TABLE b_sotbit_seometa
MODIFY COLUMN SECTIONS TEXT;
*/

namespace Sotbit\Seometa\Orm;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SiteTable;
use Sotbit\Seometa\Helper\ImportExport\ImportHelper;
use Sotbit\Seometa\Helper\Linker;
use Sotbit\Seometa\Link\ChpuWriter;

/**
 * Class ConditionTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> NAME string
 * <li> ACTIVE bool
 * <li> SEARCH bool
 * <li> SORT int
 * <li> DATE_CHANGE data
 * <li> SITES string
 * <li> TYPE_OF_CONDITION string
 * <li> FILTER_TYPE string
 * <li> TYPE_OF_INFOBLOCK string
 * <li> INFOBLOCK string
 * <li> SECTIONS string
 * <li> RULE string
 * <li> META string
 * <li> NO_INDEX bool
 * <li> STRONG bool
 * <li> GENERATE_AJAX bool
 * <li> PRIORITY float
 * <li> CHANGEFREQ float
 * <li> CATEGORY_ID string
 * <li> TAG string
 * <li> CONDITION_TAG string
 * <li> STRICT_RELINKING string
 * </ul>
 *
 * @package Bitrix\Sotbit
 **/

Loc::loadMessages(__FILE__);

class ConditionTable extends \DataManagerEx_SeoMeta
{
    /**
     * Get path file class
     *
     * @return string
     */
    public static function getFilePath(
    ) {
        return __FILE__;
    }

    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName(
    ) {
		return 'b_sotbit_seometa';
	}

    /**
     * Returns entity map definition.
     *
     * @return array
     */
    public static function getMap(
    ) {
        return [
            new Entity\IntegerField('ID', [
                'primary' => true,
                'autocomplete' => true
            ]),
            new Entity\StringField('NAME', [
                'required' => true,
                'title' => Loc::getMessage('SEOMETA_NAME')
            ]),
            new Entity\BooleanField('ACTIVE', [
                'values' => [
                    'N',
                    'Y'
                ],
                'title' => Loc::getMessage('SEOMETA_ACTIVE')
            ]),
            new Entity\BooleanField('SEARCH', [
                'values' => [
                    'N',
                    'Y'
                ],
                'title' => Loc::getMessage('SEOMETA_SEARCH')
            ]),
            new Entity\IntegerField('SORT', [
                'required' => true,
                'title' => Loc::getMessage('SEOMETA_SORT')
            ]),
            new Entity\DatetimeField('DATE_CHANGE', [
                'title' => Loc::getMessage('SEOMETA_DATE_CHANGE')
            ]),
            new Entity\TextField('SITES', [
                'required' => true,
                'title' => Loc::getMessage('SEOMETA_SITES')
            ]),
            new Entity\StringField('TYPE_OF_CONDITION', [
                'title' => Loc::getMessage('SEOMETA_TYPE_OF_CONDITION')
            ]),
            new Entity\StringField('FILTER_TYPE', [
                'title' => Loc::getMessage('SEOMETA_FILTER_TYPE')
            ]),
            new Entity\StringField('TYPE_OF_INFOBLOCK', [
                'title' => Loc::getMessage('SEOMETA_TYPE_OF_INFOBLOCK')
            ]),
            new Entity\StringField('INFOBLOCK', [
                'title' => Loc::getMessage('SEOMETA_INFOBLOCK')
            ]),
            new Entity\TextField('SECTIONS', [
                'title' => Loc::getMessage('SEOMETA_SECTIONS')
            ]),
            new Entity\TextField('RULE', [
                'title' => Loc::getMessage('SEOMETA_RULE')
            ]),
            new Entity\StringField('META', [
                'title' => Loc::getMessage('SEOMETA_META')
            ]),
            new Entity\BooleanField('NO_INDEX', [
                'values' => [
                    'N',
                    'Y'
                ],
                'title' => Loc::getMessage('SEOMETA_NO_INDEX')
            ]),
            new Entity\BooleanField('STRONG', [
                'values' => [
                    'N',
                    'Y'
                ],
                'title' => Loc::getMessage('SEOMETA_STRONG')
            ]),
            new Entity\BooleanField('GENERATE_AJAX', [
                'values' => [
                    'N',
                    'Y'
                ],
                'title' => Loc::getMessage( 'SEOMETA_GENERATE_AJAX' )
            ]),
			new Entity\FloatField( 'PRIORITY', [
					'title' => Loc::getMessage( 'SEOMETA_PRIORITY' )
            ]),
			new Entity\FloatField( 'CHANGEFREQ', [
					'title' => Loc::getMessage( 'SEOMETA_CHANGEFREQ' )
            ]),
			new Entity\IntegerField( 'CATEGORY_ID', [
					'required' => true,
					'title' => Loc::getMessage( 'SEOMETA_CATEGORY_ID' )
            ]),
			new Entity\TextField( 'TAG', [
					'title' => Loc::getMessage( 'SEOMETA_TAG' )
            ]),
            new Entity\BooleanField( 'HIDE_IN_SECTION', [
                'values'=>['N','Y'],
                'title' => Loc::getMessage( 'SEOMETA_HIDE_IN_SECTION' )
            ]),
			new Entity\TextField( 'CONDITION_TAG', [
					'title' => Loc::getMessage( 'SEOMETA_CONDITION_TAG' )
            ]),
			new Entity\BooleanField( 'STRICT_RELINKING', [
                    'values'=>['N','Y'],
					'title' => Loc::getMessage( 'SEOMETA_STRICT_RELINKING' )
            ])
		];
	}

	/**
	 * Get conditions by sections
	 *
	 * @param array $Sections
	 * @return array
	 */
    public static function GetConditionsBySections(
        $Sections = []
    ) {
		$return = [];
		$Conditions = ConditionTable::getList([
			'filter' => [
				'=ACTIVE' => 'Y'
            ],
			'order' => ['SORT' => 'asc'],
			'select' => [
				'ID',
				'SITES',
				'SECTIONS',
				'RULE',
				'TAG',
				'FILTER_TYPE',
				'INFOBLOCK',
				'STRICT_RELINKING',
                'HIDE_IN_SECTION',
            ]
        ]);
        while ($Condition = $Conditions->fetch()) {
			$Sites = unserialize($Condition['SITES']);
            if (!in_array(SITE_ID, $Sites)) {
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

    /**
     * Generate URL for condition
     *
     * @param $id
     * @param false $sectionId
     * @param false $isProgress
     * @param false $isError
     * @return array|bool
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     */
    public static function generateUrlForCondition(
        $id,
        $sectionId = false,
        $isProgress = false,
        $isError = false,
        $excelParse = false
    ) {
        @set_time_limit(0);
        if ($id == 0) {
            return [];
        }

		$arrChpu = SeometaUrlTable::getByCondition($id);
		if($arrChpu && is_array($arrChpu)) {
		    foreach($arrChpu as &$chpu) {
                $chpu = array_intersect_key($chpu, ['REAL_URL' => 'true', 'SITE_ID' => 'true']);
            }
            unset($chpu);
        }

        if ($isError && $sectionId) {
            SeometaUrlTable::deleteByOptions($id, $sectionId);
        }

        if($excelParse){
            $writer = ChpuWriter::getWriterForAutogenerator($id);
            $link = Linker::getLinkerForAutogenerator();
        }else{
            $writer = ChpuWriter::getInstance($id, $isProgress);
            $link = Linker::getInstance();
        }

        $link->Generate($writer, $id, $sectionId);
        $arrData = $writer->getData();
        if($arrData && is_array($arrData)) {
            foreach($arrData as $idCHPU => $chpu) {
                $arrNewChpu[$idCHPU] = array_intersect_key($chpu, ['REAL_URL' => 'true', 'SITE_ID' => 'true']);
            }

            $arrChpuResult = [];
            foreach ($arrNewChpu as $index => $value) {
                if($indexOld = array_search($value, $arrChpu)) {
                    $arrChpuResult[$indexOld] = $index;
                    $arrChpuSeoDataResult[] = $value;
                }
            }

            if($arrChpuResult && is_array($arrChpuResult)) {
                $arrLinks = ChpuLinksTable::getByMainChpuId(array_keys($arrChpuResult));
                $arrSeoData = ChpuSeoDataTable::getByConditionId($id);
                foreach ($arrLinks as $arrLink) {
                    $newData = $arrLink;
                    $newData['MAIN_CHPU_ID'] = $arrChpuResult[$newData['MAIN_CHPU_ID']] ?: $newData['MAIN_CHPU_ID'];
                    $newData['LINK_CHPU_ID'] = $arrChpuResult[$newData['LINK_CHPU_ID']] ?: $newData['LINK_CHPU_ID'];
                    if(array_diff($newData, $arrLink)) {
                        $newData['SEOMETA_DATA_CHPU_LINK'] = serialize($newData['SEOMETA_DATA_CHPU_LINK']);
                        ChpuLinksTable::update($newData['ID'], $newData);
                    } else {
                        ChpuLinksTable::delete($arrLink['ID']);
                    }
                }
            }
        }

		return $arrData;
	}

    /**
     * Get section list by condition ID
     *
     * @param $id
     * @return array|false|mixed
     */
    public static function getSectionList(
	    $id
    ) {
	    return Linker::getInstance()->getSectionList($id);
    }

    /**
     * Get condition by site ID
     *
     * @param $siteId
     * @return array|string[]
     */
    public static function getConditionBySiteId(
        $siteId
    ) {
        if($siteId) {
            $resConditions = ConditionTable::getList([
                'order' => [
                    'SORT' => 'asc'
                ],
                'select' => [
                    'ID',
                    'SITES',
                    'DATE_CHANGE',
                    'PRIORITY',
                    'CHANGEFREQ'
                ],
                'filter' => [
                    'ACTIVE' => 'Y'
                ]
            ]);

            $arConditionParams = [];
            while ($Condition = $resConditions->fetch()) {
                if ($Condition['SITES']) {
                    $arCond = unserialize($Condition['SITES']);
                    if (is_array($arCond) && in_array($siteId, $arCond)) {
                        $arConditionParams[] = $Condition;
                    }
                }
            }

            return $arConditionParams;
        }

        return [
            'TYPE' => 'ERROR',
            'MSG' => 'Parameter function is empty (site_id = "")'
        ];
    }

    /**
     * Get condition (only active) by ID
     *
     * @param $condition_id
     * @return array|false|string[]
     */
    public static function getConditionById($condition_id) {
        if(!empty($condition_id)) {
            $filter['ID'] = $condition_id;
            $filter['ACTIVE'] = 'Y';

            $resConditions = ConditionTable::getList([
                'order' => [
                    'SORT' => 'asc'
                ],
                'select' => [
                    'ID',
                    'PRIORITY',
                    'CHANGEFREQ'
                ],
                'filter' => $filter
            ]);

            return $resConditions->fetch();
        }

        return [
            'TYPE' => 'ERROR',
            'MSG' => 'Parameter function is empty (condition_id = "")'
        ];
    }


	/**
	 * Get linked conditions
     *
	 * @param array $WorkingConditions
	 * @return array
	 */
	public static function GetConditionsFromWorkingConditions(
	    $WorkingConditions = []
    ) {
		$return = [];
        if ($WorkingConditions) {
			$idConditions = [];
			$Conditions = ConditionTable::getList([
					'filter' => [
						'ID' => $WorkingConditions,
                        'ACTIVE' => 'Y'
                    ],
					'order' => [
						'SORT' => 'asc'
                    ],
					'select' => [
						'CONDITION_TAG',
                        'STRICT_RELINKING'
                    ]
            ]);
            while ($Condition = $Conditions->fetch()) {
                if ($Condition['STRICT_RELINKING'] == 'Y' && $Condition['CONDITION_TAG']) {
                    $arCond = unserialize($Condition['CONDITION_TAG']);
                    if (is_array($arCond)) {
                        $idConditions = array_merge($idConditions, $arCond);
                    }
                }
            }

            if ($idConditions) {
				$Conditions = ConditionTable::getList([
					'filter' => [
						'ID' => $idConditions,
                        'ACTIVE' => 'Y'
                    ],
					'order' => [
						'SORT' => 'asc'
                    ],
					'select' => [
						'ID',
						'SITES',
						'SECTIONS',
						'RULE',
						'TAG',
						'FILTER_TYPE',
						'INFOBLOCK',
                    ]
                ]);
                while ($Condition = $Conditions->fetch()) {
                    if (!$Condition['TAG']) {
                        continue;
                    }

					$Condition['SECTIONS'] = unserialize($Condition['SECTIONS']);
					$return[$Condition['ID']] = $Condition;
				}
			}
		}

		return $return;
	}

    /**
     * Delete condition by ID
     *
     * @param $ID
     * @return mixed
     */
    public static function delete(
	    $ID
    ) {
		SeometaUrlTable::deleteByOptions($ID, false, 'all');
		return parent::delete($ID);
	}

    public static function addAfterParse(?array $arr = [], $generate = null)
    {
        $cond = [
            'NAME' => $arr['NAME'],
            'ACTIVE' => $arr['ACTIVE'] ?: 'N',
            'SEARCH' => $arr['SEARCH'] ?: 'Y',
            'SORT' => $arr['SORT'] ?: 100,
            'TYPE_OF_INFOBLOCK' => $arr['TYPE_OF_INFOBLOCK'],
            'INFOBLOCK' => $arr['INFOBLOCK'],
            'SECTIONS' => serialize(explode(',', $arr['SECTIONS'])),
            'NO_INDEX' => $arr['NO_INDEX'] ?: 'N',
            'STRONG' => $arr['STRONG'] ?: 'Y',
            'PRIORITY' => $arr['PRIORITY'] ?: 0.5,
            'CHANGEFREQ' => $arr['CHANGEFREQ'] ?: 'monthly',
            'FILTER_TYPE' => $arr['FILTER_TYPE'],
            'TAG' => $arr['TAG'],
            'HIDE_IN_SECTION' => $arr['HIDE_IN_SECTION'] ?: 'N',
            'STRICT_RELINKING' => $arr['STRICT_RELINKING'] ?: 'N',
            'GENERATE_AJAX' => $arr['GENERATE_AJAX'] ?: 'N',
            'CATEGORY_ID' => $arr['CATEGORY_ID'] ?: 0,
            'DATE_CHANGE' => new \Bitrix\Main\Type\DateTime(),
        ];

        $cond['RULE'] = ImportHelper::morphyRuleForImport($arr['RULE']);

        $arrSites = SiteTable::query()
            ->addSelect('LID')
            ->setFilter([])
            ->fetchAll();

        $LIDs = [];
        array_walk($arrSites, function ($site) use (&$LIDs) {
            $LIDs[] = $site['LID'];
        });
        $arrCurrentCondSites = explode(',', $arr['SITES']);
        if ($arrCurrentCondSites) {
            foreach ($arrCurrentCondSites as $key=>$site) {
                if (!in_array($site, $LIDs)) {
                    unset($arrCurrentCondSites[$key]);
                }
            }
        }
        $result = null;
        if ($arrCurrentCondSites) {
            $cond['SITES'] = serialize($arrCurrentCondSites);
        } else {
            $result = Loc::getMessage('SEOMETA_GENERATE_SITES_ERROR');
        }

        if(!$result){
            foreach ($arr as $key => $value) {
                if (stripos($key, 'ELEMENT_') !== false) {
                    if ($key === 'ELEMENT_FILE') {
                        $arrFile = \CFile::MakeFileArray($value);
                        $seoMetaData[$key] = \CFile::SaveFile($arrFile, 'seo_images');
                    } else {
                        $seoMetaData[$key] = $value;
                    }
                } elseif ($key === 'TEMPLATE_NEW_URL' || $key === 'SPACE_REPLACEMENT') {
                    $seoMetaData[$key] = $value;
                }
            }
            if ($seoMetaData) {
                $cond['META'] = serialize($seoMetaData);
            }
            $resultCond = parent::add($cond);
            if ($resultCond->isSuccess()) {
                if ($generate) {
                    $chpu = self::generateUrlForCondition($resultCond->getId(), false, false, false, true);
                    if (!$chpu) {
                        $result = Loc::getMessage('SEOMETA_GENERATE_ERROR_IMPORT');
                    } else {
                        $result = null;
                    }
                } else {
                    $result = null;
                }

            } else {
                $result = '<span style="color: red">' . $resultCond->getErrorMessages()[0] . '</span>';
            }
        }

        return $result;
    }

}
