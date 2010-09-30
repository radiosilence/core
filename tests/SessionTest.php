<?php
define('TEST_IP', '1.2.3.4');

define('TEST_SID', 'zxyf666');
define('TEST_TOK', '968acb2a163a6407c8aac6d45f63678ce3a61696');

require_once('core.php');
require_once 'vfsStream/vfsStream.php';

import('core.session.handler');

class SessionTest extends PHPUnit_Framework_TestCase {

    /**
     * A set up session object.
     */
    protected static $sh;
    protected static $srp;
    protected static $slc;

    public function setUp() {

        if (class_exists('vfsStream', false) === false) {
            $this->markTestSkipped('vfsStream not installed.');
        }
        vfsStreamWrapper::register();
        $root = vfsStream::newDirectory('home/config');
        $root->getChild('config')->addChild(vfsStream::newFile('crypto.php')->withContent('
            <?php $config_auth["keyphrase"] = "moo"; $config_auth["base_salt"] = "baa"; ?>
        '));
        vfsStreamWrapper::setRoot($root);

        self::$slc = $this->getMock('\Core\Session\LocalStorage', array(
            'get',
            'set',
            'destroy'
        ));

        self::$srp = $this->getMock('\Core\Session\RemoteStorage', array(
            'set_remote_addr',
            '__set',
            '__get',
            'save',
            'add',
            'load',
            'destroy',
        ));
        
        self::$sh = new \Core\Session\Handler();
        self::$sh->set_remote_addr(TEST_IP)
            ->attach_local_storage(self::$slc)
            ->attach_remote_storage(self::$srp)
            ->initialize_remote_storage()
            ->attach_crypto_config('vfs://config/crypto.php');
    }

    /**
     * Make sure it has the remote address AND a remote storage before giving
     * the address to the storage. 
     *
     * @expectedException \Core\Session\RemoteStorageNotAttachedError
     * @test
     */
    public function remoteStorageRequiredForInitialize() {
         $sh = new \Core\Session\Handler();
         $sh->set_remote_addr(TEST_IP)
            ->initialize_remote_storage();
    }

    /**
     * Make sure session throws a shit fit if all the correct things aren't done.
     *
     * @expectedException \Core\Session\SetupIncompleteError
     * @test
     */
    public function startupCheck() {
        $sh = new \Core\Session\Handler();
        $sh->start();
    }

    /**
     * Make sure the handler is passing the correct address to the remote storage.
     * @test
     */
    public function correctAddrGivenToRemote() {
        self::$srp->expects($this->once())
            ->method('set_remote_addr')
            ->with($this->equalTo(TEST_IP));

        $sh = new \Core\Session\Handler();
        $sh->set_remote_addr(TEST_IP)
            ->attach_remote_storage(self::$srp)
            ->initialize_remote_storage();
    }

    /**
     * Load a real session
     *
     * @test
     */
     public function loadRealSession() {
         
        self::$srp->expects($this->once())
            ->method('load')
            ->with(array('sid' => TEST_SID, 'tok' => TEST_TOK));
        
        self::$slc->expects($this->any())
             ->method('get')
             ->will($this->returnValue(array('sid' => TEST_SID, 'tok' => TEST_TOK)));

        self::$sh->start();
     }
}
?>