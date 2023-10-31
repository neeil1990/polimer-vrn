<?php

namespace Yandex\Market\Component\Setup;

use Bitrix\Main;
use Yandex\Market;

Main\Localization\Loc::loadMessages(__FILE__);

class EditForm extends Market\Component\Model\EditForm
{
	protected $uiService;
	protected $repository;

	public function modifyRequest($request, $fields)
	{
		$result = parent::modifyRequest($request, $fields);
		$hasIblockRequest = isset($request['IBLOCK']);
		$hasIblockLinkRequest = isset($request['IBLOCK_LINK']);

		if ($hasIblockRequest || $hasIblockLinkRequest)
		{
			$iblockIds = $hasIblockRequest ? (array)$request['IBLOCK'] : [];
			$iblockIdsMap = array_flip($iblockIds);
			$usedIblockIds = [];
			$result['IBLOCK_LINK'] = $hasIblockLinkRequest ? (array)$request['IBLOCK_LINK'] : [];

			foreach ($result['IBLOCK_LINK'] as $iblockLinkKey => $iblockLink)
			{
				$iblockId = (int)$iblockLink['IBLOCK_ID'];

				if ($iblockId > 0 && isset($iblockIdsMap[$iblockId]))
				{
					$usedIblockIds[$iblockId] = true;
				}
				else
				{
					unset($result['IBLOCK_LINK'][$iblockLinkKey]);
				}
			}

			foreach ($iblockIds as $iblockId)
			{
				if (!isset($usedIblockIds[$iblockId]))
				{
					$result['IBLOCK_LINK'][] = [
						'IBLOCK_ID' => $iblockId
					];
				}
			}
		}

		return $result;
	}

	public function load($primary, array $select = [], $isCopy = false)
	{
		$result = parent::load($primary, $select, $isCopy);

		$this->parseParamField($result);

		if ($isCopy)
		{
			$copyNameMarker = (string)Market\Config::getLang('COMPONENT_SETUP_EDIT_FORM_COPY_NAME_MARKER', null, '');

			if (
				$copyNameMarker !== ''
				&& isset($result['NAME'])
				&& Market\Data\TextString::getPositionCaseInsensitive($result['NAME'], $copyNameMarker) === false
			)
			{
				$result['NAME'] .= ' ' . $copyNameMarker;
			}

			if (isset($result['FILE_NAME']))
			{
				$result['FILE_NAME'] = null;
			}
		}

		return $result;
	}

	public function validate($data, array $fields = null)
	{
		$result = parent::validate($data, $fields);

		$this->validateIblock($result, $data, $fields);
		$this->validateDelivery($result, $data, $fields);
		$this->validateFilterCondition($result, $data, $fields);
		$this->validateFilterDistinct($result, $data, $fields);

		return $result;
	}

	protected function validateIblock(Main\Entity\Result $result, $data, array $fields = null)
	{
		if (empty($data['IBLOCK_LINK']))
		{
			$result->addError(new Market\Error\EntityError(
				Market\Config::getLang('COMPONENT_SETUP_EDIT_FORM_ERROR_IBLOCK_EMPTY'),
				0,
				[ 'FIELD' => 'IBLOCK' ]
			));
		}
	}

	protected function validateDelivery(Main\Entity\Result $result, $data, array $fields = null)
	{
		if (isset($fields['DELIVERY'])) // has delivery in validation list
		{
			$deliveryTypeList = [
				Market\Export\Delivery\Table::DELIVERY_TYPE_DELIVERY
			];

			foreach ($deliveryTypeList as $deliveryType)
			{
				if (empty($data['DELIVERY']) || !$this->isValidDeliveryDataList($data['DELIVERY'], $deliveryType)) // and empty primary delivery
				{
					$childWithDeliveryOptions = null;

					foreach ($data['IBLOCK_LINK'] as $iblockLink)
					{
						// has in param

						if (!empty($iblockLink['PARAM']))
						{
							foreach ($iblockLink['PARAM'] as $tagDescription)
							{
								if (!empty($tagDescription['PARAM_VALUE']) && $tagDescription['XML_TAG'] === 'delivery-options')
								{
									foreach ($tagDescription['PARAM_VALUE'] as $tagValue)
									{
										if (!empty($tagValue['SOURCE_TYPE']) && !empty($tagValue['SOURCE_FIELD']))
										{
											$childWithDeliveryOptions = 'PARAM';
											break 3;
										}
									}
								}
							}
						}

						// has in filter

						if (!empty($iblockLink['DELIVERY']) && $this->isValidDeliveryDataList($iblockLink['DELIVERY'], $deliveryType))
						{
							$childWithDeliveryOptions = 'FILTER';
							break;
						}
						else if (!empty($iblockLink['FILTER']))
						{
							foreach ($iblockLink['FILTER'] as $filter)
							{
								if (!empty($filter['DELIVERY']) && $this->isValidDeliveryDataList($filter['DELIVERY'], $deliveryType))
								{
									$childWithDeliveryOptions = 'FILTER';
									break 2;
								}
							}
						}
					}

					if ($childWithDeliveryOptions !== null)
					{
						$result->addError(new Market\Error\EntityError(
							Market\Config::getLang('COMPONENT_SETUP_EDIT_FORM_ERROR_CHILD_DELIVERY_OPTIONS_WITHOUT_ROOT_BY_' . $childWithDeliveryOptions),
							0,
							[ 'FIELD' => 'DELIVERY' ]
						));
						break;
					}
				}
			}
		}
	}

