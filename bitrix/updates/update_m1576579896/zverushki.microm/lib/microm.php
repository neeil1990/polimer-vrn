<?php
namespace Zverushki\Microm;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class MicromTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> SITE_ID string(255) mandatory
 * <li> CODE string(255) optional
 * <li> VALUE string(255) optional
 * </ul>
 *
 * @package Zverushki\Microm
 **/

class MicromTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'zverushki_microm';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('MICROM_ENTITY_ID_FIELD'),
			),
			'SITE_ID' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateSiteId'),
				'title' => Loc::getMessage('MICROM_ENTITY_SITE_ID_FIELD'),
			),
			'CODE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateCode'),
				'title' => Loc::getMessage('MICROM_ENTITY_CODE_FIELD'),
			),
			'VALUE' => array(
				'data_type' => 'text',
				'serialized' => true,
				'validation' => array(__CLASS__, 'validateValue'),
				'title' => Loc::getMessage('MICROM_ENTITY_VALUE_FIELD'),
			),
		);
	}
	/**
	 * Returns validators for SITE_ID field.
	 *
	 * @return array
	 */
	public static function validateSiteId()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for CODE field.
	 *
	 * @return array
	 */
	public static function validateCode()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	public static function validateValue(){
		return array(
			new \Zverushki\Microm\Entity\Validator\SerializeMiromark(),
		);
	}
}