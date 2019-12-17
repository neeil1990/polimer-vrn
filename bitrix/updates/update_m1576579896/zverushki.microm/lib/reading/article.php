<?
namespace Zverushki\Microm\Reading;

use Bitrix\Main\Loader,
	Bitrix\Main\Type as BxType,
	Bitrix\Main,
	Bitrix\Main\Config,
	Zverushki\Microm,
	Zverushki\Microm\Tools\Page;


Loader::includeModule('iblock');


/**
* class Article
*
*
* @const NAME - уникальный ключ типа
* @var $__instance - объект
*
* @param $__options - входные параметры (настраиваются в модуле)
*
* @package Zverushki\Microm\Reading\Article
*/
class Article extends Type {
	const NAME = 'Article';
	private static $__instance = null;

	protected function __construct ($options) {
		parent::__construct(self::NAME, $options);

		$this->startCache(array(
			'id' => $_SERVER['REQUEST_URI']
		));

	} // end __construct

	public static function getInstance ($options) {
		if (static::$__instance === null)
			static::$__instance = new self($options);

		return static::$__instance;
	} // end function getInstance

	public static function getAdditionalOptions ($SITE_ID) {
		$IBs = \unserialize(Config\Option::get('zverushki.microm', strtolower(self::NAME).'_ib_active'));

		$options = array(
			'schema' => array(),
			'URLs' => array()
		);

		if (empty($IBs))
			return $options;

		$db = \CIBlock::GetList(
		    Array('sort' => 'asc'),
		    Array('SITE_ID' => $SITE_ID, 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'Y', 'ID' => $IBs),
		    true
		);
		while (($a = $db->fetch()) !== false) {
			if ($a['ELEMENT_CNT'] <= 0 || isset($options['URLs'][$a['DETAIL_PAGE_URL']]))
				continue;

			$options['URLs'][$a['DETAIL_PAGE_URL']] = array(
				'ID' => $a['ID'],
				'CODE' => $a['CODE'],
				'NAME' => $a['NAME'],
				'DETAIL_PAGE_URL' => $a['DETAIL_PAGE_URL'],
				'schema' => array()
			);

			$optionArticleIb = Microm\MicromTable::getList(array(
				'filter' => array('CODE' => strtolower(self::NAME).'_ib_'.$a['ID'])
			))->fetch();
			if ($optionArticleIb !== false) {
				$type = $optionArticleIb['VALUE']['type'];

				$options['URLs'][$a['DETAIL_PAGE_URL']]['schema'] = array(
					'props' => $optionArticleIb['VALUE'],
					'template' => $type
				);
			}
		}

		return $options;
	} // end function setAdditionalOptions


	/**
	 * Формируем данные
	 * @return array - массив данных
	 */
	protected function model () {
		$Page = Page\UrlRewrite::getInstance();

		foreach ($this->__options['URLs'] as $urlTemplate => $setIb)
			if (($rule = $Page->getRuleByUrl($urlTemplate)) !== false) {
				$this->__options['schema'] = $setIb['schema'];
				$filter = array();

				if ($rule['VARIABLES']['ELEMENT_ID'])
					$filter['ID'] = $rule['VARIABLES']['ELEMENT_ID'];

				elseif ($rule['VARIABLES']['ELEMENT_CODE'])
					$filter['CODE'] = $rule['VARIABLES']['ELEMENT_CODE'];

				else {
					$this->__status = 'Error. Empty variables values ELEMENT_ID & ELEMENT_CODE'; // Результат выполнения пуст
					return false;
				}

				$select = array('*');
				if (!empty($this->__options['schema']['props'])) {
					foreach ($this->__options['schema']['props'] as $code)
						$select[] = 'PROPERTY_'.$code;
				}

				$result =  \CIBlockElement::getList(
						array(),
						array_merge(array('IBLOCK_ID' => $setIb['ID'], 'ACTIVE' => 'Y', 'ACTIVE_DATE' => 'Y'), $filter),
						false,
						false,
						$select
					)
					->fetch();

				if ($result === false)
					$this->__status = 'Error. Execution result is empty'; // Результат выполнения пуст

				return $result;
			}

		$this->__status = 'Page does not apply to Article'; // Страница не относится к статьям/новостям/блогам

		return false;
	} // end function model