	protected function validateFilterCondition(Main\Entity\Result $result, $data, array $fields = null)
	{
		if (!empty($data['IBLOCK_LINK']))
		{
			$isNeedExportAllValidation = false;
			$hasNotEmptyIblockLink = false;

			foreach ($data['IBLOCK_LINK'] as $iblockLinkIndex => $iblockLink)
			{
				$filterFieldName = 'IBLOCK_LINK_' . $iblockLinkIndex . '_FILTER';
				$filterInputName = 'IBLOCK_LINK[' . $iblockLinkIndex . '][FILTER]';
				$exportAllFieldName = 'IBLOCK_LINK_' . $iblockLinkIndex . '_EXPORT_ALL';
				$hasNotEmptyFilter = false;

				if (isset($fields[$filterFieldName]) && !empty($iblockLink['FILTER']))
				{
					foreach ($iblockLink['FILTER'] as $filterIndex => $filter)
					{
						$hasValidCondition = false;

						if (!empty($filter['FILTER_CONDITION']))
						{
							foreach ($filter['FILTER_CONDITION'] as $filterCondition)
							{
								if (Market\Export\FilterCondition\Table::isValidData($filterCondition))
								{
									$hasValidCondition = true;
									break;
								}
							}
						}

						if ($hasValidCondition)
						{
							$hasNotEmptyFilter = true;
						}
						else
						{
							$result->addError(new Market\Error\EntityError(
								Market\Config::getLang('COMPONENT_SETUP_EDIT_FORM_ERROR_FILTER_CONDITION_EMPTY'),
								0,
								[ 'FIELD' => $filterInputName ]
							));
							break 2;
						}
					}
				}

				if (isset($fields[$exportAllFieldName]))
				{
					$isNeedExportAllValidation = true;
					$isExportAll = (!empty($iblockLink['EXPORT_ALL']) && (string)$iblockLink['EXPORT_ALL'] === Market\Export\Setup\Table::BOOLEAN_Y);

					if ($isExportAll || $hasNotEmptyFilter)
					{
						$hasNotEmptyIblockLink = true;
					}
				}
			}

			if ($isNeedExportAllValidation && !$hasNotEmptyIblockLink)
			{
				$result->addError(new Market\Error\EntityError(
					Market\Config::getLang('COMPONENT_SETUP_EDIT_FORM_ERROR_IBLOCK_LINK_EXPORT_EMPTY')
				));
			}
		}
	}

	protected function validateFilterDistinct(Main\Entity\Result $result, $data, array $fields = null)
	{
		if (!empty($data['IBLOCK_LINK']))
		{
			foreach ($data['IBLOCK_LINK'] as $iblockLinkIndex => $iblockLink)
			{
				$filterFieldName = 'IBLOCK_LINK_' . $iblockLinkIndex . '_FILTER';
				$filterInputName = 'IBLOCK_LINK[' . $iblockLinkIndex . '][FILTER]';

				if (!isset($fields[$filterFieldName]) || empty($iblockLink['FILTER'])) { continue; }

				foreach ($iblockLink['FILTER'] as $filterRow)
				{
					if (empty($filterRow['FILTER_CONDITION'])) { continue; }

					$filter = Market\Export\Filter\Model::initialize($filterRow);
					$sourceFilter = $filter->getSourceFilter();

					foreach ($sourceFilter as $type => $filter)
					{
						$source = Market\Export\Entity\Manager::getSource($type);
						$queryFilter = $source->getQueryFilter($filter, []);

						if (empty($queryFilter['DISTINCT'])) { continue; }

						foreach ($queryFilter['DISTINCT'] as $distinctRule)
						{
							$isValid = true;
							$ruleType = null;

							if (isset($distinctRule['TAG'], $distinctRule['ATTRIBUTE']))
							{
								$isValid = $this->hasFilledTagSource($iblockLink, $distinctRule['TAG'], $distinctRule['ATTRIBUTE']);
								$ruleType = 'ATTRIBUTE';
							}
							else if (isset($distinctRule['TAG']))
							{
								$isValid = $this->hasFilledTagSource($iblockLink, $distinctRule['TAG']);
								$ruleType = 'TAG';
							}

							if (!$isValid)
							{
								$result->addError(new Market\Error\EntityError(
									Market\Config::getLang('COMPONENT_SETUP_EDIT_FORM_ERROR_DISTINCT_REQUIRE_' . $ruleType, $distinctRule),
									0,
									[ 'FIELD' => $filterInputName ]
								));
							}
						}
					}
				}
			}
		}
	}

