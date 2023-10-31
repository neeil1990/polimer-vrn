<?php
namespace Yandex\Market\SalesBoost\Run\Steps;

use Bitrix\Main;
use Yandex\Market\Api;
use Yandex\Market\Config;
use Yandex\Market\Data;
use Yandex\Market\Exceptions;
use Yandex\Market\Reference\Assert;
use Yandex\Market\Result;
use Yandex\Market\SalesBoost;
use Yandex\Market\Trading;
use Yandex\Market\Logger;

class Submitter extends Data\Run\StepSkeleton
{
	protected $processor;

	public function __construct(SalesBoost\Run\Processor $processor)
	{
		$this->processor = $processor;
	}

	public function getName()
	{
		return 'submitter';
	}

	public function run($action, $offset = null)
	{
		$result = new Result\Step();
		$offsetObject = new Data\Run\Offset($offset, [
			'business',
			'element',
			'repeat',
		]);

		(new Data\Run\Waterfall())
			->add([$this, 'iterateBusiness'])
			->add([$this, 'iterateElements'])
			->add([$this, 'submit'])
			->add([$this, 'commit'])
			->run($offsetObject);

		if ($offsetObject->interrupted())
		{
			$result->setOffset((string)$offsetObject);
			$result->setTotal(1);

			if ($this->processor->parameter('progressCount') === true)
			{
				$result->setReadyCount($this->readyCount());
			}
		}

		return $result;
	}

	protected function readyCount()
	{
		return $this->submittedCount() + $this->deletedCount();
	}

	protected function submittedCount()
	{
		return SalesBoost\Run\Storage\SubmitterTable::getCount([
			'>=TIMESTAMP_X' => $this->processor->parameter('initTimeUTC'),
			'=STATUS' => [
				SalesBoost\Run\Storage\SubmitterTable::STATUS_ACTIVE,
				SalesBoost\Run\Storage\SubmitterTable::STATUS_ERROR,
			],
		]);
	}

	protected function deletedCount()
	{
		return SalesBoost\Run\Storage\CollectorTable::getCount([
			'>=TIMESTAMP_X' => $this->processor->parameter('initTimeUTC'),
			'=STATUS' => SalesBoost\Run\Storage\CollectorTable::STATUS_DELETE,
			'SUBMITTER.ELEMENT_ID' => false,
		]);
	}

	public function iterateBusiness(callable $next, Data\Run\Offset $offset)
	{
		do
		{
			$previous = $offset->get('business');
			$filter = (
				($previous !== null ? [ '>BUSINESS_ID' => $previous ] : [])
				+ [ '>=TIMESTAMP_X' => $this->processor->parameter('initTimeUTC') ]
			);

			$row = SalesBoost\Run\Storage\SubmitterTable::getRow([
				'select' => [ 'BUSINESS_ID' ],
				'filter' => $filter,
				'order' => [ 'BUSINESS_ID' => 'ASC' ],
			]);

			if ($row === null) { break; }

			$business = Trading\Business\Model::loadById($row['BUSINESS_ID']);

			$next($business, $offset);

			if ($offset->interrupted()) { break; }

			$offset->set('business', $row['BUSINESS_ID']);
		}
		while (true);
	}

	public function iterateElements(callable $next, Trading\Business\Model $business, Data\Run\Offset $offset)
	{
		do
		{
			$previous = $offset->get('element');
			$elements = $this->fetchElements($business, $previous);

			if (empty($elements)) { break; }

			$next($business, $elements, $offset);

			if ($offset->interrupted()) { break; }

			$lastElement = end($elements);
			$offset->set('element', $lastElement['ELEMENT_ID']);

			if ($this->processor->isExpired())
			{
				$offset->interrupt();
				break;
			}
		}
		while (true);
	}

	protected function fetchElements(Trading\Business\Model $business, $offset = null)
	{
		$result = [];

		$filter = (
			[ '=BUSINESS_ID' => $business->getId() ]
			+ ($offset !== null ? [ '>ELEMENT_ID' => $offset ] : [])
			+ [
				'=STATUS' => [
					SalesBoost\Run\Storage\SubmitterTable::STATUS_READY,
					SalesBoost\Run\Storage\SubmitterTable::STATUS_DELETE,
				],
				'>=TIMESTAMP_X' => $this->processor->parameter('initTimeUTC'),
			]
		);

		$query = SalesBoost\Run\Storage\SubmitterTable::getList([
			'select' => [ 'SKU', 'BOOST_ID', 'ELEMENT_ID', 'STATUS', 'BID' ],
			'filter' => $filter,
			'order' => [ 'ELEMENT_ID' => 'ASC' ],
			'limit' => 500,
		]);

		while ($row = $query->fetch())
		{
			$result[$row['ELEMENT_ID']] = $row;
		}

		return $result;
	}

