<?php

namespace Zverushki\Microm\Entities;

/**
 *
 */
class PageRule
{
    /**
     * @var array
     */
    private $values;

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->values = $data;
    }

    /**
     * @return mixed|null
     */
    public function condition()
    {
        return $this->values['condition'] ?? null;
    }

    /**
     * @return mixed|null
     */
    public function variables()
    {
        return $this->values['variables'] ?? [];
    }

    /**
     * @return mixed|null
     */
    public function data()
    {
        return $this->values['data'] ?? [];
    }
}