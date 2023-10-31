<?

namespace Sotbit\Seometa\Orm;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class TwitterCardTable extends
    \DataManagerEx_SeoMeta
{
    private static string $prefix = 'TW_FIELD_';
    private static array $settingFields = [
        'CARD',
        'SITE',
        'DESCRIPTION',
        'IMAGE',
        'TITLE'
    ];

    public static function getFilePath(
    ) {
        return __FILE__;
    }

    public static function getTableName(
    ) {
        return 'b_sotbit_seometa_tw';
    }

    public static function getMap(
    ) {
        return array(
            new Entity\IntegerField('ID',
                array(
                    'primary' => true,
                    'autocomplete' => true
                )),
            new Entity\IntegerField('CONDITION_ID',
                array(
                    'required' => true,
                    'title' => Loc::getMessage('SEOMETA_CONDITION_ID')
                )),
            new Entity\BooleanField('ACTIVE',
                array(
                    'values' => array(
                        'N',
                        'Y'
                    ),
                    'title' => Loc::getMessage('SEOMETA_ACTIVE')
                )),
            new Entity\TextField('SETTINGS',
                array(
                    'title' => Loc::getMessage('SEOMETA_SETTINGS')
                ))
        );
    }

    public static function getByConditionID(
        int $conditionID
    ) {
        $result = [];
        $res = self::getList([
            'filter' => [
                'CONDITION_ID' => $conditionID
            ],
            'select' => [
                '*'
            ]
        ])->fetch();

        if ($res['SETTINGS']) {
            $settings = unserialize($res['SETTINGS']);
            unset($res['SETTINGS']);
            if (is_array($settings)) {
                $res = array_merge($res, $settings);
            }

            $res[self::$prefix . 'ACTIVE'] = $res['ACTIVE'];
            unset($res['ACTIVE']);
            $result = $res;
        }

        return $result;
    }

    public static function getDefaultParams(
    ): array {
        $result = [];

        foreach (self::getMap() as $field) {
            $result[self::$prefix . $field->getColumnName()] = $field->getDefaultValue();
        }

        foreach (self::$settingFields as $settingField) {
            $result[self::$prefix . $settingField] = '';
        }

        return $result;
    }
}
