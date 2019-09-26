<?php
// cf manual https://phpunit.readthedocs.io/fr/latest/writing-tests-for-phpunit.html
namespace ccn;
use \PHPUnit\Framework\TestCase;
require_once(__DIR__ . '/../src/Array.php');

class ArrayTest extends TestCase {
    
    /**
     * @dataProvider init_get_obj_path
     */
    public function test_get_obj_path($obj, $path, $delim, $return_value_if_wrong, $expected_result) {
        $result = get_obj_path($obj, $path, $delim, $return_value_if_wrong);
        $this->assertSame($expected_result, $result);
    }
    function init_get_obj_path() {
        $obj1 = [
            'a' => [
                'b' => 1, 
                'd' => [
                    'g' => [['k' => 12, 'l' => 13], ['k' => 14, 'm' => 15], 5, ['n' => 16]], 
                    'f' => [
                        8, 
                        'h' => ['i' => 10, 'j' => 11],
                        9
                    ],
                ], 
                'c' => ['e' => 5], 
            ],
        ];

        return [
            "simple case1" => [$obj1, "a/c/e", "/", false, 5],
            "simple case2" => [$obj1, "a-c-e-", "-", false, 5],
            "simple case3" => [$obj1, "a#!c#!e#!", "#!", false, 5],
            "modifier @keys 1" => [$obj1, "/a/d@keys", "/", false, ['g', 'f']],
            "modifier @keys 2" => [$obj1, "/a/d@keys/1", "/", false, 'f'],
            "modifier @keys 3" => [$obj1, "/a/d/@keys/1", "/", false, 'f'],
            "* operator 1" => [$obj1, "/a/d/f/*/i", "/", false, [false, 'h' => 10, false]],
            "* operator 2" => [$obj1, "/a/d/g/*/k", "/", "riri", [12, 14, "riri", "riri"]],
            "limit case1" => [null, "/a/b/c", "/", "coco", "coco"],
            "limit case2" => [[], "/a/b/c", "/", "coco", "coco"],
            "limit case3" => [$obj1, "/", "/", false, $obj1],
        ];
    }

    /* public function test_get_obj_path_exception() {
        //$this->expectExceptionMessage("InvalidPathDelimiter");
        $this->assertSame("1", "1");
    } */

}

?>