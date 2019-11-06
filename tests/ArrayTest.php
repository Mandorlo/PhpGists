<?php
// cf manual https://phpunit.readthedocs.io/fr/latest/writing-tests-for-phpunit.html
namespace sqd;
use \PHPUnit\Framework\TestCase;
require_once(__DIR__ . '/../src/Array.php');

class ArrayTest extends TestCase {

    function test_array_assign() {
        $a1 = ['1'=>'a','2'=>'b'];
        $a2 = ['2'=>'c','3'=>'d','4'=>'e'];
        
        $expected_result = ['1'=>'a', '2'=>'c', '3'=>'d', '4'=>'e'];
        $result = array_assign($a1, $a2);

        $this->assertSame($expected_result, $result);
    }

    function test_array_build() {
        $keys = [1, 3, 'coco', 'truc'];
        $values = [null, '33', [1,2]];
        $expected_result = [
            '1' => null,
            '3' => '33',
            'coco' => [1,2],
            'truc' => null,
        ];

        $result = array_build($keys, $values);
        $this->assertSame($expected_result, $result);

        $result = array_build(3, 'azerty');
        $this->assertSame([3=>'azerty'], $result);
    }

    /**
     * @dataProvider init_array_has_string_key
     */
    function test_array_has_string_key($arr, $expected_result) {
        $result = array_has_string_key($arr);
        $this->assertSame($expected_result, $result);
    }
    function init_array_has_string_key() {
        return [
            'empty array' => [[], false],
            'not an array' => [3, false],
            'list' => [[1,2,'zer'], false],
            'assoc1' => [['a'=>1], true],
            'assoc2' => [['sdf'=>'aze', 1, 3, 'zer' => 5], true],
        ];
    }

    /**
     * @dataProvider init_first_not_falsy
     */
    function test_first_not_falsy($arr, $default_val, $expected_result) {
        $result = first_not_falsy($arr, $default_val);
        $this->assertSame($expected_result, $result);
    }
    function init_first_not_falsy() {
        return [
            'simple case 1' => [[null, '', 1, 3], false, 1],
            'simple case 2' => [['a' => 0, 'c' => [], 'b' => 1, 3, 'd' => 'truc'], false, 1],
            'limit case 1' => [[null, 'a' => [], ''], 'gesù', 'gesù'],
        ];
    }
    
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

    function test_implode_assoc() {
        $arr = ['a'=>['b'=>1], 3, 'c' => 5, 'd' => 'azerty'];
        $expected_result = 'a={"b":1},0=3,c=5,d=azerty';
        $result = implode_assoc($arr);
        $this->assertSame($expected_result, $result);
    }

    function test_mapper_reverse() {
        $m = ['a'=>[1,2], 'b'=>[1,3,4], 'c' => 'gloria a Dio'];
        $expected_result = [1=>'b', 2=>'a', 3=>'b', 4=>'b', 'gloria a Dio' => 'c'];
        $result = mapper_reverse($m);
        $this->assertSame($expected_result, $result);
    }

}

?>