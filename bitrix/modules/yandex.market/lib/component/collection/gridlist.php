<?php
namespace Yandex\Market\Component\Collection;

use Yandex\Market;

class GridList extends Market\Component\Model\GridList
{
	use Market\Reference\Concerns\HasMessage;

	public function getFields(array $select = [])
	{
		$result = parent::getFields($select);

		if (isset($result['SETUP']))
		{
			$result['SETUP']['LIST_COLUMN_LABEL'] = self::getMessage('FIELD_SETUP_WITH_STATUS');
			$result['SETUP']['SORTABLE'] = false;
		}

		return $result;
	}

	protected function getReferenceFields()
	{
		$result = parent::getReferenceFields();
		$result['SETUP'] = [];

		return $result;
	}

	public function load(array $queryParameters = [])
    {
    	$isNeedLoadExportStatus = (empty($queryParameters['select']) || in_array('SETUP', $queryParameters['select'], true));

    	if ($isNeedLoadExportStatus && !empty($queryParameters['select']))
		{
			$needSelectForExportStatus = [
				'ID',
				'START_DATE',
				'FINISH_DATE',
			];

			$queryParameters['select'] = array_merge($queryParameters['select'], $needSelectForExportStatus);
			$queryParameters['select'] = array_unique($queryParameters['select']);
		}

    	if (
    		!empty($queryParameters['select'])
			&& in_array('SETUP', $queryParameters['select'], true)
			&& !in_array('SETUP_EXPORT_ALL', $queryParameters['select'], true)
		)
		{
			$queryParameters['select'][] = 'SETUP_EXPORT_ALL';
		}

        $result = parent::load($queryParameters);

    	if ($isNeedLoadExportStatus)
		{
    		$this->loadExportStatus($result);
		}

        foreach ($result as &$item)
        {
            if (
            	isset($item['SETUP_EXPORT_ALL'])
				&& (string)$item['SETUP_EXPORT_ALL'] === Market\Export\Collection\Table::BOOLEAN_Y // export for all
			)
            {
                $item['SETUP'] = []; // hide limit
            }
        }
        unset($item);

        return $result;
    }

    protected function loadExportStatus($result)
	{
		$ids = array_column($result, 'ID');
		$ids = array_filter($ids);

		Market\Export\Run\Data\EntityStatus::preload(
			Market\Export\Run\Manager::ENTITY_TYPE_COLLECTION,
			$ids
		);
	}

    protected function normalizeQueryFilter(array $filter)
	{
		$result = parent::normalizeQueryFilter($filter);

		if (!empty($result['SETUP.ID']))
		{
			$result[] = [
				'LOGIC' => 'OR',
				[ 'SETUP.ID' => $result['SETUP.ID'] ],
				[ '=SETUP_EXPORT_ALL' => Market\Export\Collection\Table::BOOLEAN_Y ]
			];

			unset($result['SETUP.ID']);
		}

		return $result;
	}

	public function filterActions($item, $actions)
    {
        $result = $actions;

        foreach ($result as $actionKey => $action)
        {
            $isValid = true;

            switch ($action['TYPE'])
            {
                case 'ACTIVATE':
                    $isValid = ($item['ACTIVE'] === Market\Export\Collection\Table::BOOLEAN_N);
                break;

                case 'DEACTIVATE':
                    $isValid = ($item['ACTIVE'] === Market\Export\Collection\Table::BOOLEAN_Y);
                break;
            }

            if (!$isValid)
            {
                unset($result[$actionKey]);
            }
        }

        return $result;
    }

    public function processAjaxAction($action, $data)
    {
        switch ($action)
        {
            case 'activate':
                $isNeedExport = true;
                $result = $this->processActivateAction($data);
            break;

            case 'deactivate':
                $isNeedExport = true;
                $result = $this->processDeactivateAction($data);
            break;

            default:
                $isNeedExport = ($action === 'delete');
                $result = parent::processAjaxAction($action, $data);
            break;
        }

        if ($isNeedExport)
        {
            $exportQuery = [ 'id' => $result ];
            $exportUrl = $this->getComponentParam('EXPORT_URL');
            $exportUrl .= (Market\Data\TextString::getPosition($exportUrl, '?') === false ? '?' : '&') . http_build_query($exportQuery);

            $this->component->setRedirectUrl($exportUrl);
        }

        return $result;
    }

    protected function processActivateAction($data)
    {
	    $selectedIds = $this->getActionSelectedIds($data);

	    foreach ($selectedIds as $id)
	    {
		    $this->activateItem($id);
	    }

        return $selectedIds;
    }

    protected function processDeactivateAction($data)
    {
	    $selectedIds = $this->getActionSelectedIds($data);

	    foreach ($selectedIds as $id)
	    {
		    $this->deactivateItem($id);
	    }

	    return $selectedIds;
    }

    protected function activateItem($id)
    {
        $dataClass = $this->getDataClass();

        $dataClass::update($id, [
            'ACTIVE' => Market\Export\Collection\Table::BOOLEAN_Y
        ]);
    }

    protected function deactivateItem($id)
    {
        $dataClass = $this->getDataClass();

        $dataClass::update($id, [
            'ACTIVE' => Market\Export\Collection\Table::BOOLEAN_N
        ]);
    }
}