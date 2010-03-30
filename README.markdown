# MeioUpload 3.0 Behavior Plugin

This behavior provides to upload files in your application, as well as the possibility to translate the error message (originally only in portuguese) and the use of phpThumb as a better thumbnail generator.

You can also use it in No-DB mode, which means that no data for the upload is stored within the database. You can further this by setting `var $useTable = false` in the model, which will both trigger No-DB mode AND allow quick uploads.


## Installation
- Clone from github: in your behaviors directory type `git clone git://github.com/jrbasso/MeioUpload.git plugins/meio_upload`
- Add as a git submodule: in your behaviors directory type `git submodule add git://github.com/jrbasso/MeioUpload.git plugins/meio_upload`
- Download an archive from github and extract it in `plugins/meio_upload`

* If you require thumbnails for image generation, download the latest copy of phpThumb and extract it into your vendors directory. Should end up like: /vendors/phpThumb/{files}. (http://phpthumb.sourceforge.net)

## Usage
In a model that needs uploading, add MeioUpload.MeioUpload as behavior, like this:

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
		echo $form->end('Submit');
	?>
You'll want to include any other fields in your Model as well. :)

Make sure your directory (app/webroot/uploads/image/ in this case, image changes to the name of your model) is at least CHMOD 775, also check your php.ini MAX_FILE_SIZE is enough to support the filesizes you are uploading (default in the behavior is 2MB, can be configured)

The behavior code will save the uploaded file's name in the 'filename' field in database, it will not overwrite existing files, instead it will create a new filename based on the original plus a counter.

### Parameters of configuration

For each field, you can set the above parameters:

 * **useTable** (Boolean): Set if you are using table. If you not set, behaviour will use model value;
 * **dir** (String): Set the directory that files and thums will be saved. You can use macros *{ModelName}*, *{fieldName}* and *{DS}*. Default is 'uploads{DS}{ModelName}{DS}{fieldName}';
 * **fixFilename** (Boolean): Slug the filename to save. Default is true;
 * **maxSize** (Integer or String): Max filefize. You can set a integer (in bytes) or string like '1 MB'. Default is 2097152 (2MB);
 * **allowedMime** (Array): List of mimetypes allowed. Default is array('image/jpeg', 'image/pjpeg', 'image/png', 'image/gif', 'image/bmp', 'image/x-icon', 'image/vnd.microsoft.icon');
 * **allowedExt** (Array): List of extensions allowed. Default is array('.jpg', '.jpeg', '.png', '.gif', '.bmp', '.ico');
 * **zoomCrop** (String or False): False (boolean) or "C" (Center), "T" (Top), "B" (Bottom), "L" (Left), "R" (Right), "TL" (Top Left), "TR" (Top Right), "BL" (Bottom Left) or "BR" (Bottom Right). Default is false;
 * **thumbsizes** (Array): List of thumbs. The key of array is name of thumb and value must be a array with follow options:
   * **width**: Width of thumb. Optional if height is set;
   * **height**: Height of thumb. Optional if width is set;
   * **thumbnailQuality** (Optional): Quality of thumb. Value can be between 1 and 100;
   * **zoomCrop** (Optional): Some of zoomCrop of parameters.
 * **useImageMagick** (Boolean): If will use Image Magick. Default is false;
 * **imageMagickPath** (String): Path of Image Magick executable. Only is need if useImageMagick is true. Default is '/usr/bin/convert';
 * **fields** (Array): Set name of fields to model. Can set 'dir', 'filesize' and 'mimetype'. Default is array('dir' => 'dir', 'filesize' => 'filesize', 'mimetype' => 'mimetype');
 * **length** (Array): Define minimum and maximum width and height of uploaded image.

### Validations

 * FieldName
 * Dir
 * Empty
 * UploadError
 * MaxSize
 * InvalidMime
 * InvalidExt
 * MinWidth
 * MaxWidth
 * MinHeight
 * MaxHeight

### Deleting an uploaded file while keeping the record
Flag the file for deletion by setting `data[Model][filename][remove]` to something non-empty, e.g. `true`. The uploaded file including possible thumbnails will then be deleted together with adherent database fields upon save. Note that the record will be preserved, only the file meta-data columns will be reset.
