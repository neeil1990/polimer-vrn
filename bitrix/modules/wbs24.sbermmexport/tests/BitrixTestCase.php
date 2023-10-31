<?php
namespace Wbs24\Sbermmexport;

use PHPUnit\Framework\TestCase;

class BitrixTestCase extends TestCase {
    protected $backupGlobals = false;

    protected function getMethod($className, $methodName)
    {
        $class = new \ReflectionClass($className);
        $method = $class->getMethod($methodName);
        $method->setAccessible(true);

        return $method;
    }
}
