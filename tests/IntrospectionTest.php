<?php
// cf manual https://phpunit.readthedocs.io/fr/latest/writing-tests-for-phpunit.html

namespace ccn;
use PHPUnit\Framework\TestCase;
require_once(dirname(__FILE__) . '/../src/Introspection.php');

class IntrospectionTest extends TestCase {

    /**
     * @dataProvider init_callable_name
     */
    function test_callable_name($callable, $expected) {
        $this->assertSame(callable_name( $callable ), $expected);
    }
    function init_callable_name() {
        return [
            'right' => ['array_merge', 'array_merge'],
            'wrong' => ['qlkdmfj', false],
        ];
    }

    /**
     * @dataProvider init_eval_condition
     */
    function test_eval_condition($cond, $expected) {
        $result = eval_condition($cond, $expected);
        $this->assertSame($result, $expected);
    }
    function init_eval_condition() {
        return [
            '1' => ['1 == 2', 0],
            '2' => ["'a' < 'bb'", 1],
            'wrong' => ["1^$<w", -1],
            'empty' => ["", -1],
        ];
    }

}

?>