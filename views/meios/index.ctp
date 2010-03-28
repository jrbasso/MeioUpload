<html> 
	<head></head> 
	<body>
		<?php echo $form->create('Meio', array('type' => 'file')); ?>
		<label>File:<?php echo $form->input('filename', array('type' => 'file', 'label' => false, 'div' => false)); ?></label>
		<?php echo $form->end('Go'); ?>
	</body>
</html>