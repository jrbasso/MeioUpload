<?php

App::import('Behavior', 'MeioUpload.MeioUpload');

class Meiomodel extends CakeTestModel {
	var $name = 'Meiomodel';
	var $useTable = false;
	var $actsAs = array(
		'MeioUpload.MeioUpload' => array(
			'filename' => array()
		)
	);
}

class MeioUploadTestCase extends CakeTestCase {

	var $MeioUpload = null;
	var $TestModel = null;

	function start() {
		parent::start();
		$this->TestModel = new Meiomodel();
		$this->MeioUpload =& $this->TestModel->Behaviors->MeioUpload;
	}

	function end() {
		$folder =& new Folder(WWW_ROOT . 'uploads' . DS . 'meiomodel');
		$folder->delete();
		parent::end();
	}

	function testReplaceTokens() {
		$result = $this->MeioUpload->_replaceTokens($this->TestModel, 'test', 'field');
		$this->assertEqual($result, 'test');

		$result = $this->MeioUpload->_replaceTokens($this->TestModel, '{Model}aaa', 'field');
		$this->assertEqual($result, '{Model}aaa');

		$result = $this->MeioUpload->_replaceTokens($this->TestModel, '{ModelName}aaa{fieldName}xxx{DS}iii\\o//', 'field');
		$this->assertEqual($result, 'meiomodelaaafieldxxx' . DS . 'iii' . DS . 'o' . DS . DS);
	}

	function testFixName() {
		$this->TestModel->data = array(
			$this->TestModel->alias => array(
				'filename' => array(
					'name' => 'xxx.jpg'
				),
				'otherfield' => 'nice'
			)
		);

		$this->MeioUpload->_fixName($this->TestModel, 'filename', false);
		$this->assertEqual($this->TestModel->data[$this->TestModel->alias]['filename']['name'], 'xxx.jpg');

		$this->TestModel->data[$this->TestModel->alias]['filename']['name'] = 'default.jpg';
		$this->MeioUpload->_fixName($this->TestModel, 'filename', false);
		$this->assertEqual($this->TestModel->data[$this->TestModel->alias]['filename']['name'], 'default.jpg');

		$this->TestModel->data[$this->TestModel->alias]['filename']['name'] = 'default_1.hello.jpg';
		$this->MeioUpload->_fixName($this->TestModel, 'filename', false);
		$this->assertEqual($this->TestModel->data[$this->TestModel->alias]['filename']['name'], 'default_1_hello.jpg');

		$file = WWW_ROOT . 'uploads' . DS . 'meiomodel' . DS . 'filename' . DS . 'default.jpg';
		if ($this->skipIf(!touch($file), 'Fail to create file.')) {
			return;
		}
		$this->TestModel->data[$this->TestModel->alias]['filename']['name'] = 'default.jpg';
		$this->MeioUpload->_fixName($this->TestModel, 'filename', true);
		$this->assertEqual($this->TestModel->data[$this->TestModel->alias]['filename']['name'], 'default-0.jpg');
		unlink($file);
	}

	function testSplitFilenameAndExt() {
		$result = $this->MeioUpload->_splitFilenameAndExt('default.jpg');
		$this->assertEqual($result, array('default', 'jpg'));

		$result = $this->MeioUpload->_splitFilenameAndExt('default-1.jpg');
		$this->assertEqual($result, array('default-1', 'jpg'));

		$result = $this->MeioUpload->_splitFilenameAndExt('default_1.jpg');
		$this->assertEqual($result, array('default_1', 'jpg'));

		$result = $this->MeioUpload->_splitFilenameAndExt('default.xxx');
		$this->assertEqual($result, array('default', 'xxx'));

		$result = $this->MeioUpload->_splitFilenameAndExt('default.hi.jpg');
		$this->assertEqual($result, array('default.hi', 'jpg'));
	}

	function testSizeToBytes() {
		$result = $this->MeioUpload->_sizeToBytes(100);
		$this->assertEqual($result, 100);

		$result = $this->MeioUpload->_sizeToBytes(2000);
		$this->assertEqual($result, 2000);

		$result = $this->MeioUpload->_sizeToBytes('1KB');
		$this->assertEqual($result, 1024);

		$result = $this->MeioUpload->_sizeToBytes('1 KB');
		$this->assertEqual($result, 1024);

		$result = $this->MeioUpload->_sizeToBytes('1 kb');
		$this->assertEqual($result, 1024);

		$this->expectError();
		$result = $this->MeioUpload->_sizeToBytes('1 xx');
		$this->assertEqual($result, 2097152);
	}

	function testCreateFolders() {
		if ($this->skipIf(is_dir(WWW_ROOT . 'test'), 'Directory "test" in webroot exists.')) {
			return;
		}
		$this->MeioUpload->_createFolders('test', array('a', 'b', 'c'));
		$this->assertTrue(is_dir(WWW_ROOT . 'test'));
		$this->assertTrue(is_dir(WWW_ROOT . 'test' . DS . 'thumb' . DS . 'a'));
		$this->assertTrue(is_dir(WWW_ROOT . 'test' . DS . 'thumb' . DS . 'b'));
		$this->assertTrue(is_dir(WWW_ROOT . 'test' . DS . 'thumb' . DS . 'c'));
		$this->assertFalse(is_dir(WWW_ROOT . 'test' . DS . 'thumb' . DS . 'd'));
		$folder = new Folder();
		$folder->cd(WWW_ROOT . 'test');
		$folder->delete();
	}
}

