<?php

class Meio extends AppModel {

	var $actsAs = array(
		'MeioUpload.MeioUpload' => array(
			'filename' => array(
				'thumbsizes' => array(
					'mode1' => array(
						'width' => 50
					),
					'mode2' => array(
						'height' => 60
					),
					'mode3' => array(
						'width' => 70,
						'height' => 20
					),
					'mode4' => array(
						'width' => 70,
						'height' => 20,
						'zoomCrop' => 'C'
					)
				)
			)
		)
	);

	function save($data = null, $validate = true, $fieldList = array()) {
		// Changing this because simpletest always send image as 'application/octet-stream'
		if (isset($data['Meio']['filename']['type'])) {
			switch (substr($data['Meio']['filename']['name'], -4)) {
				case '.jpg':
				case '.jpeg':
					$data['Meio']['filename']['type'] = 'image/jpeg';
					break;
				case '.png':
					$data['Meio']['filename']['type'] = 'image/png';
					break;
				case '.gif':
					$data['Meio']['filename']['type'] = 'image/gif';
					break;
			}
		}
		return parent::save($data, $validate, $fieldList);
	}

	function __construct($id = false, $table = null, $ds = null) {
		if (Configure::read() == 0) {
			$this->cakeError('error500');
		}
		parent::__construct($id, $table, $ds);
	}

}

?>