<?php

namespace Darneo\Ozon\EventHandlers\Orm;

use Bitrix\Main\ORM;

abstract class Base
{
    protected ORM\Event $event;
    protected ORM\EventResult $eventResult;
    protected ORM\Entity $entity;
    protected string $dataClassName;

    protected array $eventParameters;
    protected $primaryName;
    protected int $rowId;
    protected array $modifiedFields = [];

    public function __construct(ORM\Event $event, ORM\EventResult $previousEventResult = null)
    {
        $this->event = $event;
        $this->eventResult = $previousEventResult ?? new ORM\EventResult();
        $this->entity = $event->getEntity();
        $this->eventParameters = $this->event->getParameters();

        $this->dataClassName = $this->entity->getDataClass();
        $this->primaryName = array_shift($this->entity->getPrimaryArray());
        $this->rowId = $this->eventParameters['primary'][$this->primaryName];
    }

    public function getResult(): ORM\EventResult
    {
        if (count($this->modifiedFields) > 0) {
            $this->eventResult->modifyFields($this->modifiedFields);
        }

        return $this->eventResult;
    }
}
