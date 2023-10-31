<?
use Bitrix\Main,
	Zverushki\Microm;
use Bitrix\Main\EventManager;

$request = Main\Context::getCurrent()->getRequest();

$statusCheck = !$request->isAjaxRequest()
    && (!defined('ADMIN_SECTION') && strpos($_SERVER['PHP_SELF'], BX_ROOT.'/admin') !== 0);

if ($statusCheck) {

    if (Microm\Options::entity()->isOpenGraph()) {
        EventManager::getInstance()
            ->addEventHandler('main', 'OnEpilog', function () {
                $pageData = (new \Zverushki\Microm\Services\Page())
                    ->get();

                (new Microm\Services\OpenGraph($pageData))
                    ->handle();
            });
    }

	EventManager::getInstance()
		->addEventHandler('main', 'OnEndBufferContent', function (&$bufferContent) {
            if (defined('ERROR_404')) {
                return true;
            }

            /**
             * @var array $arModuleVersion
             */

			$start = microtime(true);
			include __DIR__.'/install/version.php';

			$MData = new Microm\Data;

			$Options = Microm\Options::entity();
			$resultAStr = array();
			$microm = array();

			foreach (Microm\Options::listType() as $t)
				$microm[ucfirst(strtolower($t))] = 'Disabled';

			$microm['version'] = $arModuleVersion['VERSION'];
			$microm['format'] = array();
			$microm['execute'] = array(
				'time' => 0,
				'scheme' => array()
			);

			foreach (Microm\Options::listFormat() as $f)
				$microm['format'][$f] = 'Disabled';


			foreach ($MData->getStack() as $data) {
				$microm[$data['key']] = strlen($data['status']) > 0;

				foreach ($Options->settingsFormat() as $format) {
					$r = trim($data['template.'.$format]);

					$microm['format'][$format] = true;
					$microm[$data['key']] = strlen($r) > 0
												? true
												: $microm[$data['key']];

					if (strlen($r) > 0) {
						if (!isset($resultAStr[$format]))
							$resultAStr[$format] = '';

						$resultAStr[$format] .= $r;
					}
				}

				$microm['execute']['scheme'][$data['key']] = $data['execute'];
			}

			$pageIsAmp = strpos($bufferContent, '<html amp>') !== false;

//			$microm['execute']['time'] = microtime(true) - $start;

			$resultHeadStr =
						'<!-- Zverushki\Microm -->'
						.(!$pageIsAmp ? '<script data-skip-moving="true">window.Zverushki=window.Zverushki||{};window.Zverushki.Microm='.json_encode($microm).';</script>' : '')
						.(strlen($resultAStr['json-ld']) ? $resultAStr['json-ld'] : '')
						.'<!-- end Zverushki\Microm -->';

			$bufferContent = preg_replace('/<\/head>/', $resultHeadStr.'</head>', $bufferContent, 1);

			if (strlen($resultAStr['microdata'])) {
				$resultBodyStr =
							'<!-- Zverushki\Microm -->'
							.$resultAStr['microdata']
							.'<!-- end Zverushki\Microm -->';

				$bufferContent = preg_replace('/<\/body>/', $resultBodyStr.'</body>', $bufferContent, 1);
			}
		});
}

if (defined('ADMIN_SECTION') && strpos($_SERVER['PHP_SELF'], BX_ROOT.'/admin') === 0) {
	EventManager::getInstance()
		->addEventHandler('main', 'OnAdminTabControlBegin', array('\\Zverushki\\Microm\\Customibtab', 'addTab'));

	EventManager::getInstance()
		->addEventHandler('iblock', 'OnBeforeIBlockUpdate', array('\\Zverushki\\Microm\\Customibtab', 'saveSettingArticle'));
}
?>