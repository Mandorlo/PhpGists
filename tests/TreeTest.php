<?php
// cf manual https://phpunit.readthedocs.io/fr/latest/writing-tests-for-phpunit.html

namespace sqd;
use PHPUnit\Framework\TestCase;
require_once(dirname(__FILE__) . '/../src/Tree.php');

class TreeTest extends TestCase {

    /**
     * @dataProvider init_tree_implode
     */
    function test_tree_implode($tree, $start_level, $end_level, $glue, $expected) {
        $this->assertSame(tree_implode( $tree, $start_level, $end_level, $glue ), $expected);
    }
    function init_tree_implode() {
        $tree = [
            'a' => ['b' => ['c' => 1, 'd' => 2], 'e' => [], 'f' => 3],
            'g' => ['h' => ['i' => 1], 'j' => 'coco', 'k' => 4],
            'l' => 'm',
            'n' => ['o', 'p']
        ];
        return [
            'un' => [$tree, 0, 1, "_", [
                'a_b' => ['c' => 1, 'd' => 2], 'a_e' => [], 'a_f' => 3, 
                'g_h' => ['i' => 1], 'g_j' => 'coco', 'g_k' => 4,
                'l_m' => null,
                'n_0' => 'o',
                'n_1' => 'p',
            ]],
             'deux' => [$tree, 1, 3, "_", [
                'a' => ['b_c_1' => null, 'b_d_2' => null, 'e__' => null, 'f_3_' => null],
                'g' => ['h_i_1' => null, 'j_coco_' => null, "k_4_" => null],
                'l' => 'm',
                'n' => ['0_o_' => null, '1_p_' => null],
            ]],
            'trois' => [$tree, 0, 2, "_", [
                'a_b_c' => 1, 'a_b_d' => 2, 'a_e_' => null, 'a_f_3' => null,
                'g_h_i' => 1, 'g_j_coco' => null, 'g_k_4' => null,
                'l_m_' => null, 'n_0_o' => null, 'n_1_p' => null,
            ]],
        ];
    }
}

?>