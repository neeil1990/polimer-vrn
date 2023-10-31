<?php

namespace Yandex\Market\Trading\Service\Common\Action\SettingsLog;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;

/** @property Request $request */
class Action extends TradingService\Common\Action\HttpAction
{
	const PAGE_SIZE = 20;

	protected function createRequest(Main\HttpRequest $request, Main\Server $server)
	{
		return new Request($request, $server);
	}

	public function getAudit()
	{
		return Market\Logger\Trading\Audit::INTERNAL;
	}

	public function process()
	{
		try
		{
			$parameters = $this->makeParameters();
			list($rows, $total) = $this->load($parameters);

			$this->collectPagination($total);
			$this->collectRows($rows);
		}
		catch (Main\ArgumentException $exception)
		{
			$this->response->setField('error', $exception->getMessage());
		}
	}

	protected function makeParameters()
	{
		return [
			'filter' => $this->makeFilter(),
			'offset' => $this->makeOffset(),
			'limit' => static::PAGE_SIZE,
			'order' => [ 'ID' => 'DESC' ],
		];
	}

	protected function makeFilter()
	{
		$result = [
			'=ENTITY_PARENT' => $this->provider->getOptions()->getSetupId(),
		];
		$result += array_filter([
			'=URL' => $this->request->getUrl(),
			'=LEVEL' => $this->sanitizeEnum('level', $this->request->getLevel(), Market\Logger\Level::getVariants()),
			'=AUDIT' => $this->sanitizeEnum('audit', $this->request->getAudit(), Market\Logger\Trading\Audit::getVariants()),
			'>=TIMESTAMP_X' => $this->request->getDateFrom(),
			'<=TIMESTAMP_X' => $this->request->getDateTo(),
		]);
		$order = $this->request->getOrder();

		if (!empty($order))
		{
			$result['=ENTITY_TYPE'] = Market\Trading\Entity\Registry::ENTITY_TYPE_ORDER;
			$result['=ENTITY_ID'] = $order;
		}

		return $result;
	}

	protected function sanitizeEnum($name, $value, $variants)
	{
		if ((string)$value === '' || in_array($value, $variants, true)) { return $value; }

		throw new Main\ArgumentException(sprintf(
			'unknown %s %s, use one of: %s',
			$name,
			$value,
			implode(', ', $variants)
		));
	}

	protected function makeOffset()
	{
		$page = (int)$this->request->getPage();

		return $page > 0 ? static::PAGE_SIZE * ($page - 1) : 0;
	}

	protected function load(array $parameters)
	{
		$query = Market\Logger\Trading\Table::getList($parameters + [
			'count_total' => true,
		]);

		return [$query->fetchAll(), $query->getCount()];
	}

	protected function collectRows(array $rows)
	{
		$display = [];

		foreach ($rows as $row)
		{
			/** @var Main\Type\DateTime $date */
			$date = $row['TIMESTAMP_X'];

			$display[] = array_filter([
				'time' => $date->format(\DateTime::ATOM),
				'message' => $row['MESSAGE'],
				'level' => $row['LEVEL'],
				'url' => $row['URL'],
				'audit' => $row['AUDIT'],
				'entity' => $row['ENTITY_TYPE'] === 'order' ? $row['ENTITY_ID'] : null,
				'context' => $row['CONTEXT'],
				'trace' => $row['TRACE'],
			]);
		}

		$this->response->setField('rows', $display);
	}

	protected function collectPagination($total)
	{
		$this->response->setField('pager', [
			'total' => ceil($total / static::PAGE_SIZE),
		]);
	}
}