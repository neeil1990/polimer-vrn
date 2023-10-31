<?php
namespace Yandex\Market\Watcher\Track;

use Yandex\Market\Export\Entity;

class SourceInstaller
{
	private $serviceType;
	private $entityType;
	private $entityId;
	
	public function __construct($serviceType, $entityType, $entityId)
	{
		$this->serviceType = $serviceType;
		$this->entityType = $entityType;
		$this->entityId = $entityId;
	}

	public function install(array $sources)
    {
	    $sources = $this->unsetSilent($sources);
	    $stored = $this->stored();

		$new = $this->diff($sources, $stored);
		$old = $this->diff($stored, $sources);

		$this->add($new);
		$this->remove($old);

	    $this->bind($new);
	    $this->unbind($this->filterSomeoneUsed($old));
    }

    public function uninstall()
    {
        $stored = $this->stored();

        $this->remove($stored);
	    $this->unbind($this->filterSomeoneUsed($stored));
    }

	private function unsetSilent(array $sources)
	{
		foreach ($sources as $sourceKey => $source)
		{
			$event = Entity\Manager::getEvent($source['SOURCE_TYPE']);

			if ($event->isSilent($source['SOURCE_PARAMS']))
			{
				unset($sources[$sourceKey]);
			}
		}

		return $sources;
	}

    private function stored()
    {
        $result = [];

        $query = SourceTable::getList([
            'filter' => [
				'=SERVICE' => $this->serviceType,
                '=ENTITY_TYPE' => $this->entityType,
                '=ENTITY_ID' => $this->entityId
            ],
	        'select' => [
				'ID',
				'SOURCE_TYPE',
		        'SOURCE_PARAMS',
	        ],
        ]);

        while ($row = $query->fetch())
        {
            $result[] = $row;
        }

        return $result;
    }

	private function diff(array $aRows, array $bRows)
	{
		$result = [];

		foreach ($aRows as $aRow)
		{
			$found = false;

			foreach ($bRows as $bRow)
			{
				/** @noinspection TypeUnsafeComparisonInspection */
				if (
					$aRow['SOURCE_TYPE'] === $bRow['SOURCE_TYPE']
					&& $aRow['SOURCE_PARAMS'] == $bRow['SOURCE_PARAMS']
				)
				{
					$found = true;
					break;
				}
			}

			if ($found) { continue; }

			$result[] = $aRow;
		}

		return $result;
	}

	private function add(array $rows)
	{
		foreach ($rows as $row)
		{
			SourceTable::add([
				'SERVICE' => $this->serviceType,
				'ENTITY_TYPE' => $this->entityType,
				'ENTITY_ID' => $this->entityId,
				'SOURCE_TYPE' => $row['SOURCE_TYPE'],
				'SOURCE_PARAMS' => $row['SOURCE_PARAMS'],
			]);
		}
	}
	
	private function remove(array $rows)
	{
		foreach ($rows as $row)
		{
			SourceTable::delete($row['ID']);
		}
	}

	private function bind(array $rows)
	{
		foreach ($rows as $row)
		{
			$event = Entity\Manager::getEvent($row['SOURCE_TYPE']);

			$event->handleChanges(true, $row['SOURCE_PARAMS']);
		}
	}

	private function filterSomeoneUsed(array $rows)
	{
		$used = $this->someoneUsed($rows);

		return $this->diff($rows, $used);
	}

	private function someoneUsed(array $rows)
	{
		$types = array_column($rows, 'SOURCE_TYPE', 'SOURCE_TYPE');

		if (empty($types)) { return []; }

		$query = SourceTable::getList([
			'filter' => [ '=SOURCE_TYPE' => array_values($types) ],
			'select' => [
				'ID',
				'SOURCE_TYPE',
				'SOURCE_PARAMS',
			],
		]);

		return $query->fetchAll();
	}

	private function unbind(array $rows)
	{
		foreach ($rows as $row)
		{
			$event = Entity\Manager::getEvent($row['SOURCE_TYPE']);

			$event->handleChanges(false, $row['SOURCE_PARAMS']);
		}
	}
}