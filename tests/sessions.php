<?php

require('../core.php');

import('core.session.handler');

class StackTest extends PHPUnit_Framework_TestCase {

    public function testSessionConstruct() {
        $sh = new \Core\Session\Handler();
        $this->assertInstanceOf('\Core\Session\Handler', $sh);
        return $sh;
    }

    /**
     * @depends testSessionConstruct
     */
    public function testSetRemoteAddr($sh) {
         $sh->set_remote_addr('1.2.3.4');
         $this->assertEqual($sh->remote_addr,'1.2.3.4');
     }
}
?>