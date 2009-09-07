<?php  
class Upload extends CakeTestModel { 
    
    var $validate = array(
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
	
	var $actsAs = array(  
		'MeioUpload.MeioUpload' => array(  
			'first_file',
			'second_file',
			'third_file'
		) 
	); 

} 

class MultipleOptionalCase extends CakeTestCase { 
    var $fixtures = array( 'article_test' ); 
     
    function testMultipleOptional() { 
        $this->Upload =& new Upload(); 
         
        $result = $this->Upload->save(
        	//array('id', 'title')
        	// @TODO Needs to have 1 file being uploaded, other 2 slots empty (with file input array however)
        );
         
        $this->assertEqual($result, true); 
    } 
} 
?>