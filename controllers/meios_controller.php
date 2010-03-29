<?php

class MeiosController extends Controller {
	var $name = 'Meios';
	var $uses = array('MeioUpload.Meio');
	var $layout = 'ajax';

	function beforeFilter() {
		if (Configure::read() == 0) {
			$this->cakeError('error500');
		}
	}

	function index() {
		if (!empty($this->data)) {
			$this->Meio->save($this->data);
		}
	}

	function removefile() {
		$this->autoRender = false;
		$this->Meio->save(array(
			'Meio' => array(
				'id' => 1,
				'filename' => array(
					'remove' => true
				)
			)
		));
	}
}

?>