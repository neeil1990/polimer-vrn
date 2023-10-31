<?
namespace Zverushki\Microm\Reading;

use Bitrix\Main\Data,
	Bitrix\Main\Application,
	Zverushki\Microm;


/**
* class Type
*
*
* @package Zverushki\Microm\Reading\Type
*/
abstract class Type {
	protected $__options;
	protected $__template = null;
	protected $__status = '';
	protected $siteId = false;
	private $model = false;
	private $__startCache = false;
	private $__cacheParams = array(
		'time' => 86400,
		'id' => '',
		'dir' => '/zverushki/microm',
		'tag' => ''
	);

	protected function __construct ($typeName, $options) {
		$this->__options = $options;
		$this->__template = new Microm\Template($typeName);

		$Option = Microm\Options::entity();

		$this->siteId = $Option->getSiteId();
		$this->__cacheParams['version'] = $Option->getVersion();
		$this->__cacheParams['siteId'] = $this->siteId;
	}

	abstract protected function model ();
	abstract protected function __onPrepareResult ($model);
	abstract public function getName ();


	/**
	 * Устанавливает флаг старта кэширования данных
	 *
	 * @param array $params - параметры кэширования
	 * @return array - данные с кэша
	 */
	protected function startCache ($params = array()) {
		$this->__startCache = true;

		foreach ($params as $key => $v)
			if (array_key_exists($key, $this->__cacheParams))
				$this->__cacheParams[$key] = $v;

		$this->__cacheParams['requestUri'] = $GLOBALS['APPLICATION']->GetCurUri('', false);
		// $this->__cacheParams['dir'] .= '/'.$this->getName();
		$this->__cacheParams['id'] = md5(serialize(array($this->__options, $this->__cacheParams)));
	}

	protected function addKeyCache ($key, $val) {
		if (array_key_exists($key, $this->__cacheParams)) {
			$this->__cacheParams[$key] = $val;
			$this->__cacheParams['id'] = md5(serialize(array($this->__options, $this->__cacheParams)));
		}
	}

	/**
	 * Возвращает данные схемы
	 * @return array - данные
	 */
	private function __getData () {
		$model = $this->model();

		if ($model === false)
			return false;

		$this->model = $this->__onPrepareResult($model);

		if (true) {
			$templateName = $this->model['.template'];
			$options = [];
			unset($this->model['.template']);

			if ($model['IBLOCK_ID']) {
				$options['CIBlockElement'] = [
					'ID' => $model['ID'],
					'IBLOCK_ID' => $model['IBLOCK_ID'],
					'CODE' => $model['CODE']
				];
			}

			$Events = GetModuleEvents('zverushki.microm', 'OnData');

			while (($E = $Events->fetch()) !== false) {
				if (ExecuteModuleEventEx($E, [static::NAME, &$this->model, $options]) === false)
					return false;
			}

			$this->model['.template'] = $templateName;
		}

		return $this->model;
	}

	/**
	 * Возвращает кэшированые данные схемы
	 * @return array - данные
	 */
	private function __getCachedData () {
		$сache = Data\Cache::createInstance();

		$cacheManager = Application::getInstance()
							->getTaggedCache();

		if ($сache->initCache($this->__cacheParams['time'], $this->__cacheParams['id'], $this->__cacheParams['dir'])) {
			list($this->__status, $result) = $сache->getVars();

		} elseif ($сache->startDataCache()) {
			$cacheManager->startTagCache($this->__cacheParams['dir']);

			$result = $this->__getData();

			if ($this->__cacheParams['tag'])
				$cacheManager->registerTag($this->__cacheParams['tag']);

			$cacheManager->registerTag('micromCache');
			$cacheManager->registerTag($this->getName());

			$cacheManager->endTagCache();

			// if ($result === false)
			// 	$cache->abortDataCache();

			$сache->endDataCache(array(
				($this->__status ? $this->__status.': '.$GLOBALS['APPLICATION']->GetCurUri('', false) : ''),
				$result
			));
		}

		return $result;
	}

	/**
	 * Выполнение шаблона
	 *
	 * @return application/ld+json || microdata
	 */
	protected function __getTemplate ($format = '') {
		$model = $this->__startCache !== true
					? $this->__getData()
					: $this->__getCachedData();

		if ($model === false)
			return false;

		if (array_key_exists('.template', $model) && $model['.template']) {
			$template = $model['.template'];
			unset($model['.template']);

		} else
			$template = 'index';

		return $this->__template
					->getResult($template.($format ? '.'.$format : ''), $model);
	}

	public function getModel () { return $this->model;}


	/**
	 * Формирование данных отработаного шаблона
	 *
	 * @return array - тип данных, html(template)
	 */
	public function get () {
		if (!$this->__template->isExists())
			return false;

		$result = array(
			'key' => $this->getName(),
			'status' => $this->__status
		);

		$Options = Microm\Options::entity();

		foreach ($Options->settingsFormat() as $format)
			$result['template.'.$format] = $this->__getTemplate($format);

		return $result;
	}

}
?>