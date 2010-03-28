<?php
class MeioFixture extends CakeTestFixture {
    var $name = 'Meio';

    var $fields = array(
        'id' => array('type' => 'integer', 'key' => 'primary'),
        'filename' => array('type' => 'string', 'size' => 255),
        'dir' => array('type' => 'string', 'size' => 255),
		'filesize' => array('type' => 'string', 'size' => 255),
        'mimetype' => array('type' => 'string', 'size' => 255)
    );
}
?>