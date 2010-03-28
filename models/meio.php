<?php

class Meio extends AppModel {

	var $actsAs = array(
		'MeioUpload.MeioUpload' => array(
			'filename' => array(
				'allowedMime' => array('image/jpeg', 'image/pjpeg', 'image/png', 'image/gif', 'image/bmp', 'image/x-icon', 'image/vnd.microsoft.icon', 'application/octet-stream')
			)
		)
	);

	function __construct($id = false, $table = null, $ds = null) {
		if (Configure::read() == 0) {
			$this->cakeError('error500');
		}
		parent::__construct($id, $table, $ds);
	}

}

?>