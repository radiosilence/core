<?php

require('../core.php');

import('core.session.handler');

class StackTest extends PHPUnit_Framework_TestCase {

    /**
     * @expectedException ImportError
     */
    public function testFailImport() {
        import('this.should.fail');
    }

}

?>