class MeioUploadWebTest extends CakeWebTestCase {

	var $db;
	var $fixture;
	var $Model;

	function MeioUploadWebTest($label = false) {
		parent::CakeWebTestCase($label);

		Router::setRequestInfo(array(
			array(), array('base' => current(split("webroot", $_SERVER['PHP_SELF'])))
		));

		$this->db =& ConnectionManager::getDataSource('test');
		require_once App::pluginPath('meio_upload') . DS . 'tests' . DS . 'fixtures' . DS . 'meio_fixture.php';
		$this->fixture =& new MeioFixture($this->db);
		$this->fixture->drop($this->db);
	}

	function setUp() {
		parent::setUp();
		$this->fixture->create($this->db);
		$this->Model =& ClassRegistry::init('MeioUpload.Meio');
	}

	function tearDown() {
		$this->fixture->drop($this->db);
		$folder =& new Folder(WWW_ROOT . 'uploads' . DS . 'meio');
		$folder->delete();
		parent::tearDown();
	}

	function testSimpleUpload() {
		$url = Router::url(array('plugin' => 'meio_upload', 'controller' => 'meios'), true);
		$file = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views' . DS . 'themed' . DS . 'test_theme' . DS . 'webroot' . DS . 'img' . DS . 'test.jpg';
		if ($this->skipIf(!is_readable($file), 'File not readable.')) {
			return;
		}
		$this->assertTrue($this->get($url));
		$this->setField('File:', $file);
		$this->click('Go');
		$this->assertTrue(is_dir(WWW_ROOT . 'uploads' . DS . 'meio' . DS . 'filename' . DS . 'thumb'));
		$this->assertTrue(is_file(WWW_ROOT . 'uploads' . DS . 'meio' . DS . 'filename' . DS . 'test.jpg'));

		$result = $this->Model->read(null, 1);
		$expected = array(
			'Meio' => array(
				'id' => 1,
				'filename' => 'test.jpg',
				'dir' => 'uploads' . DS . 'meio' . DS . 'filename',
				'filesize' => filesize($file),
				'mimetype' => 'image/jpeg'
			)
		);
		$this->assertEqual($result, $expected);
	}

