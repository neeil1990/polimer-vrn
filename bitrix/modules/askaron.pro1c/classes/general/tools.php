<?
class CAskaronPro1CTools
{
	static public function OnPageStartDebugSettings()
	{
		//global $APPLICATION;

		if ( !defined("ADMIN_SECTION") || ADMIN_SECTION !== true )
		{
			// do not use default template
			define('SITE_TEMPLATE_ID', 'askaron_pro1c_empty');
			define("SITE_TEMPLATE_PATH", "/bitrix/templates/askaron_pro1c_empty");
		}

		// strange code for future connections
		// display mysql errors. Before 14.0.0
		global $DBDebug;
		$DBDebug = true;

		// display errors. Since 14.0.0
		if ( CheckVersion( SM_VERSION, '14.0.0' ) )
		{
			$exception_handling = \Bitrix\Main\Config\Configuration::getValue("exception_handling");

			if ( !is_array( $exception_handling ) )
			{
				$exception_handling = array();
			}

			$exception_handling["debug"] = true;

			$obConfig = \Bitrix\Main\Config\Configuration::getInstance();
			$obConfig->delete("exception_handling");
			$obConfig->add("exception_handling", $exception_handling);
		}
		// end strange code


		// display mysql errors
		global $DB;
		if ( is_object( $DB ) )
		{
			$DB->debug = true;
		}


		$time_limit = COption::GetOptionString("askaron.pro1c", "time_limit");
		if ( strlen( $time_limit ) > 0 )
		{
			@set_time_limit( $time_limit );
		}

		$memory_limit = COption::GetOptionString("askaron.pro1c", "memory_limit");
		if ( strlen( $memory_limit ) > 0 )
		{
			$memory_limit_value = intval( $memory_limit );
			if ( $memory_limit_value > 0 )
			{
				@ini_set( "memory_limit", $memory_limit."M" );
			}
			elseif ( $memory_limit_value == -1 )
			{
				@ini_set( "memory_limit", -1 );
			}
		}

		//:TODO
		//ini_get("session.gc_maxlifetime");
	}
}
?>
