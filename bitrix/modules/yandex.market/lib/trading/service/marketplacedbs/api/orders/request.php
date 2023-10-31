<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Api\Orders;

use Yandex\Market;

class Request extends Market\Api\Partner\Orders\Request
{
	protected $onlyWaitingForCancellationApprove;
	protected $dispatchType;

	/** @return bool|null */
	public function getOnlyWaitingForCancellationApprove()
	{
		return $this->onlyWaitingForCancellationApprove;
	}

	/** @param bool $onlyWaitingForCancellationApprove */
	public function setOnlyWaitingForCancellationApprove($onlyWaitingForCancellationApprove)
	{
		$this->onlyWaitingForCancellationApprove = (bool)$onlyWaitingForCancellationApprove;
	}

	/** @return string|null */
	public function getDispatchType()
	{
		return $this->dispatchType;
	}

	/** @param string $dispatchType */
	public function setDispatchType($dispatchType)
	{
		$this->dispatchType = $dispatchType;
	}

	public function processParameters(array $parameters)
	{
		foreach ($parameters as $key => $value)
		{
			if ($key === 'onlyWaitingForCancellationApprove')
			{
				$this->setOnlyWaitingForCancellationApprove($value);
				unset($parameters[$key]);
			}
			else if ($key === 'dispatchType')
			{
				$this->setDispatchType($value);
				unset($parameters[$key]);
			}
		}

		parent::processParameters($parameters);
	}

	public function getQuery()
	{
		$result = parent::getQuery();
		$onlyWaitingForCancellationApprove = $this->getOnlyWaitingForCancellationApprove();
		$dispatchType = $this->getDispatchType();

		if ($onlyWaitingForCancellationApprove !== null)
		{
			$result['onlyWaitingForCancellationApprove'] = $onlyWaitingForCancellationApprove ? 'TRUE' : 'FALSE';
		}

		if ($dispatchType !== null)
		{
			$result['dispatchType'] = $dispatchType;
		}

		return $result;
	}

	public function buildResponse($data)
	{
		return new Response($data);
	}
}
