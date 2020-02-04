<?php
class ConstNames_Grammems_Rus {
	public
		 $rPlural     = 0,
		 $rSingular   = 1,

		 $rNominativ  = 2,
		 $rGenitiv    = 3,
		 $rDativ      = 4,
		 $rAccusativ  = 5,
		 $rInstrumentalis = 6,
		 $rLocativ    = 7,
		 $rVocativ    = 8,

		 $rMasculinum = 9,
		 $rFeminum    = 10,
		 $rNeutrum    = 11,
		 $rMascFem    = 12,


		 $rPresentTense = 13,
		 $rFutureTense = 14,
		 $rPastTense = 15,

		 $rFirstPerson = 16,
		 $rSecondPerson = 17,
		 $rThirdPerson = 18,

		 $rImperative = 19,

		 $rAnimative = 20,
		 $rNonAnimative = 21,

		 $rComparative = 22,

		 $rPerfective = 23,
		 $rNonPerfective = 24,

		 $rNonTransitive = 25,
		 $rTransitive = 26,

		 $rActiveVoice = 27,
		 $rPassiveVoice = 28,


		 $rIndeclinable = 29,
		 $rInitialism = 30,

		 $rPatronymic = 31,

		 $rToponym = 32,
		 $rOrganisation = 33,

		 $rQualitative = 34,
		 $rDeFactoSingTantum = 35,

		 $rInterrogative = 36,
		 $rDemonstrative = 37,

		 $rName	    = 38,
		 $rSurName	= 39,
		 $rImpersonal = 40,
		 $rSlang	= 41,
		 $rMisprint = 42,
		 $rColloquial = 43,
		 $rPossessive = 44,
		 $rArchaism = 45,
		 $rSecondCase = 46,
		 $rPoetry = 47,
		 $rProfession = 48,
		 $rSuperlative = 49,
		 $rPositive = 50;
}

class ConstNames_Poses_Rus {
	public
		$rNOUN  = 0, 
		$rADJ_FULL = 1, 
		$rVERB = 2, 
		$rPRONOUN = 3, 
		$rPRONOUN_P = 4, 
		$rPRONOUN_PREDK = 5,
		$rNUMERAL  = 6, 
		$rNUMERAL_P = 7, 
		$rADV = 8, 
		$rPREDK  = 9, 
		$rPREP = 10,
		$rPOSL = 11,
		$rCONJ = 12,
		$rINTERJ = 13,
		$rINP = 14,
		$rPHRASE = 15,
		$rPARTICLE = 16,
		$rADJ_SHORT = 17,
		$rPARTICIPLE = 18,
		$rADVERB_PARTICIPLE = 19,
		$rPARTICIPLE_SHORT = 20,
		$rINFINITIVE = 21;
}

class ConstNames_Rus extends ConstNames_Base {
	protected $poses = array(
		"Ñ",  // 0
		"Ï", // 1
		"Ã", // 2
		"ÌÑ", // 3
		"ÌÑ-Ï", // 4
		"ÌÑ-ÏÐÅÄÊ", // 5
		"×ÈÑË", // 6
		"×ÈÑË-Ï", // 7
		"Í", // 8
		"ÏÐÅÄÊ", //9 
		"ÏÐÅÄË", // 10
		"ÏÎÑË", // 11
		"ÑÎÞÇ", // 12
		"ÌÅÆÄ", // 13
		"ÂÂÎÄÍ",// 14
		"ÔÐÀÇ", // 15
		"×ÀÑÒ", // 16
		"ÊÐ_ÏÐÈË",  // 17
		"ÏÐÈ×ÀÑÒÈÅ", //18
		"ÄÅÅÏÐÈ×ÀÑÒÈÅ", //19
		"ÊÐ_ÏÐÈ×ÀÑÒÈÅ", // 20
		"ÈÍÔÈÍÈÒÈÂ"  //21
	);
	
	protected $grammems = array(
		// 0..1
	   	"ìí","åä",
		// 2..8
		"èì","ðä","äò","âí","òâ","ïð","çâ",
		// ðîä 9-12
		"ìð","æð","ñð","ìð-æð",
		// 13..15
		"íñò","áóä","ïðø",
		// 16..18
		"1ë","2ë","3ë",	
		// 19
		"ïâë",
		// 20..21
		"îä","íî",	
		// 22
		"ñðàâí",
		// 23..24
		"ñâ","íñ",	
		// 25..26
		"íï","ïå",
		// 27..28
		"äñò","ñòð",
		// 29-31
		"0", "àááð", "îò÷",
		// 32-33
		"ëîê", "îðã",
		// 34-35
		"êà÷", "äôñò",
		// 36-37 (íàðå÷èÿ)
		"âîïð", "óêàçàò",
		// 38..39
		"èìÿ","ôàì",
		// 40
		"áåçë",
		// 41,42
		"æàðã", "îï÷",
		// 43,44,45
		"ðàçã", "ïðèòÿæ", "àðõ",
		// äëÿ âòîðîãî ðîäèòåëüíîãî è âòîðîãî ïðåäëîæíîãî
		"2",
		"ïîýò", "ïðîô",
		"ïðåâ", "ïîëîæ"
	);
	
	function getPartsOfSpeech() {
		return $this->combineObjAndArray(new ConstNames_Poses_Rus(), $this->poses);
	}
	
	function getGrammems() {
		return $this->combineObjAndArray(new ConstNames_Grammems_Rus(), $this->grammems);
	}
}
