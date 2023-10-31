<?php
namespace Sotbit\Seometa\Url;

use Sotbit\Seometa\Generator\AbstractGenerator;
use Sotbit\Seometa\Property\PropertySet;
use Sotbit\Seometa\Section\Section;
use Sotbit\Seometa\Section\SectionCollection;

/**
 * Class AbstractUrl
 * For creating urls (chpu and catalog url)
 *
 * @package Sotbit\Seometa\Url
 */
abstract class AbstractUrl {
    /**
     * Mask of url
     *
     * @var bool
     */
    protected $mask = false;

    /**
     * Template for creating urls
     *
     * @var bool
     */
    protected $template = false;

    /**
     * Template with section
     *
     * @var bool
     */
    protected $templateWithSection = false;

    /**
     * Template for property
     *
     * @var string
     */
    protected $propertyTemplate = '';

    /**
     * Delimiter for a few properties values
     *
     * @var string
     */
    protected $delimiter = '-or-';

    /**
     * Property fields
     *
     * @var array
     */
    protected $propertyFields = [];

    /**
     * Replacement for space in url
     *
     * @var string
     */
    protected $spaceReplacement = '-';

    /**
     * Delimiter between a few properties
     *
     * @var string
     */
    protected $delimeterProps = '/';

    /**
     * Is mask has section placeholder
     *
     * @return false|int
     */
    public function hasSectionPlaceholders() {
        return preg_match('/\#(ID|SECTION_ID|CODE|SECTION_CODE|SECTION_CODE_PATH|EXTERNAL_ID)\#/', $this->mask);
    }

    /**
     * Set(replace) section placeholders
     *
     * @param Section $section
     */
    public function setSectionPlaceholders(
        Section $section
    ) {
        if (!($section instanceof Section)) {
            return;
        }

        preg_match_all('/\#(ID|SECTION_ID|CODE|SECTION_CODE|SECTION_CODE_PATH|EXTERNAL_ID)\#/', $this->mask, $match);
        if (!empty($match[0])) {
            $this->replaceSectionHolders($match[0], $section);
        }
    }

    /**
     * Replace section placeholders
     *
     * @param array $keys
     * @param Section $section
     */
    private function replaceSectionHolders(
        array $keys,
        Section $section
    ) {
        $result = [];
        $keys[] = '#ID#';
        $keys[] = '#SECTION_CODE#';

        foreach ($keys as $key) {
            $clearKey = preg_replace('/#(SECTION_)?([a-z_]+)#/i', '$2', $key);
            $result[$key] = $section->$clearKey;
        }

        if (in_array('#SECTION_CODE_PATH#', $keys)) {
            $result['#SECTION_CODE_PATH#'] = $section->getSectionPath();
        }

        if (in_array('#EXTERNAL_ID#', $keys)) {
            $result['#EXTERNAL_ID#'] = $section->XML_ID;
        }

        $this->replaceHolders($result);
    }

    /**
     * Check mask on empty
     *
     * @return bool
     */
    public function isEmpty() {
        return empty($this->mask);
    }

    /**
     * Get mask
     *
     * @return string
     */
    public function getMask() {
        return $this->mask;
    }

    /**
     * Set replacement for space
     *
     * @param $spaceReplacement
     */
    public function setSpaceReplacement(
        $spaceReplacement
    ) {
        $this->spaceReplacement = $spaceReplacement;
    }

    /**
     * Get space replacement
     *
     * @return string
     */
    public function getSpaceReplacement()
    {
        return $this->spaceReplacement;
    }

    /**
     * Clean template, revert default state
     *
     * @param false $full
     */
    public function cleanTemplate(
        $full = false
    ) {
        if ($full) {
            $this->mask = $this->template;
        } else {
            $this->mask = $this->templateWithSection;
        }
    }

    /**
     * Replace holders
     *
     * @param array $arHolderValues
     */
    public function replaceHolders(
        $arHolderValues = array()
    ) {
        $arHolderValues = $this->prepareFields($arHolderValues);
        $this->mask = str_replace(array_keys($arHolderValues), $arHolderValues, $this->mask);
    }

    /**
     * Prepare fields
     *
     * @param $arFields
     * @return array
     */
    protected function prepareFields(
        $arFields
    ) {
        if (is_array($arFields)) {
            foreach ($arFields as &$arField) {
                if (is_array($arField)) {
                    $arField = implode($this->delimiter, $arField);
                }
            }
        }

        return is_array($arFields) ? $arFields : [];
    }

    /**
     * @param $sectionId
     */
    public function setSectionPlaceholdersIfNeed(
        $sectionId
    ) {
        if ($this->hasSectionPlaceholders()) {
            $this->setSectionPlaceholders(SectionCollection::getInstance()->getSectionById($sectionId));
            $this->templateWithSection = $this->mask;
        }
    }

    /**
     * @param PropertySet $propertySet
     * @param AbstractGenerator $generator
     */
    public function replaceFromSet(
        PropertySet $propertySet,
        AbstractGenerator $generator
    ) {
        $filteredProps = [];
        foreach ($propertySet->getData() as $propertySetEntity) {
            if ($propertySetEntity->isCompressed()) {
                continue;
            }

            $typeEntity = $propertySetEntity->getEntityType();
            $filteredProps[$typeEntity][] = $propertySetEntity;
        }

        $this->replacePropertiesFromSet($filteredProps, $generator);
        $this->replacePriceFromSet($filteredProps, $generator);
        $this->replaceFilterFromSet($filteredProps, $generator);

        $this->mask = str_replace('//', '/', $this->mask);
    }

    /**
     * @param array $filteredProps
     * @param AbstractGenerator $generator
     * @return mixed
     */
    abstract protected function replacePropertiesFromSet(array &$filteredProps, AbstractGenerator $generator);

    /**
     * @param array $filteredProps
     * @param AbstractGenerator $generator
     * @return mixed
     */
    abstract protected function replacePriceFromSet(array &$filteredProps, AbstractGenerator $generator);

    /**
     * @param array $filteredProps
     * @param AbstractGenerator $generator
     * @return mixed
     */
    abstract protected function replaceFilterFromSet(array &$filteredProps, AbstractGenerator $generator);

    /**
     * @return bool
     */
    public function hasPropertyFields() {
        return !empty($this->propertyFields);
    }

    /**
     * @return array
     */
    public function getPropertyFields() {
        return $this->propertyFields;
    }

    /**
     * @return string
     */
    public function getPropertyTemplate() {
        return $this->propertyTemplate;
    }

    /**
     * Reset mask in default state
     */
    public function resetMask() {
        $this->mask = $this->template;
    }
}
?>