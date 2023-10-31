<?php
namespace Yandex\Market\Watcher\Track;

use Bitrix\Main;
use Yandex\Market\Glossary;

class BindInstaller
{
	private $serviceType;
	private $ownerType;
	private $ownerId;
	
	public function __construct($serviceType, $ownerType, $ownerId)
	{
		$this->serviceType = $serviceType;
		$this->ownerType = $ownerType;
		$this->ownerId = $ownerId;
	}

	/** @param BindEntity[] $entities */
	public function install(array $entities)
    {
		$prepared = $this->prepare($entities);
        $stored = $this->stored();
		$new = $this->diff($prepared, $stored);
		$old = $this->diff($stored, $prepared);
		
		$this->add($new);
		$this->remove($old);
    }

    public function uninstall()
    {
        $stored = $this->stored();

        $this->remove($stored);
    }

	/** @param BindEntity[] $entities */
	private function prepare(array $entities)
	{
		$result = [];
		
		foreach ($entities as $entity)
		{
			$row = [
				'SETUP_ID' => $this->entitySetup($entity),
				'ELEMENT_TYPE' => $entity->type(),
				'ELEMENT_GROUP' => $entity->group() ?: 0,
				'REPLACE_TYPE' => $entity->replaceType() ?: $entity->type(),
			];
			$sign = $row['ELEMENT_TYPE'] . ':' . $row['ELEMENT_GROUP'] . ':' . $row['REPLACE_TYPE'];

			$result[$sign] = $row;
		}

		return array_values($result);
	}

	private function entitySetup(BindEntity $entity)
	{
		$configured = $entity->setupId();

		if ($configured !== null)
		{
			return $configured;
		}

		if ($this->ownerType !== Glossary::ENTITY_SETUP)
		{
			throw new Main\ArgumentException('pass setupIds for binder');
		}

		return $this->ownerId;
	}

    private function stored()
    {
        $result = [];

        $query = BindTable::getList([
            'filter' => [
				'=SERVICE' => $this->serviceType,
                '=OWNER_TYPE' => $this->ownerType,
                '=OWNER_ID' => $this->ownerId,
            ],
	        'select' => [
				'ID',
				'SETUP_ID',
				'ELEMENT_TYPE',
		        'ELEMENT_GROUP',
	        ],
        ]);

        while ($row = $query->fetch())
        {
            $result[] = $row;
        }

        return $result;
    }
	
	private function add(array $rows)
	{
		foreach ($rows as $row)
		{
			BindTable::add([
				'SERVICE' => $this->serviceType,
				'OWNER_TYPE' => $this->ownerType,
				'OWNER_ID' => $this->ownerId,
				'SETUP_ID' => $row['SETUP_ID'],
				'ELEMENT_TYPE' => $row['ELEMENT_TYPE'],
				'ELEMENT_GROUP' => $row['ELEMENT_GROUP'],
				'REPLACE_TYPE' => $row['REPLACE_TYPE'],
			]);
		}
	}
	
	private function remove(array $rows)
	{
		foreach ($rows as $row)
		{
			BindTable::delete($row['ID']);
		}
	}
	
	private function diff(array $aRows, array $bRows)
	{
		$result = [];

		foreach ($aRows as $aRow)
		{
			$found = false;

			foreach ($bRows as $bRow)
			{
				if (
					(int)$aRow['SETUP_ID'] === (int)$bRow['SETUP_ID']
					&& (string)$aRow['ELEMENT_TYPE'] === (string)$bRow['ELEMENT_TYPE']
					&& (string)$aRow['ELEMENT_GROUP'] === (string)$bRow['ELEMENT_GROUP']
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
}