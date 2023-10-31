<?
class CAskaronPro1CCache
{
	private static $module_id = "askaron.pro1c";

	static public function SetAgentByOptions()
	{
		$agent_enabled = COption::GetOptionString( self::$module_id, "clear_tag_cache_agent_enabled" );
		$interval_seconds = COption::GetOptionString( self::$module_id, "clear_tag_cache_agent_interval" ) * 60;
		if (!$interval_seconds)
		{
			$interval_seconds = 15*60;
		}

		$agent_name = "CAskaronPro1CCache::ClearManagedCacheAgent();";

		$arFilter = array(
			"NAME" => $agent_name
		);
		$arAgent = CAgent::GetList(array("SORT" => "ASC"), $arFilter)->Fetch();
		if ( $arAgent )
		{
			$arFields = array(
				"AGENT_INTERVAL" => $interval_seconds,
				"ACTIVE" => $agent_enabled
			);

			if (
				$arAgent["ACTIVE"] != $arFields["ACTIVE"]
				||
				$arAgent["AGENT_INTERVAL"] != $arFields["AGENT_INTERVAL"]
			)
			{
				$arFields["NEXT_EXEC"] = ConvertTimeStamp( false, "FULL" );
			}

			$obAgent = new CAgent;
			$obAgent->Update( $arAgent["ID"], $arFields );
		}
		else
		{
			CAgent::AddAgent(
				$agent_name,
				self::$module_id,
				"N",
				$interval_seconds,
				"",
				$agent_enabled,
				"",
				100
			);
		}
	}

	static public function ClearManagedCacheAgent()
	{
		self::ClearManagedCache();
		return "CAskaronPro1CCache::ClearManagedCacheAgent();";
	}

	static private function ClearManagedCache()
	{
		$timestamp_last_exec = COption::GetOptionString( self::$module_id, "clear_tag_cache_agent_last_timestamp" );
		$timestamp_current_exec = time();

		$date_last_exec = "";
		if ($timestamp_last_exec)
		{
			$date_last_exec = ConvertTimeStamp( $timestamp_last_exec, "FULL" );
		}

		$arIblockId = self::GetModifiedIblockIdArray( $date_last_exec );
		if ($arIblockId)
		{
			// hard clear cache
			foreach ( $arIblockId as $iblock_id )
			{
				if (defined("BX_COMP_MANAGED_CACHE") && is_object($GLOBALS["CACHE_MANAGER"]))
				{
					$GLOBALS["CACHE_MANAGER"]->ClearByTag("iblock_id_".$iblock_id);
				}
			}
		}

		COption::SetOptionString( self::$module_id, "clear_tag_cache_agent_last_timestamp", $timestamp_current_exec );

		if ($arIblockId)
		{
			if (COption::GetOptionString(self::$module_id, "clear_tag_cache_agent_main_page") == "Y")
			{
				$arLoadPages = self::GetMainPages();
				self::LoadPages($arLoadPages);
			}
		}
	}

	static private function GetMainPages()
	{
		$arResult = array();

		$rsSites = CSite::GetList($by="sort", $order="desc", Array("ACTIVE " => "Y"));
		while ($arSite = $rsSites->Fetch())
		{
			if ( strlen( $arSite[ "SERVER_NAME" ] ) > 0 )
			{
				$path = "http://".$arSite[ "SERVER_NAME" ];
				if ( strlen( $arSite["DIR"] ) > 0 &&  $arSite["DIR"] !== "/" )
				{
					$path .= $arSite["DIR"];
				}

				$arResult[] = $path;
			}
		}

		return $arResult;
	}

	static private function LoadPages( $arPages )
	{
		foreach ($arPages as $page )
		{
			// try load page and create new cache
			$http = new Bitrix\Main\Web\HttpClient(
				array(
					//"waitResponse" => false, // small DDOS if false
				)
			);

			$arCookies = array(
				//"BITRIX_SM_NCC" => "Y",
				COption::GetOptionString("main", "cookie_name", "BITRIX_SM")."_"."NCC" => "Y",
			);

			$http->setCookies($arCookies);

			$http->setHeader("User-Agent", self::$module_id." clear cache agent", $replace = true);

			$http_result = $http->get($page);
		}
	}


	static private function GetModifiedIblockIdArray( $date_last_exec = null )
	{
		$arResult = array();

		if ( CModule::IncludeModule( "iblock" ) )
		{
			$arFilterAll = array();
			if ( strlen( $date_last_exec ) > 0 )
			{
				$arFilterAll[ ">=TIMESTAMP_X" ] = $date_last_exec;
//				$arFilterAll[ "<=TIMESTAMP_X" ] = new \Bitrix\Main\DB\SqlExpression(
//					\Bitrix\Main\Application::getConnection()->getSqlHelper()->getCurrentDateTimeFunction()
//				);
				//$arFilterAll[ ">=TIMESTAMP_X" ] = new \Bitrix\Main\Type\DateTime($date_last_exec);
			}


			// IBLOCKS
			$res = \Bitrix\Iblock\IblockTable::getList(
				array(
					'select' => array( "ID", 'TIMESTAMP_X'),
					'filter' => $arFilterAll,
				)
			);
			while($arFields = $res->fetch())
			{
				$arResult[ $arFields["ID"] ] = $arFields["ID"];
			}

			// SECTIONS
			$res = \Bitrix\Iblock\SectionTable::getList(
				array(
					'group' => array('IBLOCK_ID'),
					//'select' => array('IBLOCK_ID'),
					'select' => array('IBLOCK_ID', 'CNT'),
					'filter' => $arFilterAll,
					'runtime' => array(
						new \Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(*)')
					)
				)
			);
			while($arFields = $res->fetch())
			{
				$arResult[ $arFields["IBLOCK_ID"] ] = $arFields["IBLOCK_ID"];
			}

			// ELEMENTS
			$res = \Bitrix\Iblock\ElementTable::getList(
				array(
					'group' => array('IBLOCK_ID'),
					//'select' => array('IBLOCK_ID'),
					'select' => array('IBLOCK_ID', 'CNT'),
					'filter' => $arFilterAll,
					'runtime' => array(
						new \Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(*)')
					)
				)
			);
			while($arFields = $res->fetch())
			{
				$arResult[ $arFields["IBLOCK_ID"] ] = $arFields["IBLOCK_ID"];
			}
		}

		return $arResult;
	}
}
?>