	public function submit(callable $next, Trading\Business\Model $business, array $elements, Data\Run\Offset $offset)
	{
		$bids = $this->compileBids($elements);

		$submitResult = $this->submitQuery($business, $bids);

		if (!$submitResult->isSuccess())
		{
			if ($this->canRepeat($submitResult, $offset))
			{
				$offset->set('repeat', (int)$offset->get('repeat') + 1);
				$offset->interrupt();
				return;
			}

			/** @noinspection PhpParamsInspection */
			$this->makeLogger($business)->error($submitResult);
		}

		$next($business, $elements, $submitResult->isSuccess());
	}

	protected function compileBids(array $elements)
	{
		$result = [];

		foreach ($elements as $element)
		{
			$result[] = [
				'sku' => (string)$element['SKU'],
				'bid' => (
					$element['STATUS'] === SalesBoost\Run\Storage\SubmitterTable::STATUS_READY
						? max(0, (int)$element['BID'])
						: 0
				)
			];
		}

		return $result;
	}

	protected function submitQuery(Trading\Business\Model $business, array $bids)
	{
		$trading = $business->getPrimaryTrading();
		$service = $trading->wakeupService();
		$options = $service->getOptions();

		/** @var Trading\Service\Marketplace\Options $options */
		Assert::typeOf($options, Trading\Service\Marketplace\Options::class, 'trading.options');

		$request = new Api\Business\Bids\Request();
		$request->setLogger($this->makeLogger($business));
		$request->setBusinessId($options->getBusinessId());
		$request->setOauthClientId($options->getOauthClientId());
		$request->setOauthToken($options->getOauthToken()->getAccessToken());
		$request->setBids($bids);

		return $request->send();
	}

	protected function canRepeat(Api\Reference\RequestResult $requestResult, Data\Run\Offset $offset)
	{
		global $pPERIOD;

		$errors = $requestResult->getErrors();

		if (empty($errors)) { return true; }

		$error = reset($errors);
		$errorCode = $error->getCode();
		$repeated = (int)$offset->get('repeat');

		if (in_array($errorCode, [ 'FORBIDDEN', 'METHOD_NOT_ALLOWED' ], true))
		{
			throw new Exceptions\Api\Request($error->getMessage(), $errorCode);
		}

		if ($errorCode === 'BAD_REQUEST')
		{
			return false;
		}

		if ($errorCode === 'LIMIT_EXCEEDED')
		{
			$pPERIOD = (int)Config::getOption('boost_submit_hit_rate_pause', 60);

			return ($repeated < (int)Config::getOption('boost_submit_hit_rate_repeat', 10));
		}

		return ($repeated < (int)Config::getOption('boost_submit_repeat', 2));
	}

	public function commit(callable $next, Trading\Business\Model $business, array $elements, $isSuccess = true)
	{
		list($active, $delete) = $this->splitChanges($elements);

		if ($isSuccess)
		{
			$this->commitActive($business, $active);
			$this->commitDelete($business, $delete);
		}
		else
		{
			$this->commitActive($business, $active, false);
		}

		$next($business, $elements);
	}

	protected function splitChanges(array $elements)
	{
		$active = [];
		$delete = [];

		foreach ($elements as $element)
		{
			if ($element['STATUS'] === SalesBoost\Run\Storage\SubmitterTable::STATUS_READY)
			{
				$active[] = $element['SKU'];
			}
			else
			{
				$delete[] = $element['SKU'];
			}
		}

		return [$active, $delete];
	}

	protected function commitActive(Trading\Business\Model $business, array $skus, $isSuccess = true)
	{
		if (empty($skus)) { return; }

		SalesBoost\Run\Storage\SubmitterTable::updateBatch([
			'filter' => [
				'=BUSINESS_ID' => $business->getId(),
				'=SKU' => $skus,
			],
		], [
			'STATUS' => $isSuccess
				? SalesBoost\Run\Storage\SubmitterTable::STATUS_ACTIVE
				: SalesBoost\Run\Storage\SubmitterTable::STATUS_ERROR,
			'TIMESTAMP_X' => new Data\Type\CanonicalDateTime(),
		]);
	}

	protected function commitDelete(Trading\Business\Model $business, array $skus)
	{
		if (empty($skus)) { return; }

		SalesBoost\Run\Storage\SubmitterTable::deleteBatch([
			'filter' => [
				'=BUSINESS_ID' => $business->getId(),
				'=SKU' => $skus,
			],
		]);
	}

	protected function makeLogger(Trading\Business\Model $business)
	{
		try
		{
			/** @var Logger\Trading\Logger $logger */
			$logger = $business->getPrimaryTrading()->wakeupService()->getLogger();
			$logger->setContext('AUDIT', Logger\Trading\Audit::SALES_BOOST);
		}
		catch (Main\ObjectNotFoundException $exception)
		{
			$logger = new Logger\Trading\Logger();
			$logger->setContext('AUDIT', Logger\Trading\Audit::SALES_BOOST);
		}

		return $logger;
	}
}

