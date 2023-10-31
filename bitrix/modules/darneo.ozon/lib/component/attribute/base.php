<?php

namespace Darneo\Ozon\Component\Attribute;

class Base
{
    protected string $filterSearch = '';

    protected int $limit = 50;
    protected int $page = 1;
    protected int $totalCount = 0;

    public function setFilterSearch(string $filterSearch): void
    {
        $this->filterSearch = $filterSearch;
    }

    public function setPage(int $page): void
    {
        $this->page = $page;
    }

    protected function setTotalCount(int $totalCount): void
    {
        $this->totalCount = $totalCount;
    }
}
