<?php

namespace Zverushki\Microm\Entities;

use Bitrix\Main\Context;
use CFile;

/**
 *
 */
class PageData
{
    /**
     * @var array
     */
    private $values;

    /**
     * @param array $values
     */
    public function __construct(array $values)
    {
        $this->values = $values;
    }

    /**
     * @return array|null
     */
    public function picture(): ?array
    {
        if (!$this->values['picture']) {
            return null;
        }

        $server = Context::getCurrent()->getServer();
        $path = CFile::GetPath($this->values['picture']);

        if (!file_exists($server->getDocumentRoot().$path)) {
            return null;
        }

        return [
            'path' => $path
        ];
    }
}