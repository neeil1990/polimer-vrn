<?
namespace Wbs24\Sbermmexport;

use Bitrix\Main\Loader;

class Autoload
{
    public function getModuleId()
    {
        return basename(__DIR__);
    }

    public function getModuleNamespace()
    {
        $moduleId = $this->getModuleId();
        $names = explode(".", $moduleId);
        $namespace = "";

        foreach ($names as $name) {
            $namespace .= "\\".ucfirst($name);
        }

        return $namespace;
    }

    public function getModuleClasses($path = 'lib')
    {
        $libPath = $path."/";
        $libFiles = scandir(__DIR__."/".$libPath);
        $namespace = $this->getModuleNamespace();
        $moduleClasses = [];

        foreach ($libFiles as $libName) {
            if (substr($libName, -4) != ".php") continue;
            $class = $namespace."\\".substr($libName, 0, -4);
            $moduleClasses[$class] = $libPath.$libName;
        }

        return $moduleClasses;
    }


}

$autoload = new Autoload;

Loader::registerAutoLoadClasses(
    $autoload->getModuleId(),
    $autoload->getModuleClasses()
);

Loader::registerAutoLoadClasses(
    $autoload->getModuleId(),
    $autoload->getModuleClasses('lib/wrappers')
);
