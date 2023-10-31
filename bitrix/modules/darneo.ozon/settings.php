<?php

class DarneoSettingsOzon
{
    private static int $keyId = 0;

    public static function getKeyId(): int
    {
        return self::$keyId;
    }

    public static function setKeyId(int $keyId): void
    {
        self::$keyId = $keyId;
    }
}