	protected function __onPrepareResult ($model) {
		$pictureId = $model['DETAIL_PICTURE'] ? $model['DETAIL_PICTURE'] : $model['PREVIEW_PICTURE'];
		$picture = $pictureId
						? \CFile::getById($pictureId)->fetch()
						: false;

		if ($picture) {
			$model['picture'] = array(
				'src' => \CFile::getPath($pictureId),
				'width' => $picture['WIDTH'],
				'height' => $picture['HEIGHT']
			);
		}

		$Business = Business::getInstance();
		$organization = $Business->getModel();

		if ($this->__options['schema']['props']['author']) {
			$author = array(
				'name' => $model['PROPERTY_'.$this->__options['schema']['props']['author'].'_VALUE']
			);

		} elseif ($model['CREATED_BY']) {
			$user = Main\UserTable::getList(array(
						'filter' => array('ID' => (int)$model['CREATED_BY']),
						'select' => array('ID', 'LOGIN', 'NAME', 'LAST_NAME')
					))
					->fetch();

			$author = array(
				'name' => $user['NAME'] || $user['LAST_NAME'] ? implode(' ', array($user['NAME'], $user['LAST_NAME'])) : $user['LOGIN']
			);
		}

		if ($organization['logo']) {
			$ss = getimagesize($organization['logo']);

			$organization['logo'] = array(
				'src' => $organization['logo'],
				'width' => $ss[0],
				'height' => $ss[1]
			);
		}

		$model['DETAIL_TEXT'] = strlen($model['DETAIL_TEXT']) > 0 ? preg_replace("/\s{2,}|\n/", ' ', htmlspecialcharsEx($model['DETAIL_TEXT'])) : false;
		$model['description'] = strlen($model['PREVIEW_TEXT']) > 0 ? preg_replace("/\s{2,}|\n/", ' ', htmlspecialcharsEx(strip_tags($model['PREVIEW_TEXT']))) : false;
		$model['organization'] = $organization;
		$model['author'] = $author;
		$model['wordCount'] = $model['DETAIL_TEXT']
								? str_word_count(preg_replace("/\s{2,}|\n/", ' ', htmlspecialcharsEx(strip_tags($model['DETAIL_TEXT']))))
								: false;

		return method_exists($this, ($method = '__getResult'.$this->__options['schema']['template']))
					? array_merge(
						array(
							'.template' => $this->__options['schema']['template'],
							'name' => $model['NAME'],
							'picture' => $model['picture'],
							'description' => $model['description'],
							'articleBody' => $model['DETAIL_TEXT'] ? $model['DETAIL_TEXT'] : false,
							'dateCreate' => BxType\DateTime::createFromTimestamp($model['DATE_CREATE_UNIX']),
							'dateModify' => BxType\DateTime::createFromTimestamp($model['TIMESTAMP_X_UNIX']),
							'organization' => $model['organization'],
							'author' => $model['author']
						),
						$this->{$method}($model)
					)
					: false;
	} // end function __onPrepareResult

	private function __getResultArticle ($model) {
		return array(
			'wordCount' => $model['DETAIL_TEXT'] ? $model['wordCount'] : false
		);
	} // end function __getResultArticle

	private function __getResultNewsArticle ($model) {
		return array(
			'wordCount' => $model['DETAIL_TEXT'] ? $model['wordCount'] : false,
			'dateline' => $this->__options['schema']['props']['dateline'] ? $model['PROPERTY_'.$this->__options['schema']['props']['dateline'].'_VALUE'] : false
		);
	} // end function __getResultNewsArticle

	private function __getResultTechArticle ($model) {
		return array(
			'dependencies' => $this->__options['schema']['props']['dependencies'] ? $model['PROPERTY_'.$this->__options['schema']['props']['dependencies'].'_VALUE'] : false,
			'proficiencyLevel' => $this->__options['schema']['props']['proficiencyLevel'] ? $model['PROPERTY_'.$this->__options['schema']['props']['proficiencyLevel'].'_VALUE'] : false
		);
	} // end function __getResultTechArticle

	private function __getResultBlogPosting ($model) {
		return array();
	} // end function __getResultBlogPosting

	public function getName() { return self::NAME;}
} // end class Product