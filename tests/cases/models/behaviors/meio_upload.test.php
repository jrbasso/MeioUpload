<?php
/**
 * MeioUpload Behavior Tests
 *
 * @package      meio_upload
 * @subpackage   meio_upload.tests.cases.models.behaviors
 * @since        3.0
 * @link         http://github.com/jrbasso/MeioUpload
 * @license      MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::import('Behavior', 'MeioUpload.MeioUpload');
define('MEIO_TESTS', dirname(dirname(dirname(dirname(__FILE__)))) . DS);

/**
 * MeioUploadTestBehavior
 *
 */
class MeioUploadTestBehavior extends MeioUploadBehavior {

	function replaceTokens(&$model, $string, $fieldName) {
		return $this->_replaceTokens($model, $string, $fieldName);
	}

	function fixName(&$model, $fieldName, $checkFile = true) {
		return $this->_fixName($model, $fieldName, $checkFile);
	}

	function splitFilenameAndExt($filename) {
		return $this->_splitFilenameAndExt($filename);
	}

	function sizeToBytes($size) {
		return $this->_sizeToBytes($size);
	}

	function createFolders($dir, $thumbsizes) {
		return $this->_createFolders($dir, $thumbsizes);
	}

	function _copyFileFromTemp($tmpName, $saveAs) {
		if (!copy($tmpName, $saveAs)) {
			return __d('meio_upload', 'Problems in the copy of the file.', true);
		}
		return true;
	}

	function readConfig($path) {
		return Set::extract($path, $this->_config);
	}

}

/**
 * Meio
 *
 */
class Meio extends CakeTestModel {
/**
 * Name
 *
 * @var string
 * @access public
 */
	var $name = 'Meio';

/**
 * Behaviors
 *
 * @var array
 * @access public
 */
	var $actsAs = array(
		'MeioUploadTest' => array(
			'filename' => array()
		)
	);

	function __construct($meioConfig = array()) {
		$this->actsAs = array(
			'MeioUploadTest' => array(
				'filename' => $meioConfig
			)
		);
		parent::__construct();
	}
}

/**
 * MeioUploadTestCase
 *
 */
