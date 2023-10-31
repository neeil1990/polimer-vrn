<?php

namespace Yandex\Market\Trading\Service\Common\Action\Settings;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;

class Action extends TradingService\Common\Action\HttpAction
{
	protected $secret = [
		'YANDEX_TOKEN',
		'OAUTH_TOKEN',
		'OAUTH_CLIENT_ID',
		'OAUTH_CLIENT_PASSWORD',
	];

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
		$this->collectTabs();
		$this->collectFields();
	}

	protected function collectTabs()
	{
		$tabs = $this->provider->getOptions()->getTabs();

		uasort($tabs, static function($tabA, $tabB) {
			$sortA = isset($tabA['sort']) ? $tabA['sort'] : 5000;
			$sortB = isset($tabB['sort']) ? $tabB['sort'] : 5000;

			if ($sortA === $sortB) { return 0; }

			return ($sortA < $sortB ? -1 : 1);
		});

		foreach ($tabs as $code => $tab)
		{
			$this->response->setField($code, [
				'name' => html_entity_decode(strip_tags($tab['name'])),
				'fields' => [],
			]);
		}
	}

	protected function collectFields()
	{
		$values = $this->provider->getOptions()->getValues();
		$tabsDisplay = [];

		foreach ($this->getFields() as $field)
		{
			$value = Market\Utils\Field::getChainValue($values, $field['FIELD_NAME'], Market\Utils\Field::GLUE_BRACKET);

			if ($value === null && isset($field['VALUE']))
			{
				$value = $field['VALUE'];
			}

			if ($this->testDeprecated($field, $value)) { continue; }
			if ($this->testDependHidden($field, $values)) { continue; }

			$tabCode = isset($field['TAB']) ? $field['TAB'] : 'COMMON';

			if (!isset($tabsDisplay[$tabCode])) { $tabsDisplay[$tabCode] = []; }

			$tabsDisplay[$tabCode][] = [
				'code' => $field['FIELD_NAME'],
				'name' => html_entity_decode(strip_tags($field['NAME'])),
				'value' => $this->formatValue($field, $value, $values),
			];
		}

		foreach ($tabsDisplay as $tabCode => $tabValues)
		{
			$this->response->setField($tabCode . '.fields', $tabValues);
		}
	}

	protected function getFields()
	{
		$options = $this->provider->getOptions();
		$fields = $options->getFields($this->environment, $options->getSiteId());
		$result = [];

		foreach ($fields as $code => $field)
		{
			$result[] = Market\Ui\UserField\Helper\Field::extend($field, $code);
		}

		return $result;
	}

	protected function testDeprecated(array $field, $value)
	{
		return (
			isset($field['DEPRECATED'])
			&& $field['DEPRECATED'] === 'Y'
			&& empty($value)
		);
	}

	protected function testDependHidden(array $field, array $values)
	{
		if (!isset($field['DEPEND'])) { return false; }

		return !Market\Utils\UserField\DependField::test($field['DEPEND'], $values);
	}

	protected function formatValue(array $field, $value, array $row)
	{
		if ($field['MULTIPLE'] !== 'N')
		{
			if (!is_array($value)) { $value = []; }

			$result = $this->formatMultipleValue($field, $value, $row);
		}
		else
		{
			$result = $this->formatSingleValue($field, $value, $row);
		}

		return $result;
	}

	protected function formatMultipleValue(array $field, array $value, array $row)
	{
		$className = $field['USER_TYPE']['CLASS_NAME'];

		if (method_exists($className, 'ymExportMultipleValue'))
		{
			$field = Market\Ui\UserField\Helper\Field::extendValue($field, $value, $row);
			$result = $className::ymExportMultipleValue($field, $value);
		}
		else
		{
			$result = [];

			foreach ($value as $one)
			{
				$display = $this->formatSingleValue($field, $one, $row);

				if ($display === null) { continue; }

				$result[] = $display;
			}
		}

		return $result;
	}

	protected function formatSingleValue(array $field, $value, array $row)
	{
		$raw = $value;

		$value = $this->applyValueUserField($field, $value, $row);
		$value = $this->applyValueAdditional($field, $value, $raw, $row);
		$value = $this->applyValueSecret($field, $value);

		return $value;
	}

	protected function applyValueUserField(array $field, $value, array $row)
	{
		$className = $field['USER_TYPE']['CLASS_NAME'];

		if (method_exists($className, 'ymExportValue'))
		{
			$field = Market\Ui\UserField\Helper\Field::extendValue($field, $value, $row);
			$result = $className::ymExportValue($field, $value);
		}
		else
		{
			$result = $value;
		}

		return $result;
	}

	protected function applyValueAdditional(array $field, $value, $raw, array $row)
	{
		$parts = explode('_', $field['FIELD_NAME']);
		$parts = array_map('ucfirst', $parts);

		$methodName = 'extendValue' . implode('', $parts);

		if (method_exists($this, $methodName))
		{
			$value = $this->{$methodName}($field, $value, $raw, $row);
		}

		return $value;
	}

	protected function applyValueSecret(array $field, $value)
	{
		if (!in_array($field['FIELD_NAME'], $this->secret, true)) { return $value; }

		if (is_scalar($value))
		{
			$length = Market\Data\TextString::getLength($value);
			$unmasked = floor($length / 3);

			if ($unmasked >= 1)
			{
				$result =
					Market\Data\TextString::getSubstring($value, 0, $unmasked)
					. str_repeat('*', $length - 2 * $unmasked)
					. Market\Data\TextString::getSubstring($value, $length - $unmasked, $unmasked);
			}
			else
			{
				$result = sprintf('%s(%s)', gettype($value), $length);
			}
		}
		else if (is_array($value))
		{
			$result = [];

			foreach ($value as $key => $one)
			{
				$result[$key] = $this->applyValueSecret($field, $one);
			}
		}
		else
		{
			$result = gettype($value);
		}

		return $result;
	}
}