<?php
namespace Yandex\Market\Watcher\Track;

use Yandex\Market\Glossary;
use Yandex\Market\Watcher\Agent;

class Installer
{
	private $track;
	private $bind;
	private $serviceType;
	private $ownerType;
	private $ownerId;

	public function __construct($serviceType, $ownerType, $ownerId)
	{
		$this->serviceType = $serviceType;
		$this->ownerType = $ownerType;
		$this->ownerId = $ownerId;

		$this->track = new SourceInstaller($serviceType, $ownerType, $ownerId);
		$this->bind = new BindInstaller($serviceType, $ownerType, $ownerId);
	}

	public function install(array $sources, array $entities)
	{
		if (empty($entities) && (empty($sources) || $this->isRootEntity()))
		{
			$this->uninstall();
			return;
		}

		$this->track->install($sources);
		$this->bind->install($entities);

		$this->installState();
	}

	public function uninstall()
	{
		$this->track->uninstall();
		$this->bind->uninstall();

		$this->dropAgent();
		$this->dropState();
	}

	private function isRootEntity()
	{
		return ($this->ownerType === Glossary::ENTITY_SETUP);
	}

	private function dropAgent()
	{
		if (!$this->isRootEntity()) { return; }

		Agent\StateFacade::drop('change', $this->serviceType, $this->ownerId);
	}

	private function installState()
	{
		if (!$this->isRootEntity()) { return; }

		$state = new StampState($this->serviceType, $this->ownerId);
		$state->start();
	}

	private function dropState()
	{
		if (!$this->isRootEntity()) { return; }

		$state = new StampState($this->serviceType, $this->ownerId);
		$state->drop();
	}
}