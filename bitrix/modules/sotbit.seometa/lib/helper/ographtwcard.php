<?php

namespace Sotbit\Seometa\Helper;

class OGraphTWCard
{
    private static array $arrData = [];
    private array $metaTemplates = [
        'OG_FIELD_' => 'og:',
        'TW_FIELD_' => 'twitter:'
    ];

    public function setData(
        $name,
        $value
    ) {
        if ($name) {
            if ($value && mb_stripos($name,
                    'image') !== false) {
                $this->getImageMeta($this->replaceName($name),
                    $value);
            } else {
                if ($value) {
                    self::$arrData[$this->replaceName($name)] = $value;
                }
            }
        }
    }

    public function replaceName(
        $name
    ): string {
        foreach ($this->metaTemplates as $key => $replace) {
            if (mb_stripos($name,
                    $key) !== false) {
                return mb_strtolower(str_replace($key,
                    $replace,
                    $name));
            }
        }
    }

    public function createMeta(
        string $name,
        string $value
    ): string {
        return '<meta property="' . $name . '" content="' . ($value ?: '') . '">';
    }

    public function getImageMeta(
        $nameProperty,
        $value
    ) {
        if (mb_stripos($nameProperty,
                '_descr') === false) {
            $value = \CFile::GetFileArray($value)['SRC'];

            if ($this->isLocalUrl($value)) {
                $value = $this->getHttpSchema() . '://' . $_SERVER['SERVER_NAME'] . $value;
            }
        }

        self::$arrData[$nameProperty] = $value;
    }

    private function isLocalUrl(
        $url
    ) {
        return !preg_match("/^(http|https|ftp)?(:?\/\/)?([A-Z0-9][A-Z0-9_-]*(?:\..[A-Z0-9][A-Z0-9_-]*)+):?(d+)?\/?/Diu",
            $url);
    }

    public function getHttpSchema(
    ) {
        return ($_SERVER['HTTPS'] && $_SERVER['HTTPS'] != 'off') ? 'https' : 'http';
    }

    public function getData() {
        return self::$arrData ?: [];
    }
}