<?php
namespace Wbs24\Ozonexport;

class CUpdateClientPartner
{
    public function GetUpdatesList(...$args)
    {
        if (!class_exists('\CUpdateClientPartner')) return [];

        return \CUpdateClientPartner::GetUpdatesList(...$args);
    }
}
