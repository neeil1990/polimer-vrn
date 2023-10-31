<?php
namespace Yandex\Market\Component\Molecules;

use Bitrix\Main;
use Yandex\Market\Export;
use Yandex\Market\Reference;

class ExportLink
{
	use Reference\Concerns\HasMessage;

	public function sanitize(array $request)
	{
		$result = $request;
		$hasSetupRequest = isset($request['SETUP']);
		$hasSetupLinkRequest = isset($request['SETUP_LINK']);

		if ($hasSetupRequest || $hasSetupLinkRequest)
		{
			$ids = $hasSetupRequest ? (array)$request['SETUP'] : [];
			$idsMap = array_flip($ids);
			$usedIds = [];
			$result['SETUP_LINK'] = $hasSetupLinkRequest ? (array)$request['SETUP_LINK'] : [];

			foreach ($result['SETUP_LINK'] as $setupLinkKey => $setupLink)
			{
				$setupId = (int)$setupLink['SETUP_ID'];

				if ($setupId > 0 && isset($idsMap[$setupId]))
				{
					$usedIds[$setupId] = true;
				}
				else
				{
					unset($result['SETUP_LINK'][$setupLinkKey]);
				}
			}

			foreach ($ids as $id)
			{
				if ($id > 0 && !isset($usedIds[$id]))
				{
					$result['SETUP_LINK'][] = [
						'SETUP_ID' => $id
					];
				}
			}
		}

		return $result;
	}

	public function usedIblockIds(array $data = null)
	{
		if ($data === null) { return []; }

		$filter = $this->getSetupFilter($data, 'SETUP');

		if ($filter === null) { return []; }

		$querySetupIblockLinkList = Export\IblockLink\Table::getList([
			'filter' => $filter,
			'select' => [ 'IBLOCK_ID' ],
		]);

		return array_keys(array_column($querySetupIblockLinkList->fetchAll(), 'IBLOCK_ID', 'IBLOCK_ID'));
	}

	private function getSetupFilter(array $data, $reference = null)
	{
		if (isset($data['SETUP_EXPORT_ALL']) && (string)$data['SETUP_EXPORT_ALL'] === Reference\Storage\Table::BOOLEAN_Y)
		{
			return [];
		}

		$setupIds = [];

		if (!empty($data['SETUP']))
		{
			$setupIds = array_map('intval', $data['SETUP']);
			$setupIds = array_filter($setupIds);
		}

		if (empty($setupIds))
		{
			return null;
		}

		return [
			'=' . ($reference !== null ? $reference . '.' : '') . 'ID' => $setupIds,
		];
	}

	public function validate(Main\Entity\Result $result, array $data, array $fields)
	{
		$hasEmptySetupLink = false;
		$hasEmptySetupExportAll = false;

		if (isset($fields['SETUP']))
		{
			if (empty($data['SETUP']))
			{
				$hasEmptySetupLink = true;
			}
			else
			{
				Main\Type\Collection::normalizeArrayValuesByInt($data['SETUP']);

				$hasEmptySetupLink = (count($data['SETUP']) === 0);
			}
		}

		if (isset($fields['SETUP_EXPORT_ALL']))
		{
			$hasEmptySetupExportAll = (
				empty($data['SETUP_EXPORT_ALL'])
				|| (string)$data['SETUP_EXPORT_ALL'] === Reference\Storage\Table::BOOLEAN_N
			);
		}

		if ($hasEmptySetupLink && $hasEmptySetupExportAll)
		{
			$result->addError(new Main\Entity\EntityError(
				self::getMessage('LINK_EMPTY'),
				Main\Entity\FieldError::EMPTY_REQUIRED
			));
		}
	}
}