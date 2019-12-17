<?
use Bitrix\Main,
	Zverushki\Microm;


if (($statusCheck = (!defined('ADMIN_SECTION') && strpos($_SERVER['PHP_SELF'], BX_ROOT.'/admin') !== 0))) {
	Main\EventManager::getInstance()
		->addEventHandler('main', 'OnEndBufferContent', array('ZverushkiMicromData', 'pastResult'));
}

if (defined('ADMIN_SECTION') && strpos($_SERVER['PHP_SELF'], BX_ROOT.'/admin') === 0) {
	Main\EventManager::getInstance()
		->addEventHandler('main', 'OnAdminTabControlBegin', array('\\Zverushki\\Microm\\Customibtab', 'addTab'));

	Main\EventManager::getInstance()
		->addEventHandler('iblock', 'OnBeforeIBlockUpdate', array('\\Zverushki\\Microm\\Customibtab', 'saveSettingArticle'));
}

if ($statusCheck) {
	class ZverushkiMicromData extends Microm\Data {

		function __construct () {
			parent::__construct();
		}

		public static function pastResult (&$bufferContent) {
			new self;

			$Options = Microm\Options::entity();
			$resultAStr = array();
			$microm = array();

			foreach (Microm\Options::listType() as $t)
				$microm[ucfirst(strtolower($t))] = 'Disabled';

			$microm['format'] = array();
			foreach (Microm\Options::listFormat() as $f)
				$microm['format'][$f] = 'Disabled';

			foreach (static::$stack as $data) {
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
			}

			$resultHeadStr =
						'<!-- Zverushki\Microm -->'
						.'<script>window.Zverushki=window.Zverushki||{};window.Zverushki.Microm='.json_encode($microm).';</script>'
						.(strlen($resultAStr['microdata']) ? '<style>[itemscope]{display:none}</style>' : '')
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
		} // end function pastResult

		protected function setData () {
			$Options = Microm\Options::entity();

			foreach ($Options->settingsType() as $type) {
				$ClassName = $type['ClassName'];

				if (class_exists($ClassName))
					static::$stack[$type['key']] = $ClassName::getInstance($type['options'])->get();
			}
		} // end function setData

	} // end class ZverushkiMicromData
}
?>