<?php
namespace Sotbit\Seometa\Section;

use Sotbit\Seometa\SeoMeta;

class Section {
    private $data = [];

    public function __construct(array &$data) {
        $this->data = $data;
    }

    public function __get($name) {
        return isset($this->data[$name]) ? $this->data[$name] : null;
    }

    public function getSectionPath() {
        if(isset($this->data['SECTION_PATH']))
            return $this->data['SECTION_PATH'];

        $this->data['SECTION_PATH'] = \CIBlockSection::getSectionCodePath($this->data['ID']);
        return $this->data['SECTION_PATH'];
    }
}
?>
