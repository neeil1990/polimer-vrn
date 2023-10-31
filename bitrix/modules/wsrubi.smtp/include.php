<?php
/**
 * @link      http://wsrubi.ru/dev/bitrixsmtp/
 * @author Sergey Blazheev <s.blazheev@gmail.com>
 * @copyright Copyright (c) 2011-2017 Altair TK. (http://www.wsrubi.ru)
 */
	CModule::AddAutoloadClasses('wsrubi.smtp',
		array(
			'Wsrubismtp' => 'classes/general/wsrubismtp.php',
		)
	);