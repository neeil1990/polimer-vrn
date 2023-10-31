<?php
/** @noinspection PhpReturnDocTypeMismatchInspection */
/** @noinspection PhpIncompatibleReturnTypeInspection */
namespace Yandex\Market\Export\Setup;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Yandex\Market;
use Yandex\Market\Export\Glossary;

Loc::loadMessages(__FILE__);

class Model extends Market\Reference\Storage\Model
	implements Market\Watcher\Agent\EntityRefreshable
{
	use Market\Watcher\Agent\EntityRefreshableTrait;

	/** @var \Yandex\Market\Export\Xml\Format\Reference\Base */
	protected $format;
	protected $domainParsed;

	public static function getDataClass()
	{
		return Table::class;
	}

	public static function normalizeFileName($fileName, $primary = null)
	{
		$fileName = basename(trim($fileName), '.xml');

		if ($fileName === '' && !empty($primary))
		{
			$fileName = 'setup_' . $primary;
		}

		return ($fileName !== '' ? $fileName . '.xml' : null);
	}

	public function onBeforeRemove()
    {
        if ($this->isAutoUpdate())
        {
            $this->handleChanges(false);
        }

        if ($this->hasFullRefresh())
        {
            $this->handleRefresh(false);
        }
    }

	public function onAfterSave()
	{
	    $this->updateListener();
	}

	public function updateListener()
	{
		$isAutoUpdate = $this->isAutoUpdate();
		$hasFullRefresh = $this->hasFullRefresh();

		$this->handleChanges($isAutoUpdate);
		$this->handleRefresh($hasFullRefresh);
		$this->updatePromoListener();
		$this->updateCollectionListener();
	}

	public function handleChanges($direction)
	{
		if ($direction && !$this->isFileReady()) { return; }

		$installer = new Market\Watcher\Track\Installer(Glossary::SERVICE_SELF, Glossary::ENTITY_SETUP, $this->getId());

        if ($direction)
        {
	        $installer->install($this->getTrackSourceList(), $this->getBindEntities());
        }
        else
        {
	        $installer->uninstall();
        }
	}

	public function updatePromoListener()
    {
        /** @var Market\Export\Promo\Model $promo */
        foreach ($this->getPromoCollection() as $promo)
        {
            $promo->updateListener();
        }
    }

	public function updateCollectionListener()
    {
        /** @var Market\Export\Collection\Model $promo */
        foreach ($this->getCollectionCollection() as $collection)
        {
	        $collection->updateListener();
        }
    }

	public function getTrackSourceList()
    {
        $partials = [];

	    /** @var Market\Export\IblockLink\Model $iblockLink */
        foreach ($this->getIblockLinkCollection() as $iblockLink)
        {
	        $partials[] = $iblockLink->getTrackSourceList();
        }

        return !empty($partials) ? array_merge(...$partials) : [];
    }

	public function getBindEntities()
	{
		$partials = [];

		/** @var Market\Export\IblockLink\Model $iblockLink */
		foreach ($this->getIblockLinkCollection() as $iblockLink)
		{
			$partials[] = $iblockLink->getSetupBindEntities();
		}

		return !empty($partials) ? array_merge(...$partials) : [];
	}

	public function handleRefresh($direction)
	{
		$agentParams = [
			'method' => 'refreshStart',
			'arguments' => [ (int)$this->getId() ],
		];

		if ($direction)
		{
			if (!$this->isFileReady()) { return; }

			$nextExecDate = $this->getRefreshNextExec();

			$agentParams['interval'] = $this->getRefreshPeriod();
			$agentParams['next_exec'] = ConvertTimeStamp($nextExecDate->getTimestamp(), 'FULL');

			Market\Export\Run\Agent::register($agentParams);
		}
		else
		{
			Market\Export\Run\Agent::unregister($agentParams);
			Market\Export\Run\Agent::unregister([
				'method' => 'refresh',
				'arguments' => [ (int)$this->getId() ],
				'search' => Market\Reference\Agent\Controller::SEARCH_RULE_SOFT,
			]);

			Market\Watcher\Agent\StateFacade::drop('refresh', Glossary::SERVICE_SELF, $this->getId());
		}
	}

	/**
	 * @return array
	 */
	public function getContext()
	{
		$format = $this->getFormat();
		$result = $format->getContext();
		$result += [
			'SETUP_ID' => $this->getId(),
			'EXPORT_SERVICE' => $this->getField('EXPORT_SERVICE'),
			'EXPORT_FORMAT' => $this->getField('EXPORT_FORMAT'),
			'EXPORT_FORMAT_TYPE' => $format->getType(),
			'ENABLE_AUTO_DISCOUNTS' => $this->isAutoDiscountsEnabled(),
			'ENABLE_CPA' => $this->isCpaEnabled(),
			'HTTPS' => $this->isHttps(),
			'DOMAIN_URL' => $this->getDomainUrl(),
			'ORIGINAL_URL' => $this->getDomainUrl($this->getField('DOMAIN')),
			'USER_GROUPS' => Market\Data\UserGroup::getDefaults(),
			'HAS_CATALOG' => Main\ModuleManager::isModuleInstalled('catalog'),
			'HAS_SALE' => Main\ModuleManager::isModuleInstalled('sale'),
			'SHOP_DATA' => $this->getShopData()
		];

		// sales notes

		$salesNotes = $this->getSalesNotes();

		if ($salesNotes !== '')
		{
			$result['SALES_NOTES'] = $salesNotes;
		}

		// delivery options

		$deliveryOptions = $this->getDeliveryOptions();

		if (!empty($deliveryOptions))
		{
			$result['DELIVERY_OPTIONS'] = $deliveryOptions;
		}

		return $result;
	}

	public function getDeliveryOptions()
	{
		$deliveryCollection = $this->getDeliveryCollection();

		return $deliveryCollection->getDeliveryOptions();
	}

	public function getSalesNotes()
	{
		return trim($this->getField('SALES_NOTES'));
	}

	public function getShopData()
	{
		$fieldValue = $this->getField('SHOP_DATA');

		return is_array($fieldValue) ? $fieldValue : null;
	}

	public function getFormat()
	{
		if (!isset($this->format))
		{
			$this->format = $this->loadFormat();
		}

		return $this->format;
	}

	protected function loadFormat()
	{
		$service = $this->getField('EXPORT_SERVICE');
		$format = $this->getField('EXPORT_FORMAT');

		return Market\Export\Xml\Format\Manager::getEntity($service, $format);
	}

	public function getFileName()
	{
		$nameValue = (string)$this->getField('FILE_NAME');
		$fileName = static::normalizeFileName($nameValue, $this->getId());
		$dirName = $nameValue !== '' ? dirname($nameValue) : '';

		if ($dirName !== '' && $dirName !== '.')
		{
			$fileName = rtrim($dirName, '/') . '/' . $fileName;
		}

		return $fileName;
	}

	public function getFileRelativePath()
	{
		$fileName = $this->getFileName();

		if (Market\Data\TextString::getPosition($fileName, '/') === 0)
		{
			$result = $fileName;
		}
		else
		{
			$result = BX_ROOT . '/catalog_export/' . $this->getFileName();
		}

		return $result;
	}

	public function getFileAbsolutePath()
	{
		$relativePath = $this->getFileRelativePath();

		return Main\IO\Path::convertRelativeToAbsolute($relativePath);
	}

	public function isFileReady()
	{
		$path = $this->getFileAbsolutePath();

		return Main\IO\File::isFileExists($path);
	}

	public function getFileUrl()
	{
		return $this->getDomainUrl() . $this->getFileRelativePath();
	}

	public function getDomainUrl($domain = null)
	{
		if ($domain === null)
		{
			$domain = $this->getDomain();
		}

		return 'http' . ($this->isHttps() ? 's' : '') . '://' . $domain;
	}

	public function getDomain()
	{
		$parsedDomain = $this->getDomainParsed();

		if ($parsedDomain !== false)
		{
			$result = $parsedDomain['DOMAIN'];
		}
		else
		{
			$result = $this->getField('DOMAIN');
		}

		return $result;
	}

	public function getDomainPath()
	{
		$parsedDomain = $this->getDomainParsed();

		if ($parsedDomain !== false)
		{
			$result = $parsedDomain['PATH'];
		}
		else
		{
			$result = '';
		}

		return $result;
	}

	protected function getDomainParsed()
	{
		if ($this->domainParsed === null)
		{
			$domain = $this->getField('DOMAIN');

			$this->domainParsed = $this->parseDomain($domain);
		}

		return $this->domainParsed;
	}

	protected function parseDomain($domain)
	{
		$result = false;

		if (preg_match('#^([^/]+)([^?\#]*)(.*)?$#', $domain, $matches))
		{
			$result = [
				'DOMAIN' => $matches[1],
				'PATH' => $matches[2],
				'QUERY' => $matches[3]
			];
		}

		return $result;
	}

	public function isHttps()
	{
		return ($this->getField('HTTPS') === '1');
	}

	public function isAutoDiscountsEnabled()
	{
		return ($this->getField('ENABLE_AUTO_DISCOUNTS') === '1');
	}

	public function isCpaEnabled()
	{
		return ($this->getField('ENABLE_CPA') === '1');
	}

	public function isAutoUpdate()
	{
		return ($this->getField('AUTOUPDATE') === '1');
	}

	public function getRefreshPeriod()
	{
		$period = (int)$this->getField('REFRESH_PERIOD');
		$result = null;

		if ($period > 0)
		{
			$result = $period;
		}

		return $result;
	}

	public function getRefreshTime()
	{
		$value = (string)$this->getField('REFRESH_TIME');
		$result = null;

		if ($value !== '' && preg_match('/^(\d{1,2})(?::(\d{1,2}))?$/', $value, $matches))
		{
			$result = [
				(int)$matches[1], // hour
				(int)$matches[2], // minutes
				0, // seconds
			];
		}

		return $result;
	}

	/** @return \Yandex\Market\Export\IblockLink\Collection */
	public function getIblockLinkCollection()
	{
		return $this->getChildCollection('IBLOCK_LINK');
	}

	/** @return \Yandex\Market\Export\Delivery\Collection */
	public function getDeliveryCollection()
	{
		return $this->getChildCollection('DELIVERY');
	}

    /** @return \Yandex\Market\Export\Collection\Collection */
    public function getCollectionCollection()
    {
        return $this->getChildCollection('COLLECTION');
    }

    /** @return \Yandex\Market\Export\Promo\Collection */
    public function getPromoCollection()
    {
        return $this->getChildCollection('PROMO');
    }

	protected function getChildCollectionReference($fieldKey)
	{
		$result = null;

		switch ($fieldKey)
		{
			case 'IBLOCK_LINK':
				$result = Market\Export\IblockLink\Collection::class;
			break;

			case 'DELIVERY':
				$result = Market\Export\Delivery\Collection::class;
			break;

            case 'PROMO':
                $result = Market\Export\Promo\Collection::class;
            break;

            case 'COLLECTION':
                $result = Market\Export\Collection\Collection::class;
            break;
		}

		return $result;
	}

    protected function getChildCollectionQueryParameters($fieldKey)
    {
        $result = [];

        switch ($fieldKey)
        {
            case 'PROMO':
	        case 'COLLECTION':
				$result['distinct'] = true;
                $result['filter'] = [
                    'LOGIC' => 'OR',
                    [ 'SETUP_LINK.SETUP_ID' => $this->getId() ],
                    [ 'SETUP_EXPORT_ALL' => Market\Export\Promo\Table::BOOLEAN_Y ]
                ];
            break;

            default:
                $result = parent::getChildCollectionQueryParameters($fieldKey);
            break;
        }

        return $result;
    }
}
