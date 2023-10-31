<?php

namespace Yandex\Market\Api\Reference\Internals;

use Bitrix\Main;
use Yandex\Market\Export\Run\Helper\BinaryString;

class HttpClient extends Main\Web\HttpClient
{
	/* fix chucked response reading (mbstring.func_overload=2 for new bitrix) */
	protected function receiveBytes($length)
	{
		while($length > 0 && !feof($this->resource))
		{
			$count = ($length > self::BUF_READ_LEN? self::BUF_READ_LEN : $length);

			$buf = $this->receive($count);

			$receivedBytesLength = BinaryString::getLength($buf);
			$this->receivedBytesLength += $receivedBytesLength;

			if(!$this->checkErrors($buf))
			{
				return false;
			}

			$length -= $receivedBytesLength;
		}

		return true;
	}
}