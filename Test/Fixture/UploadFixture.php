<?php

class UploadFixture extends CakeTestFixture {

	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'first_file' => array('type' => 'string', 'length' => 255),
		'second_file' => array('type' => 'string', 'length' => 255),
		'third_file' => array('type' => 'string', 'length' => 255),
	);

}
