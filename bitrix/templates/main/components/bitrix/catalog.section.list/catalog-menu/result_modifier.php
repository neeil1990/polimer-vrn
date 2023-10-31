<?php



foreach($arResult['SECTIONS'] as $key => $section) {

	if ($section['DEPTH_LEVEL'] == 1) {
		$one = $key;
		$i = 0;
	}

	if ($section['DEPTH_LEVEL'] == 2) {
		$two = $i;
		$arResult['SECTIONS'][$one]['SECTION_1'][] = $section;
		$i++;
		unset($arResult['SECTIONS'][$key]);
	}

	if ($section['DEPTH_LEVEL'] == 3) {
		$arResult['SECTIONS'][$one]['SECTION_1'][$two]['SECTION_2'][] = $section;
		unset($arResult['SECTIONS'][$key]);
	}

}

