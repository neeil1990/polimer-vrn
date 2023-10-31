<?php
namespace Yandex\Market\Ui\Trading\Molecules;

use Bitrix\Main;
use Yandex\Market\Data\Number;
use Yandex\Market\Reference\Concerns;
use Yandex\Market\Trading\Business;
use Yandex\Market\Trading\Facade;
use Yandex\Market\Utils;

class BusinessSelector
{
	use Concerns\HasOnce;
	use Concerns\HasMessage;

	protected $optionCategory;
	protected $request;

	public function __construct($optionCategory, Main\HttpRequest $request = null)
	{
		$this->optionCategory = $optionCategory;
		$this->request = $request ?: Main\Application::getInstance()->getContext()->getRequest();
	}

	public function selected()
	{
		$requested = $this->requested();
		$incoming = $requested ?: $this->stored();
		$collection = $this->collection();

		if ($incoming !== null)
		{
			$business = $collection->getItemById($incoming);

			if ($business === null)
			{
				throw new Main\ObjectNotFoundException(self::getMessage('NOT_FOUND', [
					'#ID#' => $incoming,
				]));
			}

			if (!$business->isActive())
			{
				throw new Main\ObjectException(self::getMessage('INACTIVE', [
					'#ID#' => $incoming,
				]));
			}

			if ($requested !== null)
			{
				$this->store($business);
			}
		}
		else
		{
			$business = $collection->getActive();

			if ($business === null)
			{
				throw new Main\ObjectNotFoundException(self::getMessage('NOT_EXISTS'));
			}
		}

		return $business;
	}

	protected function requested()
	{
		return Number::castInteger($this->request->get('business'));
	}

	protected function stored()
	{
		global $USER;

		$userId = ($USER instanceof \CUser ? (int)$USER->GetID() : 0);
		$option = (string)\CUserOptions::GetOption($this->optionCategory, 'business', null, $userId);

		return $option !== '' ? (int)$option : null;
	}

	protected function store(Business\Model $selected)
	{
		global $USER;

		if ($this->stored() === (int)$selected->getId()) { return; }

		$userId = ($USER instanceof \CUser ? (int)$USER->GetID() : 0);

		\CUserOptions::SetOption($this->optionCategory, 'business', $selected->getId(), false, $userId);
	}

	public function show(Business\Model $selected = null, $force = false)
	{
		$options = $this->buildOptions($selected);
		$showLimit = $force ? 0 : 1;

		if (count($options) <= $showLimit) { return; }

		if (Utils\BitrixTemplate::isBitrix24())
		{
			$this->renderCrm($options);
		}
		else
		{
			$this->renderAdmin($options);
		}
	}

	protected function buildOptions(Business\Model $selected = null)
	{
		global $APPLICATION;

		$selectedId = $selected !== null ? (int)$selected->getId() : null;
		$result = [];

		/** @var Business\Model $business */
		foreach ($this->collection() as $business)
		{
			if (!$business->isActive()) { continue; }

			$result[] = [
				'ID' => $business->getId(),
				'VALUE' => sprintf('[%s] %s', $business->getId(), $business->getField('NAME')),
				'URL' => $APPLICATION->GetCurPageParam(http_build_query([ 'business' => $business->getId() ]), [ 'business' ]),
				'SELECTED' => ($selectedId === (int)$business->getId()),
			];
		}

		return $result;
	}

	/** @noinspection JSUnresolvedReference */
	protected function renderCrm(array $options)
	{
		global $APPLICATION;

		$selectedOptions = array_filter($options, static function(array $option) { return $option['SELECTED']; });
		$selectedOption = reset($selectedOptions);
		$dropdownItems = array_map(static function(array $option) {
			return [
				'text' => $option['VALUE'],
				'link' => $option['URL'],
				'selected' => $option['SELECTED'],
			];
		}, $options);
		$dropdownItems = array_filter($dropdownItems, static function(array $item) { return !$item['selected']; });
		$dropdownItems = array_values($dropdownItems);

		$html = sprintf(
			'<div class="crm-interface-toolbar-button-container">
				<button class="ui-btn ui-btn-dropdown ui-btn-light-border" type="button" id="yamarket-setup-selector">
					%s
				</button>
			</div>',
			$selectedOption !== false ? $selectedOption['VALUE'] : 'TRADING BEHAVIOR'
		);
		$html .= sprintf(
			'<script>
				BX.ready(function() {
					const button = BX("yamarket-setup-selector");
					const items = JSON.parse(\'%s\');
					
					if (!button || !items) { return; }
					
					items.forEach(function(item) {
						item.onclick = function() { window.location.href = item.link; };
					});
					
					const menu = new BX.PopupMenuWindow({
						bindElement: button,
						items: items,
					});
			
					button.addEventListener("click", function() { menu.show(); });
				});
			</script>',
			Main\Web\Json::encode($dropdownItems)
		);

		$APPLICATION->AddViewContent('inside_pagetitle', $html);
	}

	/** @noinspection HtmlUnknownTarget */
	protected function renderAdmin(array $options)
	{
		echo '<div style="margin-bottom: 10px;">';

		foreach ($options as $option)
		{
			if ($option['SELECTED'])
			{
				echo sprintf(
					' <span class="adm-btn adm-btn-active">%s</span>',
					htmlspecialcharsbx($option['VALUE'])
				);
			}
			else
			{
				echo sprintf(
					' <a class="adm-btn" href="%s">%s</a>',
					htmlspecialcharsbx($option['URL']),
					htmlspecialcharsbx($option['VALUE'])
				);
			}
		}

		echo '</div>';
	}

	/** @return Business\Collection */
	protected function collection()
	{
		return $this->once('collection', function() {
			$collection = Business\Collection::loadByFilter([]);

			if ($collection->count() > 0) { return $collection; }

			Facade\Business::synchronize();

			return Business\Collection::loadByFilter([]);
		});
	}
}