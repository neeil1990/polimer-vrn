<?php

namespace Yandex\Market\Ui\Trading;

use Bitrix\Main;
use Yandex\Market;

class OrderViewTabSet extends Market\Ui\Reference\Page
{
	use Market\Reference\Concerns\HasLang;

	protected $setup;
	protected $externalId;
	protected $parameters;
	protected $template;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function __construct(Market\Trading\Setup\Model $setup, $externalId, array $parameters = [])
	{
		parent::__construct();

		$this->setup = $setup;
		$this->externalId = $externalId;
		$this->parameters = $parameters;
	}

	protected function getReadRights()
	{
		return Market\Ui\Access::RIGHTS_PROCESS_TRADING;
	}

	public function checkSupport()
	{
		$router = $this->setup->getService()->getRouter();
		$environment = $this->setup->getEnvironment();

		$router->getDataAction('admin/view', $environment, []);
	}

	public function initialize()
	{
		return [
			'TABSET' => Market\Config::getLangPrefix() . 'TRADING_ORDER',
			'GetTabs' => [$this, 'getTabs'],
			'ShowTab' => [$this, 'showTab'],
		];
	}

	public function getTabs()
	{
		return [
			[
				'DIV' => 'VIEW',
				'TAB' => $this->getTabTitle(),
				'SHOW_WRAP' => 'N',
				'ONSELECT' => 'yamarketLoadTab("yamarket-adm-order-view-tab", true);',
			],
		];
	}

	public function preloadAssets()
	{
		Market\Utils\Component\Assets::preloadCss('yandex.market:trading.order.view', $this->template ?: false);
		Market\Ui\Library::loadConditional('jquery', Main\Page\AssetLocation::AFTER_JS);
	}

	public function showTab($divName, $arguments, $bVarsFromForm)
	{
		$url = $this->getContentsUrl();

		$this->preloadAssets();

		?>
		<tr>
			<td>
				<div class="adm-detail-title">
					<?= $this->getTitle(); ?>
					<small>
						<a href="#" onclick="yamarketLoadTab('yamarket-adm-order-view-tab'); return false;"><?= $this->getSelfMessage('REFRESH'); ?></a>
					</small>
				</div>
				<div class="adm-detail-content-item-block" style="position:relative; vertical-align:top" id="yamarket-adm-order-view-tab" data-url="<?= htmlspecialcharsbx($url); ?>">
					<img src="/bitrix/images/sale/admin-loader.gif" alt=""/>
				</div>
				<script>
					yamarketCheckTab('yamarket-adm-order-view-tab');

					function yamarketLoadTab(id, firstLoad) {
						var node = document.getElementById(id);
						var url = node.getAttribute('data-url');
						var loadState = node.getAttribute('data-load');

						if (
							url
							&& loadState !== 'pending'
							&& (!firstLoad || loadState !== 'ready')
						) {
							node.setAttribute('data-load', 'pending');
							node.innerHTML = '<img src="/bitrix/images/sale/admin-loader.gif" alt=""/>';

							BX.ajax({
								url: url,
								scriptsRunFirst: false,
								onsuccess: function(html) {
									var callback = function() {
										BX.removeCustomEvent('onAjaxSuccessFinish', callback);
										BX.onCustomEvent(BX(node), 'onYaMarketContentUpdate', [
											{ target: node }
										]);
									};

									node.innerHTML = html;
									node.setAttribute('data-load', 'ready');

									BX.addCustomEvent('onAjaxSuccessFinish', callback);
								},
								onfailure: function() {
									node.setAttribute('data-load', 'fail');
								}
							});
						}
					}

					function yamarketCheckTab(id) {
						var node = document.getElementById(id);

						if (node.offsetWidth > 0 || node.offsetHeight > 0) { // is visible
							yamarketLoadTab(id);
						}
					}
				</script>
			</td>
		</tr>
		<?
	}

	public function getContentsUrl(array $query = [])
	{
		return Market\Ui\Admin\Path::getModuleUrl('trading_order_view', $query + array_filter([
			'lang' => LANGUAGE_ID,
			'view' => 'tab',
			'id' => $this->externalId,
			'setup' => $this->setup->getId(),
			'site' => $this->setup->getSiteId(),
			'template' => $this->template,
		]));
	}

	public function setTemplate($name)
	{
		$this->template = $name;
	}

	public function getTitle()
	{
		return $this->getSelfMessage('ORDER', [ '#EXTERNAL_ID#' => $this->externalId ]);
	}

	public function getTabTitle()
	{
		return $this->getServiceMessage('TAB');
	}

	public function getNavigationTitle()
	{
		return $this->getServiceMessage('NAVIGATION');
	}

	protected function getServiceMessage($key, $replaces = null)
	{
		$serviceKey = 'ORDER_VIEW_' . $key;
		$result = (string)$this->setup->getService()->getInfo()->getMessage($serviceKey, $replaces, '');

		if ($result === '')
		{
			$result = $this->getSelfMessage($key, $replaces);
		}

		return $result;
	}

	protected function getSelfMessage($key, $replaces = null, $fallback = null)
	{
		return static::getLang('UI_TRADING_ORDER_VIEW_TABSET_' . $key, $replaces, $fallback);
	}
}