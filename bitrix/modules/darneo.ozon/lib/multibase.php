<?php

namespace Darneo\Ozon;

use Bitrix\Main\ORM\Entity;

abstract class MultiBase
{
    protected Entity $entityTable;
    protected string $entityName;
    protected string $entityDataClass;

    public function __construct()
    {
        $this->entityTable = $this->getEntity();
        $this->entityName = $this->entityTable->getName();
        $this->entityDataClass = $this->entityTable->getDataClass();
    }

    abstract protected function getEntity();

    public function add(array $data)
    {
        return $this->entityDataClass::add($data);
    }

    public function update(int $id, array $data)
    {
        return $this->entityDataClass::update($id, $data);
    }

    public function delete(int $id)
    {
        return $this->entityDataClass::delete($id);
    }

    public function getList(array $parameters = [])
    {
        return $this->entityDataClass::getList($parameters);
    }

    public function getCount(array $filter = []): int
    {
        return $this->entityDataClass::getCount($filter);
    }
}