	public function add($fields)
	{
		$this->compileParamField($fields);
		$this->fillGroupField($fields);

		return parent::add($fields);
	}

	public function update($primary, $fields)
	{
		$this->compileParamField($fields);

		return parent::update($primary, $fields);
	}

	public function getFields(array $select = [], $item = null)
	{
		$result = parent::getFields($select, $item);

		if (isset($result['NAME']))
		{
			$result['NAME'] = $this->modifyNameField($result['NAME']);
		}

		if (isset($result['HTTPS']))
		{
			$result['HTTPS'] = $this->modifyHttpsField($result['HTTPS']);
		}

		if (isset($result['DOMAIN']))
		{
			$result['DOMAIN'] = $this->modifyDomainField($result['DOMAIN']);
		}

		if (isset($result['EXPORT_SERVICE'], $result['EXPORT_FORMAT']))
		{
			$result['EXPORT_SERVICE'] = $this->getRepository()->modifyExportServiceField($result['EXPORT_SERVICE']);
			$result['EXPORT_FORMAT'] = $this->getRepository()->modifyExportServiceField($result['EXPORT_FORMAT']);

			$result = $this->getRepository()->makeServiceDependFields($result);
		}

		return $result;
	}

	protected function modifyNameField(array $field)
	{
		$field['SETTINGS']['DEFAULT_VALUE'] = $this->getUiService()->getTitle();

		return $field;
	}

	protected function modifyHttpsField(array $field)
	{
		$request = Main\Context::getCurrent()->getRequest();

		$field['SETTINGS']['DEFAULT_VALUE'] = $request->isHttps()
			? Market\Ui\UserField\BooleanType::VALUE_Y
			: Market\Ui\UserField\BooleanType::VALUE_N;

		return $field;
	}

	protected function modifyDomainField(array $field)
	{
		$host = Main\Context::getCurrent()->getRequest()->getHttpHost();
		$siteId = Market\Data\SiteDomain::getSite($host);

		if ($siteId !== null && !Market\Data\Site::isCrm($siteId))
		{
			$field['SETTINGS']['DEFAULT_VALUE'] = $host . $this->siteDomainDirectory($siteId);
		}
		else
		{
			$found = null;

			foreach (Market\Data\Site::getVariants() as $variant)
			{
				if (Market\Data\Site::isCrm($variant)) { continue; }

				$variantHost = (string)Market\Data\SiteDomain::getHost($variant);

				if ($variantHost === '') { continue; }

				$found = $variantHost . $this->siteDomainDirectory($variant);
				break;
			}

			$field['SETTINGS']['DEFAULT_VALUE'] = $found !== null ? $found : $host;
		}

		return $field;
	}

	protected function siteDomainDirectory($siteId)
	{
		list(, $dir) = Market\Data\Site::getDocumentRoot($siteId);

		return $dir !== '/' ? $dir : '';
	}

	public function extend($data, array $select = [])
	{
		$result = $data;

		if (!isset($result['FILE_NAME']) || trim($result['FILE_NAME']) === '')
		{
			$result['FILE_NAME'] = 'export_' . randString(3) . '.xml';
		}

		if (!empty($result['IBLOCK_LINK']))
		{
			$setup = $this->loadSetupModel($data);
			$setupContext = $setup->getContext();

			foreach ($result['IBLOCK_LINK'] as &$iblockLink)
			{
				$iblockId = isset($iblockLink['IBLOCK_ID']) ? (int)$iblockLink['IBLOCK_ID'] : null;
				$iblockLink['CONTEXT'] = Market\Export\Entity\Iblock\Provider::getContext($iblockId) + $setupContext;
			}
			unset($iblockLink);
		}

		return $result;
	}

