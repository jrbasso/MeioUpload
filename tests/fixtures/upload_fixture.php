<?php  
class UploadFixture extends CakeTestFixture { 
    var $name = 'Upload
    '; 
     
    var $fields = array( 
        'id' => array('type' => 'integer', 'key' => 'primary'), 
        'first_file' => array('type' => 'varchar', 'size' => 255), 
        'second_file' => array('type' => 'varchar', 'size' => 255), 
        'third_file' => array('type' => 'varchar', 'size' => 255), 
    );
} 
?>