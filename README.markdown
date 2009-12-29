# MeioUpload 2.0 Behavior Plugin

This behavior provides to upload files in your application, as well as the possibility to translate the error message (originally only in portuguese) and the use of phpThumb as a better thumbnail generator.

You can also use it in No-DB mode, which means that no data for the upload is stored within the database. You can further this by setting `var $useTable = false` in the model, which will both trigger No-DB mode AND allow quick uploads.


## Installation
- Clone from github : in your behaviors directory type `git clone git://github.com/jrbasso/MeioUpload.git plugins/meio_upload`
- Add as a git submodule : in your behaviors directory type `git submodule add git://github.com/jrbasso/MeioUpload.git plugins/meio_upload`
- Download an archive from github and extract it in `plugins/meio_upload`

* If you require thumbnails for image generation, download the latest copy of phpThumb and extract it into your vendors directory. Should end up like: /vendors/phpThumb/{files}. (http://phpthumb.sourceforge.net)

## Usage
In a model that needs uploading, replace the class declaration with something similar to the following:

	<?php
	class Image extends AppModel {
		var $name = 'Image';
		var $actsAs = array(
			'MeioUpload.MeioUpload' => array('filename')
		);
	}
	?>

You also need to specify the fields in your database like so
<pre>
``	CREATE TABLE `images` (``
``		`id` int(8) unsigned NOT NULL auto_increment,``
``		`filename` varchar(255) default NULL,``
``		`dir` varchar(255) default NULL,``
``		`mimetype` varchar(255) NULL,``
``		`filesize` int(11) unsigned default NULL,``
``		`created` datetime default NULL,``
``		`modified` datetime default NULL,``
``		PRIMARY KEY  (`id`)``
``	) ENGINE=MyISAM  DEFAULT CHARSET=utf8;``
</pre>

Create your upload view, make sure it's a multipart/form-data form, and the filename field is of type 'file':

	<?php
		echo $form->create('Image', array('type' => 'file'));
		echo $form->input('filename', array('type' => 'file'));
		echo $form->input('dir', array('type' => 'hidden'));
		echo $form->input('mimetype', array('type' => 'hidden'));
		echo $form->input('filesize', array('type' => 'hidden'));
		echo $form->end('Submit');
	?>
You'll want to include any other fields in your Model as well :)

Make sure your directory (app/webroot/uploads/image/ in this case, image changes to the name of your model) is at least CHMOD 775, also check your php.ini MAX_FILE_SIZE is enough to support the filesizes you are uploading (default in the behavior is 2MB, can be configured)

The behavior code will save the uploaded file's name in the 'filename' field in database, it will not overwrite existing files, instead it will create a new filename based on the original plus a counter. For thumbnails, when the file is uploaded, it will automatically create 3 thumbnail sizes (this can be configured) and prepend the name to the thumbfiles as directories(i.e. app/webroot/uploads/image/image_001.jpg will produced app/webroot/uploads/thumb/small/image/image_001.jpg, app/webroot/uploads/thumb/medium/image/image_001.jpg, app/webroot/uploads/thumb/large/image/image_001.jpg)

### Deleting an uploaded file while keeping the record
Flag the file for deletion by setting `data[Model][filename][remove]` to something non-empty, e.g. `TRUE`. The uploaded file including possible thumbnails will then be deleted together with adherent database fields upon save. Note that the record will be preserved, only the file meta-data columns will be reset.