	public function processAjaxAction($action, $data)
	{
		$result = null;

		switch ($action)
		{
			case 'filterCount':
				session_write_close(); // release sessions

				$result = $this->ajaxActionFilterCount($data);
			break;

			default:
				$result = parent::processAjaxAction($action, $data);
			break;
		}

		return $result;
	}

	public function ajaxActionFilterCount($data, $baseName = 'IBLOCK_LINK', $step = Market\Export\Run\Manager::STEP_OFFER)
	{
		$request = Main\Context::getCurrent()->getRequest();

		$setup = $this->loadSetupModel($data);
		$offset = null;
		$offsetName = $request->getPost('offsetName');

		if ($offsetName !== null && preg_match('/^' . $baseName . '\[(\d+)\]\[FILTER\](?:\[(\d+)\])?/', $offsetName, $offsetNameMatches))
		{
			$offset = $offsetNameMatches[1] . (isset($offsetNameMatches[2]) ? ':' . $offsetNameMatches[2] : '');
		}

		return [ 'status' => 'ok' ] + $this->getFilterCount($setup, $offset, $baseName, $step);
	}

	public function getFilterCount(Market\Export\Setup\Model $setup, $offset = null, $baseName = 'IBLOCK_LINK', $step = Market\Export\Run\Manager::STEP_OFFER)
	{
		/** @var $offerStep Market\Export\Run\Steps\Offer */
		$processor = new Market\Export\Run\Processor($setup);
		$offerStep = Market\Export\Run\Manager::getStepProvider($step, $processor);

		$processor->loadModules();

		$filterCountList = $offerStep->getCount($offset, true);
		$iblockLinkIndex = (int)$offset;
		$filterIndex = 0;
		$result = [
			'countList' => [],
			'warningList' => []
		];

		foreach ($filterCountList->getCountList() as $countKey => $count)
		{
			$countKeyParts = explode(':', $countKey);

			if (count($countKeyParts) === 2) // is filter
			{
				$inputName = $baseName . '[' . $iblockLinkIndex . '][FILTER][' . $filterIndex . '][FILTER_CONDITION]';

				++$filterIndex;
			}
			else
			{
				$inputName = $baseName . '[' . $iblockLinkIndex . '][FILTER]';

				$filterIndex = 0;
				++$iblockLinkIndex;
			}

			$warning = $filterCountList->getCountWarning($countKey);

			$result['countList'][$inputName] = $count;
			$result['warningList'][$inputName] = $warning ? $warning->getMessage() : null;
		}

		return $result;
	}

	/**
	 * @param $data
	 *
	 * @return Market\Export\Setup\Model
	 */
	protected function loadSetupModel($data)
	{
		/** @var \Yandex\Market\Export\Setup\Model $modelClassName */
		$modelClassName = $this->getModelClass();

		return $modelClassName::initialize($data);
	}

	protected function isValidDeliveryDataList($dataList, $deliveryType)
	{
		$result = false;

		if (is_array($dataList))
		{
			foreach ($dataList as $data)
			{
				$isMatchType = (isset($data['DELIVERY_TYPE']) && $data['DELIVERY_TYPE'] === $deliveryType);

				if ($isMatchType && Market\Export\Delivery\Table::isValidData($data))
				{
					$result = true;
					break;
				}
			}
		}

		return $result;
	}

	protected function hasFilledTagSource($iblockLink, $tagName, $attributeName = null)
	{
		$result = false;

		if (!empty($iblockLink['PARAM']))
		{
			foreach ($iblockLink['PARAM'] as $param)
			{
				if ($param['XML_TAG'] !== $tagName || empty($param['PARAM_VALUE'])) { continue; }

				foreach ($param['PARAM_VALUE'] as $paramValue)
				{
					if ($attributeName !== null)
					{
						$isMatch = (
							$paramValue['XML_TYPE'] === 'attribute'
							&& $paramValue['XML_ATTRIBUTE_NAME'] === $attributeName
						);
					}
					else
					{
						$isMatch = $paramValue['XML_TYPE'] === 'value';
					}

					if (
						$isMatch
						&& isset($paramValue['SOURCE_TYPE'], $paramValue['SOURCE_FIELD'])
						&& (string)$paramValue['SOURCE_TYPE'] !== ''
						&& (string)$paramValue['SOURCE_FIELD'] !== ''
					)
					{
						$result = true;
						break 2;
					}
				}
			}
		}

		return $result;
	}

