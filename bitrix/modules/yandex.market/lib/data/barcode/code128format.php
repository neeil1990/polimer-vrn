<?php

namespace Yandex\Market\Data\Barcode;

use Yandex\Market;

class Code128Format extends AbstractFormat
{
	public function getImage($text, $size = 20, $factor = 1)
	{
		$codeString = $this->getCodeString($text);
		$codeLength = $this->getCodeLength($codeString);
		$imageWidth = $codeLength * $factor;
		$imageHeight = $size;

		$image = imagecreate($imageWidth, $imageHeight);
		$black = imagecolorallocate($image, 0, 0, 0);
		$white = imagecolorallocate($image, 255, 255, 255);

		imagefill($image, 0, 0, $white);

		$location = 10;
		$codeStringLength = Market\Data\TextString::getLength($codeString);

		for ($i = 0; $i < $codeStringLength; $i++)
		{
			$currentSize = $location + Market\Data\TextString::getSubstring($codeString, $i, 1);

			imagefilledrectangle($image, $location * $factor, 0, $currentSize * $factor, $imageHeight, ($i % 2 === 1 ? $white : $black));

			$location = $currentSize;
		}

		return $image;
	}

	protected function getCodeString($text)
	{
		$checkSum = 104;
		$codeString = '';
		$codeList = $this->getCodeList();
		$textLength = Market\Data\TextString::getLength($text);

		$codeKeys = array_keys($codeList);
		$codeValues = array_flip($codeKeys);

		for ($i = 0; $i < $textLength; $i++)
		{
			$symbol = Market\Data\TextString::getSubstring($text, $i, 1);
			$code = $codeList[$symbol];
			$codeString .= $code;
			$checkSum += $codeValues[$symbol] * ($i + 1);
		}

		$checkSumSymbolIndex = $checkSum - ((int)($checkSum / 103)) * 103;
		$checkSumSymbol = $codeKeys[$checkSumSymbolIndex];
		$codeString .= $codeList[$checkSumSymbol];

		return '211214' . $codeString . '2331112';
	}

	protected function getCodeLength($codeString)
	{
		$codeStringLength = Market\Data\TextString::getLength($codeString);
		$result = 20;

		for ($i = 0; $i < $codeStringLength; $i++)
		{
			$result += (int)Market\Data\TextString::getSubstring($codeString, $i, 1);
		}

		return $result;
	}

	protected function getCodeList()
	{
		return [
			' ' => '212222',
			'!' => '222122',
			'"' => '222221',
			'#' => '121223',
			'$' => '121322',
			'%' => '131222',
			'&' => '122213',
			'\'' => '122312',
			'(' => '132212',
			')' => '221213',
			'*' => '221312',
			'+' => '231212',
			',' => '112232',
			'-' => '122132',
			'.' => '122231',
			'/' => '113222',
			'0' => '123122',
			'1' => '123221',
			'2' => '223211',
			'3' => '221132',
			'4' => '221231',
			'5' => '213212',
			'6' => '223112',
			'7' => '312131',
			'8' => '311222',
			'9' => '321122',
			':' => '321221',
			';' => '312212',
			'<' => '322112',
			'=' => '322211',
			'>' => '212123',
			'?' => '212321',
			'@' => '232121',
			'A' => '111323', 
			'B' => '131123', 
			'C' => '131321', 
			'D' => '112313', 
			'E' => '132113', 
			'F' => '132311', 
			'G' => '211313', 
			'H' => '231113', 
			'I' => '231311', 
			'J' => '112133', 
			'K' => '112331', 
			'L' => '132131', 
			'M' => '113123', 
			'N' => '113321', 
			'O' => '133121', 
			'P' => '313121', 
			'Q' => '211331', 
			'R' => '231131', 
			'S' => '213113', 
			'T' => '213311', 
			'U' => '213131', 
			'V' => '311123', 
			'W' => '311321', 
			'X' => '331121', 
			'Y' => '312113', 
			'Z' => '312311', 
			'[' => '332111', 
			'\\' => '314111', 
			']' => '221411', 
			'^' => '431111', 
			'_' => '111224', 
			'\`' => '111422', 
			'a' => '121124', 
			'b' => '121421', 
			'c' => '141122', 
			'd' => '141221', 
			'e' => '112214', 
			'f' => '112412', 
			'g' => '122114', 
			'h' => '122411', 
			'i' => '142112', 
			'j' => '142211', 
			'k' => '241211', 
			'l' => '221114', 
			'm' => '413111', 
			'n' => '241112', 
			'o' => '134111', 
			'p' => '111242', 
			'q' => '121142', 
			'r' => '121241', 
			's' => '114212', 
			't' => '124112', 
			'u' => '124211', 
			'v' => '411212', 
			'w' => '421112', 
			'x' => '421211', 
			'y' => '212141', 
			'z' => '214121', 
			'{' => '412121', 
			'|' => '111143', 
			'}' => '111341', 
			'~' => '131141', 
			'DEL' => '114113', 
			'FNC 3' => '114311', 
			'FNC 2' => '411113', 
			'SHIFT' => '411311', 
			'CODE C' => '113141', 
			'FNC 4' => '114131', 
			'CODE A' => '311141', 
			'FNC 1' => '411131', 
			'Start A' => '211412', 
			'Start B' => '211214', 
			'Start C' => '211232', 
			'Stop' => '2331112'
		];
	}
}