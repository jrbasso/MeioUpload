<?php

class Upload extends CakeTestModel {

	public $validate = array(
		'first_file' => array(
			'Empty' => array(
				'check' => false,
			),
		),
		'second_file' => array(
			'Empty' => array(
				'check' => false,
			),
		),
		'third_file' => array(
			'Empty' => array(
				'check' => false,
			),
		),
	);

	public $actsAs = array(
		'MeioUpload.MeioUpload' => array(
			'first_file',
			'second_file',
			'third_file'
		)
	);

}

class MeioUploadTestCase extends CakeTestCase {

	public $fixtures = array('plugin.meio_upload.upload');

	public $Upload;

	public function startTest() {
		$this->Upload =& ClassRegistry::init('Upload');
	}

	public function testMultipleOptional() {
		$result = $this->Upload->save(array(
			// @TODO Needs to have 1 file being uploaded, other 2 slots empty (with file input array however)
		));

		$this->assertNotEqual($result, false);
	}

}
