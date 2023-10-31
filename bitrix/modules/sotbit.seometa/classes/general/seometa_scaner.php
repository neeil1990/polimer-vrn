<?

use Sotbit\Seometa\Orm\SeometaUrlTable;

class CSeoMetaScaner
{
	public static function Scan(&$lastID, &$percent)
	{
		$limit = 5;
		$lastID = intval($lastID);
		$limit = intval($limit);

		$httpClient = new \Bitrix\Main\Web\HttpClient();
		$protocol = empty($_SERVER['HTTPS']) ? 'http://' : 'https://';

		$countAll = SeometaUrlTable::getCount();

		if ($countAll > 0)
		{
			$rsSearch = SeometaUrlTable::getPartOfURLs($lastID, $limit);
			
			while ($arSearch = $rsSearch->Fetch())
			{
				$lastID = $arSearch['ID'];
				$url = $protocol . $_SERVER["SERVER_NAME"] . $arSearch['REAL_URL'];

				if($httpClient->post($url, ["SEOMETA_STATUS_CODE" => "Y"]))
				{
					$status = $httpClient->getStatus();
					$result = $httpClient->getResult();

					$arPageInfo = array();
					$arPageInfo['STATUS'] = $status;

					if (preg_match_all('#<meta\s+name="(keywords|description)"\s+content="([^"]+)"#is', $result, $matches))
					{
						foreach ($matches[1] as $i => $k)
						{
							$arPageInfo[mb_strtoupper($k)] = htmlspecialcharsBack($matches[2][$i]);
						}
					}

					if (preg_match_all('#<title>(.*?)</title>#is', $result, $matches))
					{
						$arPageInfo['TITLE'] = htmlspecialcharsBack($matches[1][0]);
					}

					$arPageInfo['DATE_SCAN'] = new \Bitrix\Main\Type\DateTime(date('Y-m-d H:i:s'), 'Y-m-d H:i:s');

					SeometaUrlTable::update($arSearch['ID'], $arPageInfo);
				}

				sleep(1);
			}

			$countWorked = SeometaUrlTable::getCount(array('<=ID' => $lastID));

			if ($countAll <= $limit)
			{
				$percent = 100;
			}
			else
			{
				$percent = round($countWorked/$countAll * 100, 2);
			}
		}
		else
		{
			$percent = 100;
		}
	}
}
