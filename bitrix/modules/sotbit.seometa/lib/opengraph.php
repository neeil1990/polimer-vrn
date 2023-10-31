<?

namespace Sotbit\Seometa;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class OpengraphTable extends
    \DataManagerEx_SeoMeta
{
    private static string $prefix = 'OG_FIELD_';
    private static array $settingFields = [
        'TYPE',
        'LOCALE',
        'SITE_NAME',
        'URL',
        'IMAGE',
        'TITLE',
        'DESCRIPTION'
    ];

    public static function getFilePath(
    ) {
        return __FILE__;
    }

    public static function getTableName(
    ) {
        return 'b_sotbit_seometa_og';
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
        $result = self::getList([
            'filter' => [
                'CONDITION_ID' => $conditionID
            ],
            'select' => [
                '*'
            ]
        ])->fetch();

        if ($result['SETTINGS']) {
            $settings = unserialize($result['SETTINGS']);
            unset($result['SETTINGS']);
            if (is_array($settings)) {
                $result = array_merge($result,
                    $settings);
            }

            $result[self::$prefix . 'ACTIVE'] = $result['ACTIVE'];
            unset($result['ACTIVE']);
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
