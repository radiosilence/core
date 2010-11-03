<?php
require_once __DIR__ . '/../core.php';
require_once 'vfsStream/vfsStream.php';

import('core.session.handler');

class ImportTest extends PHPUnit_Framework_TestCase {

    public function setUp() {
        if (class_exists('vfsStream', false) === false) {
            $this->markTestSkipped('vfsStream not installed.');
        }
        vfsStreamWrapper::register();
        $root = vfsStream::newDirectory('home/test');
        $root->getChild('test')->addChild(vfsStream::newFile('succeed.php'));
        $root->getChild('test')->addChild(vfsStream::newFile('succeed2.php'));
        $root->getChild('test')->addChild(vfsStream::newFile('succeed3.php'));
        vfsStreamWrapper::setRoot($root);
    }

    /**
     * Importing something that doesn't exist should raise an ImportError
     *
     * @expectedException ImportError
     * @test
     */
    public function ImportError() {
        import('non.existent.module');
    }

    /**
     * Should not assert or do anything if file includes. Anything
     * else shows lack of working php require_once. That would be bad.
     *
     * @test
     */
    public function ImportSingleFile() {
        IMPORTER::add_include_path(vfsStream::url('home'));
        import('test.succeed');
    }

    /**
     * Should not assert or do anything if file includes. Anything
     * else shows lack of working php require_once. That would be bad.
     *
     * @test
     */
    public function ImportMultiFile() {
        IMPORTER::add_include_path(vfsStream::url('home'));
        import('test.*');
    }
}

?>