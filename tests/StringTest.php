<?php
// cf manual https://phpunit.readthedocs.io/fr/latest/writing-tests-for-phpunit.html

namespace ccn;
use PHPUnit\Framework\TestCase;
require_once(dirname(__FILE__) . '/../src/String.php');

class StringTest extends TestCase {
    
    /**
     * @dataProvider init_remove_accents
     */
    function test_remove_accents($str, $encoding, $expected) {
        $this->assertSame(remove_accents( $str, $encoding ), $expected);
    }
    function init_remove_accents() {
        return [
            "french 1" => ["ç é è ù ò É À à", "utf8", "c e e u o E A a"],
            "other 1" => ["ß Ö ö Ü ä Ø Å", "Utf-8", "ss O o U a O A"],
        ];
    }
}

?>