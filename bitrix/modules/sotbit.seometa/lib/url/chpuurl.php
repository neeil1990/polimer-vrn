<?php
namespace Sotbit\Seometa\Url;


use Sotbit\Seometa\Generater\Common;
use Sotbit\Seometa\Generator\AbstractGenerator;


/**
 * Class ChpuUrl
 * @package Sotbit\Seometa\Url
 */
class ChpuUrl extends AbstractUrl
{
    /**
     * @var mixed
     */
    protected $propertyTemplate;

    /**
     * ChpuUrl constructor.
     * @param bool $template
     * @param false $spaceReplacement
     */
    public function __construct(
        $template = true,
        $spaceReplacement = false
    ) {
        preg_match('/{([^:]+)(:.*)+?}/i', $template, $match);
        $this->propertyTemplate = $match[1];

        if(!empty($match[2])) {
            $delimProp = [];
            preg_match('/((?:)[^:]+)/', $match[2], $delimProp);

            if(!empty($delimProp[0])) {
                $this->delimeterProps = $delimProp[0];
            }
        }

        preg_match_all('/#PROPERTY_([a-z_]+)#/i', $template,$match);
        $this->propertyFields = array_combine($match[0], $match[1]);

        if (is_string($template)) {
            $this->mask = $this->templateWithSection = $this->template = preg_replace('/{.+}/', '#PROPERTIES#', $template);
        }

        if(!empty($spaceReplacement)) {
            parent::setSpaceReplacement($spaceReplacement);
        }
    }

    /**
     * @param $str
     */
    public function setDelimiter(
        $str
    ) {
        if (is_string($str)) {
            $this->delimiter = $str;
        }
    }

    /**
     * @param Common $Generator
     * @return string
     */
    public function getLinkGlue(
        Common $Generator
    ) {
        if (mb_strpos('\Sotbit\Seometa\Generater\ComboxGenerator', get_class($Generator)) !== false) {
            return '&';
        }

        return '';
    }

    /**
     * @param array $filteredProps
     * @param AbstractGenerator $generator
     * @return mixed|void
     */
    protected function replacePropertiesFromSet(
        array &$filteredProps,
        AbstractGenerator $generator
    ) {
        if(empty($filteredProps['PROPERTY']) || !preg_match_all('/#PROPERTY_[a-z]+#/i', $this->propertyTemplate)) {
            $this->mask = str_replace('#PROPERTIES#', '', $this->mask);

            return;
        }

        $result = [];

        foreach($filteredProps['PROPERTY'] as $propertySetEntity) {
            $result[] = $generator->generate($this, $propertySetEntity);
        }

        $this->mask = str_replace('#PROPERTIES#', implode($this->delimeterProps, $result), $this->mask);
    }

    /**
     * @param array $filteredProps
     * @param AbstractGenerator $generator
     * @return mixed|void
     */
    protected function replacePriceFromSet(
        array &$filteredProps,
        AbstractGenerator $generator
    ) {
        if (mb_strpos($this->mask, '#PRICES#') === false) {
            return;
        }

        if(empty($filteredProps['PRICE'])) {
            $this->mask = str_replace('/#PRICES#', '', $this->mask);

            return;
        }

        $result = [];
        foreach($filteredProps['PRICE'] as $propertySetEntity) {
            $result[] = $generator->generate($this, $propertySetEntity);
        }

        $this->mask = str_replace('#PRICES#', implode('/', $result), $this->mask);
    }

    /**
     * @param array $filteredProps
     * @param AbstractGenerator $generator
     * @return mixed|void
     */
    protected function replaceFilterFromSet(
        array &$filteredProps,
        AbstractGenerator $generator
    ) {
        if (mb_strpos($this->mask, '#FILTER#') === false) {
            return;
        }

        if(empty($filteredProps['FILTER'])) {
            $this->mask = str_replace('/#FILTER#', '', $this->mask);

            return;
        }

        $result = [];
        foreach($filteredProps['FILTER'] as $propertySetEntity) {
            $result[] = $generator->generate($this, $propertySetEntity);
        }

        $this->mask = str_replace('#FILTER#', implode('/', $result), $this->mask);
    }
}
?>