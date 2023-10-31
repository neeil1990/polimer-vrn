<?php

namespace Darneo\Ozon\Fields\Config;

use Bitrix\Main\Entity\Base;
use CUser;

abstract class Manager
{
    protected Base $entity;
    protected int $userId;

    public function __construct()
    {
        $this->entity = $this->getEntity();
        $this->userId = (int)(new CUser())->GetID();
    }

    abstract protected function getEntity();

    public function getField($fieldName)
    {
        return $this->getFields()[$fieldName];
    }

    abstract public function getFields();
}