class MeioUploadTestCase extends CakeTestCase {

/**
 * MeioUpload
 *
 * @var object
 * @access public
 */
	var $MeioUpload = null;

/**
 * TestModel
 *
 * @var object
 * @access public
 */
	var $TestModel = null;

/**
 * Fixtures
 *
 * @var array
 * @access public
 */
	var $fixtures = array('plugin.meio_upload.meio');

/**
 * start
 *
 * @return void
 * @access public
 */
	function start() {
		parent::start();
		$this->TestModel = new Meio();
		$this->MeioUpload =& $this->TestModel->Behaviors->MeioUploadTest;
		$file = new File(TMP . 'tests' . DS . 'meio');
		$file->delete();
	}

/**
 * end
 *
 * @return void
 * @access public
 */
	function end() {
		parent::end();
		$file = new File(TMP . 'tests' . DS . 'meio');
		$file->delete();
	}

/**
 * testReplaceTokens
 *
 * @return void
 * @access public
 */
	function testReplaceTokens() {
		$result = $this->MeioUpload->replaceTokens($this->TestModel, 'test', 'field');
		$this->assertEqual($result, 'test');

		$result = $this->MeioUpload->replaceTokens($this->TestModel, '{Model}aaa', 'field');
		$this->assertEqual($result, '{Model}aaa');

		$result = $this->MeioUpload->replaceTokens($this->TestModel, '{ModelName}aaa{fieldName}xxx{DS}iii\\o//', 'field');
		$this->assertEqual($result, 'meioaaafieldxxx' . DS . 'iii' . DS . 'o' . DS . DS);
	}

/**
 * testFixName
 *
 * @return void
 * @access public
 */
	function testFixName() {
		$this->TestModel->data = array(
			$this->TestModel->alias => array(
				'filename' => array(
					'name' => 'xxx.jpg'
				),
				'otherfield' => 'nice'
			)
		);

		$this->MeioUpload->fixName($this->TestModel, 'filename', false);
		$this->assertEqual($this->TestModel->data[$this->TestModel->alias]['filename']['name'], 'xxx.jpg');

		$this->TestModel->data[$this->TestModel->alias]['filename']['name'] = 'default.jpg';
		$this->MeioUpload->fixName($this->TestModel, 'filename', false);
		$this->assertEqual($this->TestModel->data[$this->TestModel->alias]['filename']['name'], 'default.jpg');

		$this->TestModel->data[$this->TestModel->alias]['filename']['name'] = 'default_1.hello.jpg';
		$this->MeioUpload->fixName($this->TestModel, 'filename', false);
		$this->assertEqual($this->TestModel->data[$this->TestModel->alias]['filename']['name'], 'default_1_hello.jpg');

		$file = WWW_ROOT . 'uploads' . DS . 'meio' . DS . 'filename' . DS . 'default.jpg';
		if ($this->skipIf(!@touch($file), 'Fail to create file.')) {
			return;
		}
		$this->TestModel->data[$this->TestModel->alias]['filename']['name'] = 'default.jpg';
		$this->MeioUpload->fixName($this->TestModel, 'filename', true);
		$this->assertEqual($this->TestModel->data[$this->TestModel->alias]['filename']['name'], 'default-0.jpg');
		unlink($file);
	}

/**
 * testSplitFilenameAndExt
 *
 * @return void
 * @access public
 */
	function testSplitFilenameAndExt() {
		$result = $this->MeioUpload->splitFilenameAndExt('default.jpg');
		$this->assertEqual($result, array('default', 'jpg'));

		$result = $this->MeioUpload->splitFilenameAndExt('default-1.jpg');
		$this->assertEqual($result, array('default-1', 'jpg'));

		$result = $this->MeioUpload->splitFilenameAndExt('default_1.jpg');
		$this->assertEqual($result, array('default_1', 'jpg'));

		$result = $this->MeioUpload->splitFilenameAndExt('default.xxx');
		$this->assertEqual($result, array('default', 'xxx'));

		$result = $this->MeioUpload->splitFilenameAndExt('default.hi.jpg');
		$this->assertEqual($result, array('default.hi', 'jpg'));
	}

/**
 * testSizeToBytes
 *
 * @return void
 * @access public
 */
	function testSizeToBytes() {
		$result = $this->MeioUpload->sizeToBytes(100);
		$this->assertEqual($result, 100);

		$result = $this->MeioUpload->sizeToBytes(2000);
		$this->assertEqual($result, 2000);

		$result = $this->MeioUpload->sizeToBytes('1KB');
		$this->assertEqual($result, 1024);

		$result = $this->MeioUpload->sizeToBytes('1 KB');
		$this->assertEqual($result, 1024);

		$result = $this->MeioUpload->sizeToBytes('1 kb');
		$this->assertEqual($result, 1024);

		$this->expectError();
		$result = $this->MeioUpload->sizeToBytes('1 xx');
		$this->assertEqual($result, 2097152);
	}

/**
 * testCreateFolders
 *
 * @return void
 * @access public
 */
	function testCreateFolders() {
		if ($this->skipIf(is_dir(TMP . 'tests' . DS . 'test'), 'Directory "test" in app/tmp/tests exists.')) {
			return;
		}
		$this->MeioUpload->createFolders(TMP . 'tests' . DS . 'test', array('a', 'b', 'c'));
		$this->assertTrue(is_dir(TMP . 'tests' . DS . 'test'));
		$this->assertTrue(is_dir(TMP . 'tests' . DS . 'test' . DS . 'thumb' . DS . 'a'));
		$this->assertTrue(is_dir(TMP . 'tests' . DS . 'test' . DS . 'thumb' . DS . 'b'));
		$this->assertTrue(is_dir(TMP . 'tests' . DS . 'test' . DS . 'thumb' . DS . 'c'));
		$this->assertFalse(is_dir(TMP . 'tests' . DS . 'test' . DS . 'thumb' . DS . 'd'));
		$folder = new Folder();
		$folder->cd(TMP . 'tests' . DS . 'test');
		$folder->delete();
	}

/**
 * testChangeDir
 *
 * @return void
 * @access public
 */
	function testChangeDir() {
		$model = new Meio(array('dir' => TMP));
		$this->assertEqual($model->Behaviors->MeioUploadTest->readConfig('Meio.filename.dir') . DS, TMP);
		$model->Behaviors->MeioUploadTest->changeDir($model, 'filename', CACHE);
		$this->assertEqual($model->Behaviors->MeioUploadTest->readConfig('Meio.filename.dir') . DS, CACHE);
	}

/**
 * testUploadDir
 *
 * @return void
 * @access public
 */
	function testUploadDir() {
		$model = new Meio();
		$result = $model->Behaviors->MeioUploadTest->readConfig('Meio.filename.dir');
		$this->assertEqual(WWW_ROOT . 'uploads' . DS . 'meio' . DS . 'filename', $result);

		$model = new Meio(array('dir' => 'testing'));
		$result = $model->Behaviors->MeioUploadTest->readConfig('Meio.filename.dir');
		$this->assertEqual(WWW_ROOT . 'testing', $result);

		$model = new Meio(array('dir' => TMP . 'tests' . DS . 'meio'));
		$data = array(
			'Meio' => array(
				'filename' => array(
					'name' => 'test.png',
					'type' => 'image/png',
					'tmp_name' => MEIO_TESTS . 'files' . DS . '1.png',
					'error' => UPLOAD_ERR_OK,
					'size' => 95
				)
			)
		);
		$model->create();
		$this->assertTrue($model->save($data));
		$this->assertTrue(file_exists(TMP . 'tests' . DS . 'meio' . DS . 'test.png'));
		@unlink(TMP . 'tests' . DS . 'meio' . DS . 'test.png');
	}

}
