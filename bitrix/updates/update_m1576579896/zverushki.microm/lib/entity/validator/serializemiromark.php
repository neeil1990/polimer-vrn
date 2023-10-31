<?php
/**
 * Валидаров сохранения данных
 *
 * @copyright  Zverushki
 */

namespace Zverushki\Microm\Entity\Validator;

use Bitrix\Main\Entity,
	Bitrix\Main\Entity\Validator,
	Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class SerializeMiromark extends Validator\Base
{
	private $value = false;
	public function __construct()
	{
		parent::__construct();
	}
	function validate($value, $primary, array $row, Entity\Field $field){
		$arError = array();
		foreach ($value as $key => $item) {
			switch ($key) {
				case 'name':
				case 'address_streetAddress':
				case 'address_addressLocality':
				case 'address_postalCode':
				case 'priceRange':
					if(!$this->request($item)){
						$arError[] = Loc::getMessage("ERROR_REQ_".$key);
					}
					break;
				case 'address_addressCountry':
					if(!$this->request($item)){
						$arError[] = Loc::getMessage("ERROR_REQ_".$key);
					}else if(!$this->validationCountry($item)){
						$arError[] = Loc::getMessage("ERROR_COUNTRY_".$key);
					}
					break;
				case '@id':
				case 'url':
					if(!$this->request($item)){
						$arError[] = Loc::getMessage("ERROR_REQ_".$key);
					}else if(!$this->validationUrl($item)){
						$arError[] = Loc::getMessage("ERROR_URL_".$key);
					}
					break;
				case 'email':
					if(!$this->validationEmail($item)){
						$arError[] = Loc::getMessage("ERROR_FORMAT_".$key);
					}
					break;
				case 'logo':
					if(!$this->request($item)){
						$arError[] = Loc::getMessage("ERROR_REQ_".$key);
					}
					if(!$this->validationUrl($item)){
						$arError[] = Loc::getMessage("ERROR_URL_".$key);
					}
					break;
				case 'image':
					if(!$this->validationUrl($item)){
						$arError[] = Loc::getMessage("ERROR_URL_".$key);
					}
					break;
				case 'geo_latitude':
				case 'geo_longitude':
					if(!$this->validationGeo($item)){
						$arError[] = Loc::getMessage("ERROR_FORMAT_".$key);
					}
					break;
			}
		}

		if(count($arError) > 0)
			return $this->getErrorMessage($value, $field, $arError, array());
		return true;
	}
	function validationEmail($val){
		if(strlen($val) <=0 || preg_match("/.+@.+\..+/i", $val))
			return true;
		return false;
	}
	function validationGeo($val){
		if(strlen($val) <=0 || preg_match("/[0-9]{1,2}[\.|,][0-9]{5}/", $val))
			return true;
		return false;
	}
	function validationUrl($uri){
		if(strlen($uri) <=0 || preg_match( '/^(http|https):\/\/(.*)$/i' ,$uri))
			return true;
		return false;
	}
	function validationCountry($CN){
		if(strlen($CN) === 2 && preg_match("/^[A-Z]+$/", $CN))
			return true;
		return false;
	}
	function request($val, $min = 0){
		if(strlen($val) > $min)
			return true;
		return false;
	}
}