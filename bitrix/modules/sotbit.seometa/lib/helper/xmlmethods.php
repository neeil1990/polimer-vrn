<?php

namespace Sotbit\Seometa\Helper;

class XMLMethods
{
    private $xmlVersion = '<?xml version="1.0" encoding="UTF-8"?>';
    private $xmlAttr = ['xmlns' => 'http://www.sitemaps.org/schemas/sitemap/0.9'];
    private $seometaItem = 'sitemap_seometa_';

    function createXml($fileName) {
        $result = [];

        $result['urlset'] = [
            '_a' => $this->xmlAttr,
            '_c' => [
                'url' => []
            ]
        ];

        $error = file_put_contents($fileName, self::ary2xml($result));
        if ($error === false) {
            return [
                'TYPE' => 'ERROR',
                'MSG' => 'Error: do note created file seometa xml!'
            ];
        }

        return $result;
    }

    function seometaMainSitemapFiles($index, $id, $sitePath) {
        $item = [];
        for($i = $index; $i > 0; $i--) {
            $item[] = [
                '_c' => [
                    'loc' => [
                        '_v' => $sitePath . $this->seometaItem . $id . '_' . $i . '.xml'
                    ],
                    'lastmod' => [
                        '_v' => date('Y-m-d\TH:i:sP')
                    ]
                ]
            ];
        }

        return array_reverse($item);
    }

    function writeSiteMap($fileName, $xmlData)
    {
        $result = '';
        if (file_exists($fileName) && !empty($xmlData)) {
            $result = file_put_contents($fileName, $this->xmlVersion . $xmlData);
        }

        if ($result === false) {
            return [
                'TYPE' => 'ERROR',
                'MSG' => 'Can not create a file or xml data is empty!'
            ];
        }

        return true;
    }

    function delSeometaFromMainSitemap(&$ary) {
        foreach ($ary as $key => $item) {
            if(is_int($key)){
                if(mb_strpos($item['_c']['loc']['_v'], $this->seometaItem) !== false) {
                    unset($ary[$key]);
                }
            }
        }
    }

    function xml2ary(&$string) {
        $parser = xml_parser_create();
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parse_into_struct($parser, $string, $vals, $index);
        xml_parser_free($parser);

        $mnary= [];
        $ary =& $mnary;
        foreach ($vals as $r) {
            $t = $r['tag'];
            if ($r['type'] == 'open' || $r['type'] == 'complete') {
                if (isset($ary[$t])) {
                    if (isset($ary[$t][0])) {
                        $ary[$t][] = [];
                    } else {
                        $ary[$t] = [
                            $ary[$t],
                            []
                        ];
                    }

                    $cv =& $ary[$t][count($ary[$t]) - 1];
                } else {
                    $cv =& $ary[$t];
                }

                if (isset($r['attributes'])) {
                    foreach ($r['attributes'] as $k => $v) {
                        $cv['_a'][$k] = $v;
                    }
                }

                if($r['type'] == 'open') {
                    $cv['_c'] = [];
                    $cv['_c']['_p'] =& $ary;
                    $ary =& $cv['_c'];
                } elseif ($r['type'] == 'complete') {
                    $cv['_v'] = $r['value'] ?? '';
                }
            } elseif ($r['type'] == 'close') {
                $ary =& $ary['_p'];
            }
        }

        self::_del_p($mnary);
        return $mnary;
    }

    function _del_p(&$ary) {
        foreach ($ary as $k => $v) {
            if ($k === '_p') {
                unset($ary[$k]);
            } elseif (is_array($ary[$k])) {
                self::_del_p($ary[$k]);
                if(($k === 'url' || $k === "sitemap") && count($ary[$k]) == 1){
                    $ary[$k] = [
                        $ary[$k]
                    ];
                }
            }
        }
    }

    function ary2xml($cary, $forcetag='') {
        $res = [];
        foreach ($cary as $tag => $r) {
            if (isset($r[0])) {
                $res[] = self::ary2xml($r, $tag);
            } else {
                if ($forcetag) {
                    $tag = $forcetag;
                }

                $res[] = "<$tag";
                if (isset($r['_a'])) {
                    foreach ($r['_a'] as $at => $av) {
                        $res[] = " $at=\"$av\"";
                    }
                }

                $res[] = ">";
                if (isset($r['_c'])) {
                    $res[] = $emptyTag = self::ary2xml($r['_c']);
                } elseif (isset($r['_v'])) {
                    $res[] = $emptyTag = $r['_v'];
                }
                if(!isset($emptyTag)){
                    $countRes = count($res);
                    unset($res[$countRes-1]);
                    unset($res[$countRes-2]);
                    $res = array_values($res);
                }else{
                    $res[] = "</$tag>";
                }
            }
        }
        
        return implode('', $res);
    }

    function ins2ary(&$ary, $element, $pos) {
        if(!$ary){
            $ary = $element;
        }else{
            $ar1 = array_slice($ary, 0, $pos);
            $ar1 = array_merge($ar1, $element);
            $ary = array_merge($ar1, array_slice($ary, $pos));
        }
    }
}
