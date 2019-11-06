<?php
namespace sqd;
use \PHPUnit\Framework\TestCase;
require_once(dirname(__FILE__) . '/../src/ModelBuilder.php');

class ModelBuilderTest extends TestCase {

    /**
     * @dataProvider initTestModel
     */
    function test_ModelBuilder($model, $data, $context, $expected_result) {    
        $builder = new ModelBuilder($model, $data, $context);
        $res = $builder->build();
        $this->assertSame($expected_result, $res);
    }

    function initTestModel() {
        $model = [
            'AB' => '/a/b',
            'A' => '/aa',
            'C' => [
                'D' => '/d',
                'E' => '@REGEX(/^chose$/, /a/b/1, "DEFAULT")',
                'F' => '@REGEX(/^([\d]+)\s+ans$/, /a/f, false)  |r>  @MAPPER($taille, $r)',
            ],
            'G' => '/d/biz || []',
            'H' => function($data, $context, $curr_result) {
                        return $curr_result['A']." ".$data['a']['f'];
                    },
            '?I' => [
                '__condition' => function($data) {return $data['d'] == 3;},
                '__value' => [
                    'J' => 'hard coded string',
                    '?K' => [
                        '__condition' => function($data) {return $data['aa'] == 3;},
                        '__value' => 'coco',
                    ],
                ],
            ],
            'L' => 3
        ];
        $data = [
            'a' => ['b' => [1, 'truc'], 'f' => '11 ans'],
            'aa' => 'riri',
            'd' => 3,
        ];
        $context = [
            'taille' => [
                '11' => 'PETIT',
                '30' => 'GRAND',
            ],
        ];
        $expected_result = [
            'AB' => [1, 'truc'],
            'A' => 'riri',
            'C' => [
                'D' => 3,
                'E' => 'DEFAULT',
                'F' => 'PETIT',
            ],
            'G' => [],
            'H' => 'riri 11 ans',
            'I' => ['J' => 'hard coded string'],
            'L' => 3,
        ];
        
        return [
            "test1" => [$model, $data, $context, $expected_result],
        ];
    }
}




?>