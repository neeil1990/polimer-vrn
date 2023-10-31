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
    * ����������� ������
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

        //��������� ���� ���� ����
        $result = array(
            'RESULT'=> 'ERROR',
            'TEXT'  => 'Auth key not found'
        );

        //���� ��������� ������ �� ������� ������ ��� �����������
        if (strlen($allSettings['PlatformId']) > 1 AND strlen($allSettings['OAuth']) > 1)
        {
            $result = array(
                'RESULT'=> 'OK'
            );
        }

        //��������� ���������� ����������
        $thisday    = date('N'); //�������� ���� ������
        $thistime   = time() - strtotime('today'); //�������� ������� � ������ ���
        $arSchedule = json_decode($allSettings['Schedule'], true); //�������� ������ ����������
        foreach ($arSchedule as $ob)
        {
            $schudule[$ob['day']][] = array('start'=> $ob['start'],'end'  => $ob['end']);
        }

        //���� ��������
        $enable = FALSE; //�� ��������� ���������
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
        //���� ��������� ���������� ���������� ������
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