	protected function getUiService()
	{
		if ($this->uiService === null)
		{
			$this->uiService = $this->loadUiService();
		}

		return $this->uiService;
	}

	protected function loadUiService()
	{
		$serviceName = (string)$this->getComponentParam('SERVICE');

		if ($serviceName === '')
		{
			$result = Market\Ui\Service\Manager::getCommonInstance();
		}
		else
		{
			$result = Market\Ui\Service\Manager::getInstance($serviceName);
		}

		return $result;
	}

	protected function parseParamField(&$setupRow)
	{
		if (!isset($setupRow['IBLOCK_LINK'])) { return; }

		foreach ($setupRow['IBLOCK_LINK'] as &$iblockLink)
		{
			if (!isset($iblockLink['PARAM'])) { continue; }

			foreach ($iblockLink['PARAM'] as &$param)
			{
				if (!empty($param['SETTINGS']))
				{
					$param['SETTINGS'] = $this->parseParamSettings($param['SETTINGS']);
				}

				if (isset($param['PARAM_VALUE']))
				{
					foreach ($param['PARAM_VALUE'] as &$paramValue)
					{
						if (!isset($paramValue['SOURCE_TYPE'], $paramValue['SOURCE_FIELD'])) { continue; }

						$paramValue['SOURCE_FIELD'] = $this->parseSourceField($paramValue['SOURCE_TYPE'], $paramValue['SOURCE_FIELD']);
					}
					unset($paramValue);
				}
			}
			unset($param);
		}
		unset($iblockLink);
	}

	protected function compileParamField(&$setupRow)
	{
		if (!isset($setupRow['IBLOCK_LINK'])) { return; }

		foreach ($setupRow['IBLOCK_LINK'] as &$iblockLink)
		{
			if (!isset($iblockLink['PARAM'])) { continue; }

			foreach ($iblockLink['PARAM'] as &$param)
			{
				$param['SETTINGS'] = !empty($param['SETTINGS'])
					? $this->compileParamSettings($param['SETTINGS'])
					: null;

				if (isset($param['PARAM_VALUE']))
				{
					foreach ($param['PARAM_VALUE'] as &$paramValue)
					{
						if (!isset($paramValue['SOURCE_TYPE'], $paramValue['SOURCE_FIELD'])) { continue; }

						$paramValue['SOURCE_FIELD'] = $this->compileSourceField($paramValue['SOURCE_TYPE'], $paramValue['SOURCE_FIELD']);
					}
					unset($paramValue);
				}
			}
			unset($param);
		}
		unset($iblockLink);
	}

	protected function compileParamSettings($settings)
	{
		foreach ($settings as &$setting)
		{
			if (!isset($setting['TYPE'], $setting['FIELD'])) { continue; }

			$setting['FIELD'] = $this->compileSourceField($setting['TYPE'], $setting['FIELD']);
		}
		unset($setting);

		return $settings;
	}

	protected function parseParamSettings($settings)
	{
		foreach ($settings as &$setting)
		{
			if (!isset($setting['TYPE'], $setting['FIELD'])) { continue; }

			$setting['FIELD'] = $this->parseSourceField($setting['TYPE'], $setting['FIELD']);
		}
		unset($setting);

		return $settings;
	}

	protected function compileSourceField($type, $field)
	{
		try
		{
			$source = Market\Export\Entity\Manager::getSource($type);

			if ($source instanceof Market\Export\Entity\Reference\HasFieldCompilation)
			{
				$result = $source->compileField($field);
			}
			else
			{
				$result = $field;
			}
		}
		catch (Main\ObjectNotFoundException $exception)
		{
			$result = $field;
		}

		return $result;
	}

	protected function parseSourceField($type, $field)
	{
		try
		{
			$source = Market\Export\Entity\Manager::getSource($type);

			if ($source instanceof Market\Export\Entity\Reference\HasFieldCompilation)
			{
				$result = $source->parseField($field);
			}
			else
			{
				$result = $field;
			}
		}
		catch (Main\ObjectNotFoundException $exception)
		{
			$result = $field;
		}

		return $result;
	}

	protected function fillGroupField(&$fields)
	{
		$groupId = (int)$this->getComponentParam('GROUP');

		if ($groupId > 0)
		{
			$fields['GROUP'] = [ $groupId ];
		}
	}

	protected function getRepository()
	{
		if ($this->repository === null)
		{
			$this->repository = $this->makeRepository();
		}

		return $this->repository;
	}

	protected function makeRepository()
	{
		$uiService = $this->getUiService();
		$modelClass = $this->getModelClass();

		return new Repository($uiService, $modelClass);
	}
}