	function testConflitName() {
		$url = Router::url(array('plugin' => 'meio_upload', 'controller' => 'meios'), true);
		$file = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views' . DS . 'themed' . DS . 'test_theme' . DS . 'webroot' . DS . 'img' . DS . 'test.jpg';
		if ($this->skipIf(!is_readable($file), 'File not readable.')) {
			return;
		}
		$this->assertTrue($this->get($url));
		$this->setField('File:', $file);
		$this->click('Go');
		$this->assertTrue(is_file(WWW_ROOT . 'uploads' . DS . 'meio' . DS . 'filename' . DS . 'test.jpg'));
		$result = $this->Model->read(null, 1);
		$expected = array(
			'Meio' => array(
				'id' => 1,
				'filename' => 'test.jpg',
				'dir' => 'uploads' . DS . 'meio' . DS . 'filename',
				'filesize' => filesize($file),
				'mimetype' => 'image/jpeg'
			)
		);

		$this->assertTrue($this->get($url));
		$this->setField('File:', TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views' . DS . 'themed' . DS . 'test_theme' . DS . 'webroot' . DS . 'img' . DS . 'test.jpg');
		$this->click('Go');
		$this->assertTrue(is_file(WWW_ROOT . 'uploads' . DS . 'meio' . DS . 'filename' . DS . 'test-0.jpg'));
		$result = $this->Model->read(null, 2);
		$expected = array(
			'Meio' => array(
				'id' => 2,
				'filename' => 'test-0.jpg',
				'dir' => 'uploads' . DS . 'meio' . DS . 'filename',
				'filesize' => filesize($file),
				'mimetype' => 'image/jpeg'
			)
		);
	}

	function testThumb() {
		if ($this->skipIf(!App::import('Vendor', 'phpthumb', array('file' => 'phpThumb' . DS . 'phpthumb.class.php')), 'PHPThumb cant be loaded.')) {
			return;
		}
		if ($this->skipIf(!function_exists('getimagesize'), 'Function getimagesize not supported.')) {
			return;
		}
		$url = Router::url(array('plugin' => 'meio_upload', 'controller' => 'meios'), true);
		$file = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views' . DS . 'themed' . DS . 'test_theme' . DS . 'webroot' . DS . 'img' . DS . 'test.jpg';
		if ($this->skipIf(!is_readable($file), 'File not readable.')) {
			return;
		}
		$this->assertTrue($this->get($url));
		$this->setField('File:', $file);
		$this->click('Go');
		$this->assertTrue(is_file(WWW_ROOT . 'uploads' . DS . 'meio' . DS . 'filename' . DS . 'test.jpg'));
		$this->assertTrue(is_file(WWW_ROOT . 'uploads' . DS . 'meio' . DS . 'filename' . DS . 'thumb' . DS . 'mode1' . DS . 'test.jpg'));
		list($width, $height) = getimagesize(WWW_ROOT . 'uploads' . DS . 'meio' . DS . 'filename' . DS . 'thumb' . DS . 'mode1' . DS . 'test.jpg');
		$this->assertEqual(50, $width);

		$this->assertTrue(is_file(WWW_ROOT . 'uploads' . DS . 'meio' . DS . 'filename' . DS . 'thumb' . DS . 'mode2' . DS . 'test.jpg'));
		list($width, $height) = getimagesize(WWW_ROOT . 'uploads' . DS . 'meio' . DS . 'filename' . DS . 'thumb' . DS . 'mode2' . DS . 'test.jpg');
		$this->assertEqual(60, $height);

		$this->assertTrue(is_file(WWW_ROOT . 'uploads' . DS . 'meio' . DS . 'filename' . DS . 'thumb' . DS . 'mode3' . DS . 'test.jpg'));
		list($width, $height) = getimagesize(WWW_ROOT . 'uploads' . DS . 'meio' . DS . 'filename' . DS . 'thumb' . DS . 'mode3' . DS . 'test.jpg');
		$this->assertEqual(20, $width);
		$this->assertEqual(20, $height);

		$this->assertTrue(is_file(WWW_ROOT . 'uploads' . DS . 'meio' . DS . 'filename' . DS . 'thumb' . DS . 'mode4' . DS . 'test.jpg'));
		list($width, $height) = getimagesize(WWW_ROOT . 'uploads' . DS . 'meio' . DS . 'filename' . DS . 'thumb' . DS . 'mode4' . DS . 'test.jpg');
		$this->assertEqual(70, $width);
		$this->assertEqual(20, $height);
	}

	function testDeleteRow() {
		$url = Router::url(array('plugin' => 'meio_upload', 'controller' => 'meios'), true);
		$file = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views' . DS . 'themed' . DS . 'test_theme' . DS . 'webroot' . DS . 'img' . DS . 'test.jpg';
		if ($this->skipIf(!is_readable($file), 'File not readable.')) {
			return;
		}
		$this->assertTrue($this->get($url));
		$this->setField('File:', $file);
		$this->click('Go');
		$this->assertTrue(is_file(WWW_ROOT . 'uploads' . DS . 'meio' . DS . 'filename' . DS . 'test.jpg'));
		$this->assertTrue(is_file(WWW_ROOT . 'uploads' . DS . 'meio' . DS . 'filename' . DS . 'thumb' . DS . 'mode1' . DS . 'test.jpg'));
		$this->assertTrue(is_array($this->Model->read(null, 1)));
		$this->assertTrue($this->Model->delete(1));
		$this->assertFalse(is_file(WWW_ROOT . 'uploads' . DS . 'meio' . DS . 'filename' . DS . 'test.jpg'));
		$this->assertFalse(is_file(WWW_ROOT . 'uploads' . DS . 'meio' . DS . 'filename' . DS . 'thumb' . DS . 'mode4' . DS . 'test.jpg'));
	}

	function testRemoveFlag() {
		$url = Router::url(array('plugin' => 'meio_upload', 'controller' => 'meios'), true);
		$file = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views' . DS . 'themed' . DS . 'test_theme' . DS . 'webroot' . DS . 'img' . DS . 'test.jpg';
		if ($this->skipIf(!is_readable($file), 'File not readable.')) {
			return;
		}
		$this->assertTrue($this->get($url));
		$this->setField('File:', $file);
		$this->click('Go');
		$this->assertTrue(is_file(WWW_ROOT . 'uploads' . DS . 'meio' . DS . 'filename' . DS . 'test.jpg'));
		$this->assertTrue(is_file(WWW_ROOT . 'uploads' . DS . 'meio' . DS . 'filename' . DS . 'thumb' . DS . 'mode1' . DS . 'test.jpg'));

		$url = Router::url(array('plugin' => 'meio_upload', 'controller' => 'meios', 'action' => 'removefile'), true);
		$this->assertTrue($this->get($url));
		$data = $this->Model->read(null, 1);
		$this->assertTrue(is_array($data));
		$this->assertEqual($data['Meio']['filename'], '');
		$this->assertEqual($data['Meio']['dir'], '');
		$this->assertEqual($data['Meio']['filesize'], '');
		$this->assertEqual($data['Meio']['mimetype'], '');
		$this->assertFalse(is_file(WWW_ROOT . 'uploads' . DS . 'meio' . DS . 'filename' . DS . 'test.jpg'));
		$this->assertFalse(is_file(WWW_ROOT . 'uploads' . DS . 'meio' . DS . 'filename' . DS . 'thumb' . DS . 'mode4' . DS . 'test.jpg'));
	}
}
?>