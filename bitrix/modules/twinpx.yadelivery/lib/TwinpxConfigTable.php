<?php
namespace Twinpx\Yadelivery;

use Bitrix\Main\Localization\Loc,
Bitrix\Main\ORM\Data\DataManager,
Bitrix\Main\ORM\Fields\StringField,
Bitrix\Main\ORM\Fields\TextField,
Bitrix\Main\ORM\Fields\Validators\LengthValidator;

Loc::loadMessages(__FILE__);

class TwinpxConfigTable extends DataManager
{
    /**
    * Returns DB table name for entity.
    *
    * @return string
    */
    public static function getTableName()
    {
        return 'b_twpx_yadelivery_config';
    }

    /**
    * Returns entity map definition.
    *
    * @return array
    */
    public static function getMap()
    {
        return [
            new StringField(
                'CODE',
                [
                    'primary' => true,
                    'validation' => [__CLASS__, 'validateCode'],
                    'title' => Loc::getMessage('YADELIVERY_CONFIG_ENTITY_CODE_FIELD')
                ]
            ),
            new TextField(
                'VALUE',
                [
                    'title' => Loc::getMessage('YADELIVERY_CONFIG_ENTITY_VALUE_FIELD')
                ]
            ),
        ];
    }

    /**
    * Returns validators for CODE field.
    *
    * @return array
    */
    public static function validateCode()
    {
        return [
            new LengthValidator(null, 50),
        ];
    }


    /**
    * собственные методы
    */
    public static function getByCode($code)
    {
        if (!$code) return;

        $res = parent::getRow(array('filter' => array('=CODE'=> $code)));
        $return = $res['VALUE'];

        return $return;

    }

    public static function GetAllOptions()
    {
        //cache
        $cache = \Bitrix\Main\Data\Cache::createInstance();
        if ($cache->initCache(300, md5(__FUNCTION__), 'modules/twinpx.yadelivery/'.__FUNCTION__)) {
            $return = $cache->getVars();
        }
        elseif ($cache->startDataCache()) {
            $return = array();
            if ($isInvalid) {
                $cache->abortDataCache();
            }
            $res = parent::getList(array('select' => array('*'))); //'cache'  => array('ttl'=> 300)
            while ($ar = $res->Fetch())
            {
                $return[$ar['CODE']] = $ar['VALUE'];
            }
            $cache->endDataCache($return);
        }

        return $return;
    }

    public static function CheckSettings()
    {
        $obConfig = parent::getList(array('select' => array('*')));

        while ($arResult = $obConfig->fetch())
        {
            $allSettings[$arResult['CODE']] = $arResult['VALUE'];
        }

        //проверяем если есть ключ
        $result = array(
            'RESULT'=> 'ERROR',
            'TEXT'  => 'Auth key not found'
        );

        //пока проверяем только на наличие данные для авторизации
        if (strlen($allSettings['PlatformId']) > 1 AND strlen($allSettings['OAuth']) > 1)
        {
            $result = array(
                'RESULT'=> 'OK'
            );
        }

        //проверяем расписание активность
        $thisday    = date('N'); //получаем день недели
        $thistime   = time() - strtotime('today'); //получаем секунды с начало дня
        $arSchedule = json_decode($allSettings['Schedule'], true); //получаем массив расписании
        foreach ($arSchedule as $ob)
        {
            $schudule[$ob['day']][] = array('start'=> $ob['start'],'end'  => $ob['end']);
        }

        //сама проверка
        $enable = FALSE; //по умолчание отключена
        if (!empty($schudule[$thisday]))
        {
            foreach ($schudule[$thisday] as $time)
            {
                if ($thistime > $time['start'] && $time['end'] > $thistime)
                {
                    $enable = TRUE;
                }
            }
        }
        //если отклюыена расписание возвращаем ошибку
        if ($enable === FALSE)
        {
            unset($_SESSION['YD_SETPRICE']);
            $result = array(
                'RESULT'=> 'ERROR',
                'VALUE' => 'Schedule disabled'
            );
        }

        return $result;
    }

}