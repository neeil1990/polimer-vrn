<?php

namespace Corsik\YaDelivery\Admin;

use CJSCore;
use Bitrix\Main\Localization\Loc;
use Corsik\YaDelivery\Options;

class Scripts
{
    private static $epilogAdminJS = "/bitrix/js/corsik.yadelivery/admin/admin.epilog.js";

    public static function getAdminMainJS()
    {
        ?>
        <script type="text/javascript"
                src="/bitrix/js/<?= Loc::getMessage("CORSIK_DELIVERY_SERVICE_MENU_ID") ?>/admin/admin.main.js"></script>
        <?
    }

    static function registerEpilogExtJs()
    {
        $registerName = 'corsik.onEpilog';
        if (!CJSCore::IsExtRegistered($registerName)) {
            CJSCore::RegisterExt(
                $registerName,
                [
                    "js" => self::$epilogAdminJS,
                    'oninit' => function () {
                        return [
                            'lang_additional' => [
                                'api_key_dadata' => Options::getOptionByName("api_key_dadata"),
                                'type_prompts' => Options::getOptionByName("type_prompts"),
                            ]
                        ];
                    }
                ]
            );
        }
        CJSCore::Init([$registerName]);
    }
}