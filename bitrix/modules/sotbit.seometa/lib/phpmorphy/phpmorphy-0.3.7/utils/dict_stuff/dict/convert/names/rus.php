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
		"�",  // 0
		"�", // 1
		"�", // 2
		"��", // 3
		"��-�", // 4
		"��-�����", // 5
		"����", // 6
		"����-�", // 7
		"�", // 8
		"�����", //9 
		"�����", // 10
		"����", // 11
		"����", // 12
		"����", // 13
		"�����",// 14
		"����", // 15
		"����", // 16
		"��_����",  // 17
		"���������", //18
		"������������", //19
		"��_���������", // 20
		"���������"  //21
	);
	
	protected $grammems = array(
		// 0..1
	   	"��","��",
		// 2..8
		"��","��","��","��","��","��","��",
		// ��� 9-12
		"��","��","��","��-��",
		// 13..15
		"���","���","���",
		// 16..18
		"1�","2�","3�",	
		// 19
		"���",
		// 20..21
		"��","��",	
		// 22
		"�����",
		// 23..24
		"��","��",	
		// 25..26
		"��","��",
		// 27..28
		"���","���",
		// 29-31
		"0", "����", "���",
		// 32-33
		"���", "���",
		// 34-35
		"���", "����",
		// 36-37 (�������)
		"����", "������",
		// 38..39
		"���","���",
		// 40
		"����",
		// 41,42
		"����", "���",
		// 43,44,45
		"����", "������", "���",
		// ��� ������� ������������ � ������� �����������
		"2",
		"����", "����",
		"����", "�����"
	);
	
	function getPartsOfSpeech() {
		return $this->combineObjAndArray(new ConstNames_Poses_Rus(), $this->poses);
	}
	
	function getGrammems() {
		return $this->combineObjAndArray(new ConstNames_Grammems_Rus(), $this->grammems);
	}
}
