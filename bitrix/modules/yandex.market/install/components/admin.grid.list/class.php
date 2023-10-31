<?php

namespace Yandex\Market\Components;

use Bitrix\Main;
use Yandex\Market;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class AdminGridList extends \CBitrixComponent
{
    protected static $langPrefix = 'YANDEX_MARKET_GRID_LIST_';

    /** @var \Yandex\Market\Component\Base\GridList */
    protected $provider;
    protected $viewList;
    protected $viewFilter;
    protected $viewSort;

    public function onPrepareComponentParams($params)
    {
        $params['GRID_ID'] = trim($params['GRID_ID']);
        $params['SUBLIST'] = ($params['SUBLIST'] === 'Y');
        $params['SUBLIST_TARGET'] = ($params['SUBLIST_TARGET'] === 'Y');
        $params['USE_FILTER'] = (!$params['SUBLIST'] && $params['USE_FILTER'] !== 'N');
        $params['LIST_FIELDS'] = (array)$params['LIST_FIELDS'];
        $params['FILTER_FIELDS'] = (array)$params['FILTER_FIELDS'];
        $params['DEFAULT_LIST_FIELDS'] = (array)$params['DEFAULT_LIST_FIELDS'];
        $params['DEFAULT_FILTER_FIELDS'] = (array)$params['DEFAULT_FILTER_FIELDS'];
        $params['CONTEXT_MENU'] = (array)$params['CONTEXT_MENU'];
        $params['CONTEXT_MENU_EXCEL'] = ($params['CONTEXT_MENU_EXCEL'] === 'Y');
        $params['CONTEXT_MENU_SETTINGS'] = ($params['CONTEXT_MENU_SETTINGS'] !== 'N');
        $params['TITLE'] = trim($params['TITLE']);
        $params['NAV_TITLE'] = trim($params['NAV_TITLE']);
        $params['EDIT_URL'] = trim($params['EDIT_URL']);
        $params['ROW_ACTIONS'] = (array)$params['ROW_ACTIONS'];
        $params['GROUP_ACTIONS'] = (array)$params['GROUP_ACTIONS'];
        $params['GROUP_ACTIONS_PARAMS'] = (array)$params['GROUP_ACTIONS_PARAMS'];
        $params['UI_GROUP_ACTIONS'] = isset($params['UI_GROUP_ACTIONS']) ? (array)$params['UI_GROUP_ACTIONS'] : null;
        $params['UI_GROUP_ACTIONS_PARAMS'] = isset($params['UI_GROUP_ACTIONS_PARAMS']) ? (array)$params['UI_GROUP_ACTIONS_PARAMS'] : null;
        $params['PRIMARY'] = !empty($params['PRIMARY']) ? (array)$params['PRIMARY'] : [ 'ID' ];
		$params['ALLOW_SAVE'] = isset($params['ALLOW_SAVE']) ? (bool)$params['ALLOW_SAVE'] : true;
		$params['PAGER_LIMIT'] = isset($params['PAGER_LIMIT']) ? (int)$params['PAGER_LIMIT'] : null;
		$params['PAGER_FIXED'] = isset($params['PAGER_FIXED']) ? (int)$params['PAGER_FIXED'] : null;

        $params['PROVIDER_TYPE'] = trim($params['PROVIDER_TYPE']);

        $provider = $this->getProvider($params['PROVIDER_TYPE']);

        $params = $provider->prepareComponentParams($params);

        return $params;
    }

    public function executeComponent()
    {
        global $APPLICATION;

        $this->initResult();

        if (!$this->checkParams() || !$this->loadModules() || !$this->loadAdminLib())
        {
            $this->showErrors();
            return;
        }

        $hasCriticalError = false;

        try
        {
			$this->loadMessages();
	        $this->loadFields();
	        $this->loadFilter();

	        if ($this->canHandleRequest() && ($this->processAction() || $this->hasAjaxRequest()))
	        {
	            $APPLICATION->RestartBuffer();
	        }

            $this->buildHeaders();

	        $queryParams = [];
	        $queryParams += $this->initFilter();
	        $queryParams += $this->initSelect();
	        $queryParams += $this->initPager($queryParams);
	        $queryParams += $this->initSort();

	        if ($this->canHandleRequest() && $this->hasPostAction())
	        {
		        $this->processPostAction($queryParams);
	        }

	        if ($this->isExportMode())
	        {
				$this->loadAll($queryParams);
	        }
	        else
	        {
		        $this->loadItems($queryParams);

		        if ($this->isNeedResetQueryParams($queryParams))
		        {
					$queryParams = $this->resetQueryParams($queryParams);
			        $this->loadItems($queryParams);
		        }
	        }

	        $this->buildContextMenu();
	        $this->buildRows();
			$this->buildNavString($queryParams);
			$this->buildGroupActions();
        }
	    catch (Main\SystemException $exception)
	    {
	        $hasCriticalError = true;
	        $this->addError($exception->getMessage());

			$this->arResult['EXCEPTION_MIGRATION'] = Market\Migration\Controller::canRestore($exception);
	    }

	    $this->setTitle();

		if ($hasCriticalError)
		{
			$this->includeComponentTemplate('exception');
		}
		else
		{
			$this->resolveTemplateName();
			$this->includeComponentTemplate();
	    }
    }

    protected function canHandleRequest()
    {
        return (
            !$this->arParams['SUBLIST']
            || $this->arParams['SUBLIST_TARGET']
        );
    }

    protected function processAction()
    {
        $viewList = $this->getViewList();
	    $ids = $viewList->GroupAction();
	    $action = $ids ? $this->getViewListAction($viewList) : null;
        $result = false;

		if ($action !== null)
		{
			$result = true;

	        try
	        {
	        	if (!$this->arParams['ALLOW_SAVE'])
				{
					throw new Main\SystemException($this->getLang('ACTION_DISALLOW'));
				}

	            $actionData = [
	                'ID' => $ids,
	                'IS_ALL' => false
	            ];

	            if ($this->isViewListActionToAll($viewList))
	            {
	                $filter = $this->initFilter();

	                $actionData['IS_ALL'] = true;
	                $actionData['FILTER'] = isset($filter['filter']) ? $filter['filter'] : null;
	            }

	            $provider = $this->getProvider();
	            $provider->processAjaxAction($action, $actionData);
	        }
	        catch (Main\SystemException $exception)
	        {
	            $this->addError($exception->getMessage());
	        }
        }

        return $result;
    }

	protected function hasPostAction()
	{
		return ($this->getPostAction() !== null);
	}

	protected function getPostAction()
	{
		return $this->request->get('postAction');
	}

	protected function processPostAction($data)
	{
		try
		{
			$postAction = $this->getPostAction();
			$provider = $this->getProvider();

			$provider->processPostAction($postAction, $data);
		}
		catch (Main\SystemException $exception)
		{
			$this->addError($exception->getMessage());
		}
	}

    protected function getViewListAction(\CAdminList $viewList)
    {
    	$result = null;

	    if (method_exists($viewList, 'GetAction'))
	    {
		    $result = $viewList->GetAction();
	    }
	    else if (isset($_REQUEST['action_button']) && $_REQUEST['action_button'] !== '')
	    {
		    $result = $_REQUEST['action_button'];
	    }
	    else if (isset($_REQUEST['action']))
	    {
		    $result = $_REQUEST['action'];
	    }

	    return $result;
    }

    protected function isViewListActionToAll(\CAdminList $viewList)
    {
    	$result = false;
    	$uiGridRequestKey = 'action_all_rows_' . $viewList->table_id;

    	if (method_exists($viewList, 'IsGroupActionToAll'))
	    {
	    	$result = $viewList->IsGroupActionToAll();
	    }
    	else if (isset($_REQUEST['action_target']))
	    {
		    $result = ($_REQUEST['action_target'] === 'selected');
	    }
    	else if (isset($_REQUEST[$uiGridRequestKey]))
	    {
	    	$result = ($_REQUEST[$uiGridRequestKey] === 'Y');
	    }

    	return $result;
    }

    protected function hasAjaxRequest()
    {
    	$isTargetList = ($this->request->get('table_id') === $this->arParams['GRID_ID'] || !$this->isSubList());
    	$requestMode = $this->request->get('mode');

        return (
			$isTargetList
			&& (
				$requestMode === 'excel'
				|| ($this->request->isAjaxRequest() && $requestMode !== null)
			)
		);
    }

    protected function deleteItem($id)
    {
        $provider = $this->getProvider();

        $provider->deleteItem($id);
    }

    protected function initResult()
    {
        $this->arResult['CONTEXT_MENU'] = [];
        $this->arResult['FIELDS'] = [];
        $this->arResult['FILTER'] = [];
        $this->arResult['ITEMS'] = [];
        $this->arResult['TOTAL_COUNT'] = null;
        $this->arResult['MESSAGES'] = [];
        $this->arResult['ERRORS'] = [];
        $this->arResult['WARNINGS'] = [];
        $this->arResult['REDIRECT'] = null;
    }

    protected function getRequiredParams()
    {
        $provider = $this->getProvider();
        $result = [ 'GRID_ID' ] + $provider->getRequiredParams();

        return $result;
    }

    protected function checkParams()
    {
        $result = true;
        $requiredParams = $this->getRequiredParams();

        foreach ($requiredParams as $paramKey)
        {
            if (empty($this->arParams[ $paramKey ]))
            {
                $result = false;
                $message = $this->getLang('PARAM_REQUIRE', array(
                    '#PARAM#' => $paramKey
                ));

                $this->addError($message);
            }
        }

        return $result;
    }

    protected function getRequiredModules()
    {
        $provider = $this->getProvider();

        return $provider->getRequiredModules();
    }

    protected function loadModules()
    {
        $result = true;
        $modules = $this->getRequiredModules();

        foreach ($modules as $module)
        {
            if (!$this->loadModule($module))
            {
                $result = false;
            }
        }

        return $result;
    }

    protected function loadModule($module)
    {
        $result = true;

        if (!Main\Loader::includeModule($module))
        {
            $result = false;
            $message = $this->getLang('MODULE_REQUIRE', [
                '#MODULE#' => $module
            ]);

            $this->addError($message);
        }

        return $result;
    }

    protected function loadAdminLib()
    {
	    global $adminSidePanelHelper;

	    require_once Main\IO\Path::convertRelativeToAbsolute(BX_ROOT . '/modules/main/interface/admin_lib.php');

	    if (!is_object($adminSidePanelHelper) && class_exists(\CAdminSidePanelHelper::class))
	    {
		    $adminSidePanelHelper = new \CAdminSidePanelHelper();
	    }

	    return true;
    }

    public function setRedirectUrl($url)
	{
		$this->arResult['REDIRECT'] = $url;
	}

    public function addWarning($message)
    {
        $this->arResult['WARNINGS'][] = $message;
    }

    public function hasWarnings()
    {
        return !empty($this->arResult['WARNINGS']);
    }

    public function getWarnings()
    {
    	return $this->arResult['WARNINGS'];
    }

    public function showWarnings()
    {
        \CAdminMessage::ShowMessage([
            'TYPE' => 'ERROR',
            'MESSAGE' => implode('<br />', $this->arResult['WARNINGS']),
            'HTML' => true
        ]);
    }

    public function addError($message)
    {
        $this->arResult['ERRORS'][] = $message;
    }

    public function hasErrors()
    {
        return !empty($this->arResult['ERRORS']);
    }

    public function getErrors()
    {
    	return $this->arResult['ERRORS'];
    }

    public function showErrors()
    {
        \CAdminMessage::ShowMessage([
            'TYPE' => 'ERROR',
            'MESSAGE' => implode('<br />', $this->arResult['ERRORS']),
            'HTML' => true
        ]);
    }

	public function addMessage($message)
	{
		$this->arResult['MESSAGES'][] = $message;
	}

    public function hasMessages()
    {
        return !empty($this->arResult['MESSAGES']);
    }

    public function getMessages()
    {
    	return $this->arResult['MESSAGES'];
    }

    public function showMessages()
    {
		foreach ($this->arResult['MESSAGES'] as $message)
		{
			if (!is_array($message))
			{
				$message = [
					'TYPE' => 'OK',
					'MESSAGE' => $message,
					'HTML' => true,
				];
			}

	        \CAdminMessage::ShowMessage($message);
	    }
    }

    protected function setTitle()
    {
		global $APPLICATION;

        if ($this->arParams['TITLE'] !== '')
        {
            $APPLICATION->SetTitle($this->arParams['TITLE']);
        }
    }

	protected function loadMessages()
	{
		$types = [
			'MESSAGE' => 'addMessage',
			'ERROR' => 'addError',
			'WARNING' => 'addWarning',
		];

		foreach ($types as $type => $method)
		{
			$sessionKey = $this->arParams['GRID_ID'] . '_' . $type;

			if (empty($_SESSION[$sessionKey])) { continue; }

			$this->{$method}($_SESSION[$sessionKey]);
			unset($_SESSION[$sessionKey]);
		}
	}

    protected function loadFields()
    {
    	$provider = $this->getProvider();
        $select = $this->arParams['LIST_FIELDS'];

        $this->arResult['FIELDS'] = $provider->getFields($select);
    }

    protected function initFilter()
    {
        $provider = $this->getProvider();
        $defaultFilter = $provider->getDefaultFilter();
        $result = [];

		if (!empty($defaultFilter))
		{
			$result['filter'] = $defaultFilter;
		}
		else if (!empty($this->arParams['DEFAULT_FILTER']))
		{
			$result['filter'] = (array)$this->arParams['DEFAULT_FILTER'];
		}

        if (!$this->arParams['USE_FILTER'])
        {
            return $result;
        }

	    $listView = $this->getViewList();

        if ($listView instanceof \CAdminUiList)
        {
	        $result = $this->initFilterFromAdminList($listView, $result);
        }
        else
        {
        	$filterRequest = $listView->getFilter();
        	$result = $this->initFilterFromRequest($filterRequest, $result);
        }

        return $result;
    }

    protected function initFilterFromAdminList(\CAdminUiList $listView, array $defaultParameters)
    {
    	$listFilter = [];
    	$fieldsMap = array_column($this->arResult['FILTER'], 'fieldName', 'id');
    	$result = $defaultParameters;

	    $listView->AddFilter($this->arResult['FILTER'], $listFilter);

	    foreach ($listFilter as $filterKey => $filterValue)
	    {
	    	if (!preg_match('/^(.*?)(find_.+)$/', $filterKey, $matches)) { continue; }

	    	list(, $filterCompare, $filterId) = $matches;

	    	if (isset($fieldsMap[$filterId]))
		    {
		    	$filterField = $fieldsMap[$filterId];

			    if (!isset($result['filter']))
			    {
				    $result['filter'] = [];
			    }

			    $result['filter'][$filterCompare . $filterField] = $filterValue;
		    }
	    }

	    return $result;
    }

    protected function initFilterFromRequest($request, array $defaultParameters)
    {
        $result = $defaultParameters;

        foreach ($this->arResult['FILTER'] as &$filter)
        {
            switch ($filter['type'])
            {
	            case 'number':
	            case 'date':

	                $fromRequestKey = $filter['id'] . '_from';
	                $hasFromRequest = (isset($request[$fromRequestKey]) && $request[$fromRequestKey] !== '');
	                $toRequestKey = $filter['id'] . '_to';
	                $hasToRequest = (isset($request[$toRequestKey]) && $request[$toRequestKey] !== '');
	                $filter['value'] = [
                        'from' => $hasFromRequest ? htmlspecialcharsbx($request[$fromRequestKey]) : '',
                        'to' => $hasToRequest ? htmlspecialcharsbx($request[$toRequestKey]) : ''
                    ];

	                if ($hasFromRequest || $hasToRequest)
	                {
	                    if (!isset($result['filter']))
		                {
		                    $result['filter'] = [];
		                }

		                if ($hasFromRequest)
		                {
		                    $result['filter']['>=' . $filter['fieldName']] = $request[$fromRequestKey];
		                }

		                if ($hasToRequest)
		                {
		                    $result['filter']['<=' . $filter['fieldName']] = $request[$toRequestKey];
		                }
	                }

	            break;

	            default:

	                if (isset($request[$filter['id']]) && $request[$filter['id']] !== '')
		            {
		                $filterRequest = $request[$filter['id']];

		                $filter['value'] = htmlspecialcharsbx($filterRequest);

		                if (!isset($result['filter']))
		                {
		                    $result['filter'] = [];
		                }

		                $result['filter'][$filter['fieldName']] = $filterRequest;
		            }

	            break;
            }
        }
        unset($filter);

	    return $result;
    }

    protected function initSelect()
    {
        $view = $this->getViewList();

	    return [
            'select' => $view->GetVisibleHeaderColumns()
        ];
    }

    protected function initPager($queryParams)
    {
        $result = [];

        // size

        if ($this->isSubList())
        {
            if ($this->isSubListAjaxPage())
            {
                $this->fillEmptyPager();
            }

            $navSize = \CAdminSubResult::GetNavSize(
                $this->arParams['GRID_ID'],
                20,
                $this->arParams['AJAX_URL']
            );
        }
        else if ($this->useUiView())
        {
            $navSize = \CAdminUiResult::GetNavSize($this->arParams['GRID_ID']);
        }
        else
        {
            $navSize = \CAdminResult::GetNavSize($this->arParams['GRID_ID']);
        }

        if ($this->arParams['PAGER_LIMIT'] > 0 && $navSize > $this->arParams['PAGER_LIMIT'])
        {
	        $navSize = $this->arParams['PAGER_LIMIT'];
        }

	    if ($this->arParams['PAGER_FIXED'] !== null)
	    {
		    $navSize = $this->arParams['PAGER_FIXED'];
	    }

	    // nav params

	    if ($this->isBitrix24())
	    {
		    $navParams = [
			    'PAGEN' => $this->getBitrix24NavigationPage($navSize),
			    'SIZEN' => $navSize,
		    ];
	    }
	    else
	    {
		    $navParams = \CDBResult::GetNavParams($navSize);
	    }

	    // query parameters

	    if (!$navParams['SHOW_ALL'])
		{
			$page = (int)$navParams['PAGEN'];
			$pageSize = (int)$navParams['SIZEN'];

			$totalCount = $this->loadTotalCount($queryParams);

			if ($totalCount !== null)
			{
				$maxPageNum = max(1, ceil($totalCount / $pageSize));

				if ($page > $maxPageNum)
				{
					$page = $maxPageNum;
				}
			}

			$result['limit'] = $pageSize;
			$result['offset'] = $pageSize * ($page - 1);

			$this->arResult['TOTAL_COUNT'] = $totalCount;
		}

        return $result;
    }

    protected function fillEmptyPager()
    {
        global $NavNum;

        if ($NavNum === null) { $NavNum = 0; }

        for ($i = $NavNum + 1; $i < 10; $i++)
        {
            if (isset($_REQUEST['SIZEN_' . $i]))
            {
                $NavNum = $i - 1;
                break;
            }
        }
    }

    protected function getBitrix24NavigationPage($pageSize)
    {
	    $result = 1;

	    if ($this->request->get('apply_filter') !== 'Y')
	    {
		    $result = (int)$this->request->get('page');
	    }

	    if ($result > 0)
	    {
		    if (!isset($_SESSION['YAMARKET_PAGINATION_DATA']))
		    {
			    $_SESSION['YAMARKET_PAGINATION_DATA'] = [];
		    }

		    $_SESSION['YAMARKET_PAGINATION_DATA'][$this->arParams['GRID_ID']] = ['PAGEN' => $result, 'SIZEN' => $pageSize];
	    }
	    else
	    {
		    $sessionData = isset($_SESSION['YAMARKET_PAGINATION_DATA'][$this->arParams['GRID_ID']])
		        ? $_SESSION['YAMARKET_PAGINATION_DATA'][$this->arParams['GRID_ID']]
		        : null;

		    if (
			    isset($sessionData['PAGEN'], $sessionData['SIZEN'])
			    && (int)$sessionData['SIZEN'] === (int)$pageSize
			    && $this->request->get('clear_nav') !== 'Y'
		    )
		    {
				$result = (int)$sessionData['PAGEN'];
		    }
	    }

	    return max(1, $result);
    }

    protected function initSort()
    {
	    $viewSort = $this->getViewSort();
	    $order = null;

	    if (!empty($GLOBALS[$viewSort->by_name]))
	    {
	    	$sortField = Market\Data\TextString::toUpper($GLOBALS[$viewSort->by_name]);

	    	if (isset($this->arResult['FIELDS'][$sortField]))
		    {
		        $sortOrder = (
		            isset($GLOBALS[$viewSort->ord_name]) && Market\Data\TextString::toUpper($GLOBALS[$viewSort->ord_name]) === 'DESC'
		                ? 'DESC'
		                : 'ASC'
		        );

		        $order = [
		            $sortField => $sortOrder
		        ];
		    }
	    }

	    if ($order === null)
	    {
	        $provider = $this->getProvider();
	        $order = $provider->getDefaultSort();
	    }

	    return [
	        'order' => $order
	    ];
    }

    protected function isExportMode()
    {
    	$view = $this->getViewList();

    	return method_exists($view, 'isExportMode')
		    ? $view->isExportMode()
		    : (isset($_REQUEST['mode']) && $_REQUEST['mode'] === 'excel');
    }

    protected function loadAll($queryParams)
    {
	    try
	    {
		    $queryParams = array_diff_key($queryParams, [
		        'limit' => true,
			    'offset' => true,
		    ]);

	        if (isset($this->arParams['PAGER_LIMIT']))
		    {
			    $this->loadAllByPage($queryParams, $this->arParams['PAGER_LIMIT']);
		    }
	        else
		    {
		        $this->loadItems($queryParams);
		    }
	    }
	    catch (Main\SystemException $exception)
	    {
		    $this->addError($exception->getMessage());
		    $this->arResult['EXCEPTION_MIGRATION'] = Market\Migration\Controller::canRestore($exception);
	    }
    }

    protected function loadAllByPage($queryParams, $limit)
    {
    	$offset = 0;
    	$iterationCount = 0;
    	$iterationLimit = 50;

		do
		{
			$pageParams = [
				'offset' => $offset,
				'limit' => $limit,
			];

			$items = $this->queryItems($pageParams + $queryParams);

			if (!empty($items) && is_array($items))
			{
				array_push($this->arResult['ITEMS'], ...$items);
			}

			$offset += $limit;

			if ($this->arResult['TOTAL_COUNT'] !== null)
			{
				$hasNext = $this->arResult['TOTAL_COUNT'] > $offset;
			}
			else
			{
				$hasNext = !empty($items);
			}

			if (++$iterationCount > $iterationLimit) { break; }
		}
		while ($hasNext);
    }

    protected function loadItems($queryParams)
    {
	    try
	    {
		    $this->arResult['ITEMS'] = $this->queryItems($queryParams);
	    }
	    catch (Main\SystemException $exception)
	    {
	    	$this->addError($exception->getMessage());
		    $this->arResult['EXCEPTION_MIGRATION'] = Market\Migration\Controller::canRestore($exception);
	    }
    }

    protected function queryItems($queryParams)
    {
	    if (!empty($queryParams['select']))
	    {
		    $queryParams['select'] = array_merge(
			    $queryParams['select'],
			    $this->arParams['PRIMARY']
		    );
	    }

	    $queryResult = $this->getProvider()->load($queryParams);

	    if (isset($queryResult['ITEMS']))
	    {
		    $rows = $queryResult['ITEMS'];

		    if (isset($queryResult['TOTAL_COUNT']))
		    {
			    $this->arResult['TOTAL_COUNT'] = $queryResult['TOTAL_COUNT'];
		    }
	    }
	    else
	    {
		    $rows = $queryResult;
	    }

	    return $rows;
    }

    protected function isNeedResetQueryParams($queryParams)
    {
    	return (
    		empty($this->arResult['ITEMS'])
		    && $this->arResult['TOTAL_COUNT'] > 0
		    && $queryParams['offset'] >= $this->arResult['TOTAL_COUNT']
	    );
    }

    protected function resetQueryParams($queryParams)
    {
	    $queryParams['offset'] = 0;

	    return $queryParams;
    }

    protected function loadTotalCount($queryParams)
    {
        $provider = $this->getProvider();

        return $provider->loadTotalCount($queryParams);
    }

    protected function loadFilter()
    {
        if (!$this->arParams['USE_FILTER']) { return; }

        $useFieldsMap = array_flip($this->arParams['FILTER_FIELDS']);
        $defaultFieldsMap = array_flip($this->arParams['DEFAULT_FILTER_FIELDS']);
        $filterIdList = [];
        $filterDefaultIndexes = [];
        $filterIndex = 0;
        $useUiView = $this->useUiView();

        foreach ($this->arResult['FIELDS'] as $fieldName => $field)
        {
            if (
	            (!empty($useFieldsMap) && !isset($useFieldsMap[$fieldName]))
	            || (isset($field['FILTERABLE']) && $field['FILTERABLE'] === false)
	            || $field['USER_TYPE']['BASE_TYPE'] === 'file'
            )
            {
            	continue;
            }

			$hasClassName = !empty($field['USER_TYPE']['CLASS_NAME']);
            $item = [
                'id' => 'find_' . Market\Data\TextString::toLower($fieldName),
                'fieldName' => $fieldName,
                'value' => null,
                'name' => $this->getFirstNotEmpty($field, array('LIST_COLUMN_LABEL', 'EDIT_FORM_LABEL', 'LIST_FILTER_LABEL')),
                'type' => null,
	            'filterable' => '',
            ];

			if ($field['USER_TYPE']['BASE_TYPE'] === 'list' && !empty($field['VALUES']))
            {
	            $item['type'] = 'list';
	            $item['items'] = [];

	            foreach ($field['VALUES'] as $option)
	            {
	            	$item['items'][$option['ID']] = $option['VALUE'];
	            }

	            $filterIdList[] = $item['id'];
            }
			else if ($hasClassName && is_callable(array($field['USER_TYPE']['CLASS_NAME'], 'GetList')))
            {
                $item['type'] = 'list';
                $item['items'] = [];

                $query = call_user_func(array($field['USER_TYPE']['CLASS_NAME'], 'GetList'), $field);

                if (is_array($query))
                {
	                foreach ($query as $option)
	                {
		                $item['items'][$option['ID']] = $option['VALUE'];
	                }
                }
                else if ($query)
                {
	                while ($option = $query->Fetch())
	                {
		                $item['items'][$option['ID']] = $option['VALUE'];
	                }
                }

                $filterIdList[] = $item['id'];
            }
            else if ($field['USER_TYPE']['BASE_TYPE'] === 'datetime')
            {
                $item['type'] = 'date';

                $filterIdList[] = $item['id'] . '_from';
            	$filterIdList[] = $item['id'] . '_to';
            }
            else if ($field['USER_TYPE']['USER_TYPE_ID'] !== 'boolean' && in_array($field['USER_TYPE']['BASE_TYPE'], ['int', 'double'], true))
            {
            	$item['type'] = 'number';

            	$filterIdList[] = $item['id'] . '_from';
            	$filterIdList[] = $item['id'] . '_to';
            }
            else if ($hasClassName && !$useUiView && is_callable([$field['USER_TYPE']['CLASS_NAME'], 'GetFilterHTML']))
            {
                $item['type'] = 'custom';

                $filterIdList[] = $item['id'];
            }
            else if ($useUiView && $field['USER_TYPE']['USER_TYPE_ID'] === 'boolean')
            {
            	$item['type'] = 'list';
	            $item['items'] = [
	            	Market\Ui\UserField\BooleanType::VALUE_Y => Main\Localization\Loc::getMessage('MAIN_YES'),
		            Market\Ui\UserField\BooleanType::VALUE_N => Main\Localization\Loc::getMessage('MAIN_NO'),
	            ];
            }
            else
            {
                $item['type'] = 'string';

                $filterIdList[] = $item['id'];
            }

            $this->arResult['FILTER'][$fieldName] = $item;

            if (isset($defaultFieldsMap[$fieldName]))
            {
            	$filterDefaultIndexes[$fieldName] = $filterIndex;
            }

            ++$filterIndex;
        }

        if (empty($filterDefaultIndexes) && !empty($this->arResult['FILTER']))
        {
        	reset($this->arResult['FILTER']);

        	$firstKey = key($this->arResult['FILTER']);
        	$filterDefaultIndexes[$firstKey] = 0;
        }

        $this->getViewList()->InitFilter($filterIdList);

        if ($this->useUiView())
        {
        	foreach ($filterDefaultIndexes as $fieldName => $fieldIndex)
	        {
		        $this->arResult['FILTER'][$fieldName]['default'] = true;
	        }
        }
        else
        {
            $this->getViewFilter()->SetDefaultRows(array_values($filterDefaultIndexes));
        }
    }

    public function getFilterHtml($filter)
    {
        $field = $this->arResult['FIELDS'][$filter['fieldName']];

        return call_user_func(
            [ $field['USER_TYPE']['CLASS_NAME'], 'GetFilterHTML' ],
            $field,
			[
				'NAME' => $filter['id'],
				'VALUE' => $filter['value'],
				'TABLE_ID' => $this->arParams['GRID_ID'] . '_filter',
			]
		);
    }

	protected function buildContextMenu()
    {
    	$menuItems = isset($this->arParams['CONTEXT_MENU']) ? (array)$this->arParams['CONTEXT_MENU'] : [];
	    $menuItems = array_merge($menuItems, $this->provider->getContextMenu());

		if (
			!empty($menuItems)
			|| $this->arParams['CONTEXT_MENU_EXCEL']
			|| $this->arParams['CONTEXT_MENU_SETTINGS']
		)
		{
			$view = $this->getViewList();
			$view->AddAdminContextMenu($menuItems, $this->arParams['CONTEXT_MENU_EXCEL'], $this->arParams['CONTEXT_MENU_SETTINGS']);
		}
    }

    protected function buildHeaders()
    {
        $defaultFieldsMap = array_flip($this->arParams['DEFAULT_LIST_FIELDS']);
        $headers = [];
        $view = $this->getViewList();

        foreach ($this->arResult['FIELDS'] as $fieldName => $field)
        {
        	if (isset($field['SELECTABLE']) && $field['SELECTABLE'] === false) { continue; }

            $headers[$fieldName] = [
                'id' => $fieldName,
                'content' => $this->getFirstNotEmpty($field, array('LIST_COLUMN_LABEL', 'EDIT_FORM_LABEL', 'LIST_FILTER_LABEL')),
                'sort' => !isset($field['SORTABLE']) || $field['SORTABLE'] ? $fieldName : null,
                'first_order' => 'asc',
                'default' => empty($defaultFieldsMap) || isset($defaultFieldsMap[$fieldName])
            ];
        }

        $view->AddHeaders($headers);
    }

    protected function buildRows()
    {
        if (!empty($this->arResult['ITEMS']))
        {
            $view = $this->getViewList();
            $headers = $view->GetVisibleHeaderColumns();
            $provider = $this->getProvider();

            foreach ($this->arResult['ITEMS'] as $item)
            {
                $link = null;
                $actions = $this->buildRowActions($item);
                $actions = $provider->filterActions($item, $actions);
                $defaultActions = array_filter($actions, function ($action) { return $action['DEFAULT'] === true; });
	            $defaultAction = reset($defaultActions);
	            $editUrl = $this->getRowEditUrl($item);

	            if ($defaultAction !== false)
	            {
	            	if (
						isset($defaultAction['URL'])
						&& (
							empty($defaultAction['ACTION'])
							|| preg_match('/BX.adminPanel.Redirect/', $defaultAction['ACTION'])
						)
		            )
		            {
			            $link = $defaultAction['URL'];
			            $item['ROW_URL'] = $defaultAction['URL'];
		            }
	            	else
		            {
			            $item['ROW_URL'] = $editUrl;
		            }
	            }
	            else if ((string)$editUrl !== '')
	            {
		            $link = $editUrl;
		            $item['ROW_URL'] = $editUrl;
	            }

                $viewRow = $view->AddRow($item['ID'], [], $link);

                foreach ($headers as $fieldName)
                {
                    $viewRow->AddViewField($fieldName, $this->buildRowValue($item, $fieldName));
                }

				if (!empty($actions))
				{
                    $viewRow->AddActions($actions);
                }

				if (!empty($item['DISABLED']) || empty($actions))
				{
					$viewRow->bReadOnly = true;
				}
            }
        }
    }

    protected function getRowEditUrl($item)
    {
    	$itemType = isset($item['ROW_TYPE']) ? $item['ROW_TYPE'] : 'DEFAULT';
    	$parameterPrefix = $itemType !== 'DEFAULT' ? $itemType . '_' : '';
	    $parameterName = $parameterPrefix  . 'EDIT_URL';
	    $result = null;

	    if (isset($this->arParams[$parameterName]))
	    {
	    	$result = (string)$this->arParams[$parameterName];
	    	$replaces = array_intersect_key($item, [
	    		'ID' => true,
			    'PRIMARY' => true,
		    ]);

	    	foreach ($replaces as $key => $value)
		    {
			    $result = str_replace('#' . $key . '#', $value, $result);
		    }
	    }

    	return $result;
    }

    protected function buildRowValue($item, $fieldKey)
    {
        $result = null;
	    $field = isset($this->arResult['FIELDS'][$fieldKey]) ? $this->arResult['FIELDS'][$fieldKey] : null;

	    if ($field === null || !$this->isMatchRowType($item, $field))
	    {
			// nothing
	    }
        else if (isset($field['USER_TYPE']['CLASS_NAME']))
        {
            $result = $this->buildRowValueFromUserField($field, $item[$fieldKey], $item);
        }
        else if (isset($item[$fieldKey]))
        {
            $result = $item[$fieldKey];
        }

        return $result;
    }

    protected function buildRowValueFromUserField($userField, $value, $row)
    {
    	return Market\Ui\UserField\Helper\Renderer::getViewHtml($userField, $value, $row);
    }

    protected function buildRowActions($item)
    {
        return !empty($this->arParams['ROW_ACTIONS'])
	        ? $this->makeRowActions($item, $this->arParams['ROW_ACTIONS'])
	        : [];
    }

	protected function makeRowActions($item, $actions)
	{
		global $APPLICATION;

		$result = [];
		$replacesFrom = [];
		$replacesTo = [];

		foreach ($item as $key => $value)
		{
			if (is_scalar($value))
			{
				$replacesFrom[] ='#' . $key . '#';
				$replacesTo[] = $value;
			}
		}

		foreach ($actions as $type => $action)
		{
			if (!$this->isMatchRowType($item, $action)) { continue; }

			// menu

			if (isset($action['MENU']))
			{
				$result[] = array_filter([
					'ICON' => isset($action['ICON']) ? $action['ICON'] : null,
					'DEFAULT' => isset($action['DEFAULT']) ? $action['DEFAULT'] : null,
					'FILTER' => isset($action['FILTER']) ? $action['FILTER'] : null,
					'TEXT' => $action['TEXT'],
					'TYPE' => $type,
					'MENU' => $this->makeRowActions($item, $action['MENU']),
				]);

				continue;
			}

			// action

			$actionMethod = null;
			$actionUrl = null;

			if (isset($action['METHOD']))
			{
				$actionMethod = str_replace($replacesFrom, $replacesTo, $action['METHOD']);
			}
			else if ($type === 'DELETE' || isset($action['ACTION']))
			{
				$actionMethod = isset($action['ACTION']) ? $action['ACTION'] : 'delete';

				$queryParams = [
					'sessid' => bitrix_sessid(),
					'action_button' => $actionMethod,
					'ID' => $item['ID'],
				];

				if ($this->useUiView())
				{
					$queryParams['action'] = $actionMethod;
					unset($queryParams['action_button']);

					$actionMethod = sprintf(
						'BX.Main.gridManager.getById("%s").instance.reloadTable("POST", %s)',
						$this->arParams['GRID_ID'],
						Main\Web\Json::encode($queryParams)
					);
				}
				else
				{
					$url = $APPLICATION->GetCurPageParam(
						http_build_query($queryParams),
						array_keys($queryParams)
					);

					$actionMethod = $this->arParams['GRID_ID'] . '.GetAdminList("' . \CUtil::addslashes($url) . '");';
				}
			}
			else
			{
				if (isset($action['QUERY']))
				{
					$actionUrlQueryParameters = $action['QUERY'];

					foreach ($actionUrlQueryParameters as &$actionUrlQueryParameter)
					{
						$actionUrlQueryParameter = str_replace($replacesFrom, $replacesTo, $actionUrlQueryParameter);
					}
					unset($actionUrlQueryParameter);

					$actionUrl = $APPLICATION->GetCurPageParam(
						http_build_query($actionUrlQueryParameters),
						array_merge(
							array_keys($actionUrlQueryParameters),
							$this->getUrlSystemParameters()
						),
						false
					);
				}
				else
				{
					$actionUrl = str_replace($replacesFrom, $replacesTo, $action['URL']);
				}

				if (Market\Data\TextString::getPosition($actionUrl, 'lang=') === false)
				{
					$actionUrl .=
						(Market\Data\TextString::getPosition($actionUrl, '?') === false ? '?' : '&')
						. 'lang=' . LANGUAGE_ID;
				}

				if (isset($action['MODAL']) && $action['MODAL'] === 'Y')
				{
					$modalParameters = array_merge(
						[ 'content_url' => $actionUrl ],
						isset($action['MODAL_PARAMETERS']) ? (array)$action['MODAL_PARAMETERS'] : [],
						[
							'draggable' => true,
							'resizable' => true,
						]
					);

					if (isset($action['MODAL_TITLE']))
					{
						$modalParameters['title'] = str_replace($replacesFrom, $replacesTo, $action['MODAL_TITLE']);
					}

					$actionMethod = sprintf(
						'(new BX.YandexMarket.Dialog(%s)).Show();',
						\CUtil::PhpToJSObject($modalParameters)
					);
				}
				else if (isset($action['WINDOW']) && $action['WINDOW'] === 'Y')
				{
					$actionMethod = 'jsUtils.OpenWindow("' . \CUtil::AddSlashes($actionUrl) . '", 1250, 800);';
				}
				else
				{
					$actionMethod = "BX.adminPanel.Redirect([], '".\CUtil::AddSlashes($actionUrl)."', event);";
				}
			}

			if ($actionMethod !== null)
			{
				if (!empty($action['CONFIRM']))
				{
					$confirmMessage = !empty($action['CONFIRM_MESSAGE']) ? $action['CONFIRM_MESSAGE'] : $this->getLang('ROW_ACTION_CONFIRM');
					$actionMethod = 'if (confirm("' . \CUtil::AddSlashes($confirmMessage) . '")) ' . $actionMethod;
				}

				$result[] = array_filter([
					'URL' => $actionUrl,
					'ACTION' => $actionMethod,
					'ONCLICK' => $actionMethod, // submenu for main.ui.grid
					'ICON' => isset($action['ICON']) ? $action['ICON'] : null,
					'DEFAULT' => isset($action['DEFAULT']) ? $action['DEFAULT'] : null,
					'FILTER' => isset($action['FILTER']) ? $action['FILTER'] : null,
					'TEXT' => $action['TEXT'],
					'TYPE' => $type,
				]);
			}
		}

		return $result;
	}

    protected function isMatchRowType($item, $target)
    {
    	$itemType = isset($item['ROW_TYPE']) ? $item['ROW_TYPE'] : 'DEFAULT';
    	$targetType = isset($target['ROW_TYPE']) ? $target['ROW_TYPE'] : 'DEFAULT';

    	if (is_array($targetType))
	    {
	    	$result = in_array($itemType, $targetType, true);
	    }
    	else
	    {
		    $result = ($itemType === $targetType);
	    }

    	return $result;
    }

    protected function buildNavString($queryParams)
    {
        $listView = $this->getViewList();

        if ($this->isSubList())
        {
            $iterator = new \CAdminSubResult([], $this->arParams['GRID_ID'], $listView->GetListUrl(true));
        }
        else if ($this->useUiView())
        {
	        $iterator = new \CAdminUiResult([], $this->arParams['GRID_ID']);
        }
        else
        {
            $iterator = new \CAdminResult([], $this->arParams['GRID_ID']);
        }

		if (isset($queryParams['limit']))
		{
			$page = floor($queryParams['offset'] / $queryParams['limit']) + 1;
			$totalCount = $this->arResult['TOTAL_COUNT'];
			$totalPages = ceil($totalCount / $queryParams['limit']);

			$iterator->NavStart($queryParams['limit'], true, $page);
			$iterator->NavRecordCount = $totalCount;
			$iterator->NavPageCount = $totalPages;
			$iterator->NavPageNomer = $page;
		}
		else
		{
			$iterator->NavStart();
		}

		if ($listView instanceof \CAdminUiList)
		{
			$listView->SetNavigationParams($iterator, [
				'BASE_LINK' => $this->getBaseUrl(),
			]);
		}
		else
		{
			$listView->NavText($iterator->GetNavPrint($this->arParams['NAV_TITLE']));
        }

		$this->arResult['NAV_OBJECT'] = $iterator;
    }

    protected function buildGroupActions()
    {
    	$useUiView = $this->useUiView();
	    $actions = $useUiView && isset($this->arParams['UI_GROUP_ACTIONS'])
		    ? (array)$this->arParams['UI_GROUP_ACTIONS']
		    : $this->arParams['GROUP_ACTIONS'];
	    $actions += $useUiView ? $this->provider->getUiGroupActions() : $this->provider->getGroupActions();

	    if (!empty($actions))
		{
			$params = $useUiView && isset($this->arParams['UI_GROUP_ACTIONS_PARAMS'])
				? (array)$this->arParams['UI_GROUP_ACTIONS_PARAMS']
				: (array)$this->arParams['GROUP_ACTIONS_PARAMS'];
			$params += $useUiView ? $this->provider->getUiGroupActionParams() : $this->provider->getGroupActionParams();

			if (
				$useUiView
				&& !isset($actions['for_all'])
				&& (!isset($params['disable_action_target']) || $params['disable_action_target'] !== true)
			)
			{
				$actions['for_all'] = true;
			}

			$viewList = $this->getViewList();
			$viewList->AddGroupActionTable($actions, $params);
		}
    }

    protected function getFirstNotEmpty($data, $keys)
    {
        $result = null;

        foreach ($keys as $key)
        {
            if (!empty($data[ $key ]))
            {
                $result = $data[ $key ];
            }
        }

        return $result;
    }

    public function getViewList()
    {
        if ($this->viewList === null)
        {
            if ($this->isSubList())
            {
                $this->viewList = new \CAdminSubList(
                    $this->arParams['GRID_ID'],
                    false, //$this->getViewSort(), sort inside class
                    $this->arParams['AJAX_URL']
                );
            }
            else if ($this->useUiView())
            {
            	$this->viewList = new \CAdminUiList(
	                $this->arParams['GRID_ID'],
                    $this->getViewSort()
	            );
	        }
            else
            {
	            $this->viewList = new \CAdminList(
		            $this->arParams['GRID_ID'],
		            $this->getViewSort()
	            );
            }
        }

        return $this->viewList;
    }

    public function getViewSort()
    {
        if ($this->viewSort === null)
        {
            $this->viewSort = $this->useUiView() && class_exists(\CAdminUiSorting::class)
	            ? new \CAdminUiSorting($this->arParams['GRID_ID'])
                : new \CAdminSorting($this->arParams['GRID_ID']);
        }

        return $this->viewSort;
    }

    public function getViewFilter()
    {
        if ($this->viewFilter === null)
        {
            $this->viewFilter = new \CAdminFilter(
                $this->arParams['GRID_ID'] . '_filter',
                $this->getViewFilterPopup()
            );
        }

        return $this->viewFilter;
    }

    protected function getViewFilterPopup()
    {
        $result = [];

        foreach ($this->arResult['FILTER'] as $filter)
        {
            $result[] = $filter['name'];
        }

        return $result;
    }

    protected function resolveTemplateName()
    {
    	if ((string)$this->getTemplateName() !== '' || !$this->useUiView()) { return; }

    	if ($this->isBitrix24())
	    {
		    $this->setTemplateName('bitrix24');
	    }
    	else
	    {
		    $this->setTemplateName('ui');
	    }
    }

    protected function isBitrix24()
    {
    	return Market\Utils\BitrixTemplate::isBitrix24();
    }

    protected function useUiView()
    {
    	return !$this->isSubList() && $this->supportsUiView();
    }

    protected function supportsUiView()
    {
    	return (
    		\class_exists(\CAdminUiList::class)
		    && \class_exists(\CAdminUiListActionPanel::class)
	    );
    }

    protected function isSubList()
    {
        $result = false;

        if ($this->arParams['SUBLIST'] && Main\Loader::includeModule('iblock'))
        {
            require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/iblock/classes/general/subelement.php';

            $result = true;
        }

        return $result;
    }

    protected function isSubListAjaxPage()
    {
        global $APPLICATION;

        $curPage = $APPLICATION->GetCurPage(false);

        return Market\Data\TextString::getPosition($this->arParams['AJAX_URL'], $curPage) === 0;
    }

    public function getUrl()
    {
        global $APPLICATION;

        $systemParameters = $this->getUrlSystemParameters();

        return $APPLICATION->GetCurPageParam('', $systemParameters);
    }

    public function getBaseUrl()
    {
        global $APPLICATION;

        return $this->arParams['BASE_URL'] ?: $APPLICATION->GetCurPage();
    }

    protected function getUrlSystemParameters()
    {
    	return array_merge(
    		Main\HttpRequest::getSystemParameters(),
            [ 'table_id', 'mode', 'grid_id', 'grid_action', 'bxajaxid', 'internal', 'clear_nav' ]
	    );
    }

    public function getLang($code, $replaces = null)
    {
		return Main\Localization\Loc::getMessage(static::$langPrefix . $code, $replaces) ?: $code;
    }

    public function getProvider($providerType = null)
    {
        if ($this->provider === null)
        {
            if (!Main\Loader::includeModule('yandex.market'))
            {
                throw new Main\SystemException($this->getLang('REQUIRE_SELF_MODULE'));
            }

            if (!isset($providerType))
            {
                $providerType = $this->arParams['PROVIDER_TYPE'];
            }

            $className = 'Yandex\Market\Component\\' . $providerType . '\GridList';

            if (
                !class_exists($className)
                || !is_subclass_of($className, 'Yandex\Market\Component\Base\GridList')
            )
            {
				throw new Main\SystemException($this->getLang('INVALID_PROVIDER'));
            }

            $this->provider = new $className($this);
        }

        return $this->provider;
    }
}