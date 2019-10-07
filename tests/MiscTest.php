<?php
// cf manual https://phpunit.readthedocs.io/fr/latest/writing-tests-for-phpunit.html

namespace ccn;
use PHPUnit\Framework\TestCase;
require_once(dirname(__FILE__) . '/../src/Misc.php');

class MiscTest extends TestCase {

    function test_first_of() {
        $this->assertSame(1, first_of(null, '', 1, [], 'toto'));
        $this->assertSame('toto', first_of(null, '', [], null, 'toto'));
    }

}

?>