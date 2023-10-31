<?php

namespace Darneo\Ozon\Main\Helper;

use CUser;
use Darneo\Ozon\Main\Table\AccessTable;

class Access
{
    public static function isPermission(): bool
    {
        global $USER;
        if ($USER->IsAdmin()) {
            return true;
        }

        $userId = (new CUser())->GetID();
        $arGroups = CUser::GetUserGroup($userId);

        $parameters = ['select' => ['GROUP_ID']];
        $result = AccessTable::getList($parameters);
        while ($row = $result->fetch()) {
            if (in_array($row['GROUP_ID'], $arGroups, true)) {
                return true;
            }
        }

        return false;
    }
}