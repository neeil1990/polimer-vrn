<?php
namespace Yandex\Market\Component\SalesBoost;

use Yandex\Market;

/** @property Market\Components\AdminGridList $component  */
class GridList extends Market\Component\Model\GridList
{
	public function processPostAction($action, $data)
	{
		if ($action === 'reinstall')
		{
			$this->processReinstall($data);
		}
		else
		{
			parent::processPostAction($action, $data);
		}
	}

	protected function processReinstall($data)
	{
		global $APPLICATION;

		$model = $this->getModelClass();
		$successUrl = $APPLICATION->GetCurPageParam('', [ 'postAction' ]);

		$setupList = $model::loadList(array_diff_key($data, [
			'select' => true,
			'limit' => true,
			'offset' => true,
		]));

		/** @var Market\SalesBoost\Setup\Model $setup */
		foreach ($setupList as $setup)
		{
			Market\Reference\Assert::typeOf($setup,  Market\SalesBoost\Setup\Model::class, 'setup');

			$setup->updateListener();
		}

		Market\Utils\ServerStamp\Facade::reset();
		\CAdminNotify::DeleteByTag(Market\SalesBoost\Agent\Processor::NOTIFY_DISABLED);

		LocalRedirect($successUrl);
	}

	public function filterActions($item, $actions)
    {
        $result = $actions;

        foreach ($result as $actionKey => $action)
        {
            $isValid = true;

            if ($action['TYPE'] === 'ACTIVATE')
            {
	            $isValid = ($item['ACTIVE'] === Market\SalesBoost\Setup\Table::BOOLEAN_N);
            }
			else if ($action['TYPE'] === 'DEACTIVATE')
			{
				$isValid = ($item['ACTIVE'] === Market\SalesBoost\Setup\Table::BOOLEAN_Y);
			}

            if (!$isValid)
            {
                unset($result[$actionKey]);
            }
        }

        return $result;
    }

	/** @noinspection DuplicatedCode */
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
            'ACTIVE' => Market\SalesBoost\Setup\Table::BOOLEAN_Y
        ]);
    }

    protected function deactivateItem($id)
    {
        $dataClass = $this->getDataClass();

        $dataClass::update($id, [
            'ACTIVE' => Market\SalesBoost\Setup\Table::BOOLEAN_N
        ]);
    }
}