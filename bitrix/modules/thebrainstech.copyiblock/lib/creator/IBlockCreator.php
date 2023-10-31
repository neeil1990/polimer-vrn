<?php

abstract class IBlockCreator
{
    public $IBlockID;
    public $mainIBlock;
    public $error = [];

    abstract public function create();
}
