<?php
/**
 * MeioUpload Behavior
 *
 *
 * @package meio_upload
 * @subpackage meio_upload.models.behaviors
 * @version 3.0
 */
App::import('Core', array('File', 'Folder'));

class MeioUploadBehavior extends ModelBehavior {
/**
 * The default options for the behavior
 *
 * @var array
 * @access protected
 */
	var $_defaultOptions = array(
		'dir' => 'uploads{DS}{ModelName}{DS}{fieldName}',
		'fixFilename' => true,
		'maxSize' => 2097152, // 2MB
		'allowedMime' => array('image/jpeg', 'image/pjpeg', 'image/png', 'image/gif', 'image/bmp', 'image/x-icon', 'image/vnd.microsoft.icon'),
		'allowedExt' => array('.jpg', '.jpeg', '.png', '.gif', '.bmp', '.ico'),
		'zoomCrop' => false, // Whether to use ZoomCrop or not with PHPThumb
		'thumbsizes' => array(
			// Place any custom thumbsize in model config instead,
		),
		'thumbnailQuality' => 75, // Global Thumbnail Quality
		'useImageMagick' => false,
		'imageMagickPath' => '/usr/bin/convert', // Path to imageMagick on your server
		'fields' => array(
			'dir' => 'dir',
			'filesize' => 'filesize',
			'mimetype' => 'mimetype'
		),
		'length' => array(
			'minWidth' => 0, // 0 for not validates
			'maxWidth' => 0,
			'minHeight' => 0,
			'maxHeight' => 0
		),
		'validations' => array()
	);

/**
 * Default validations
 *
 * @var array
 * @access protected
 */
	var $_defaultValidations = array(
		'FieldName' => array(
			'rule' => array('uploadCheckFieldName'),
			'check' => true,
			'last' => true
		),
		'Dir' => array(
			'rule' => array('uploadCheckDir'),
			'check' => true,
			'last' => true
		),
		'Empty' => array(
			'rule' => array('uploadCheckEmpty'),
			'check' => true,
			'on' => 'create',
			'last' => true
		),
		'UploadError' => array(
			'rule' => array('uploadCheckUploadError'),
			'check' => true,
			'last' => true
		),
		'MaxSize' => array(
			'rule' => array('uploadCheckMaxSize'),
			'check' => true,
			'last' => true
		),
		'InvalidMime' => array(
			'rule' => array('uploadCheckInvalidMime'),
			'check' => true,
			'last' => true
		),
		'InvalidExt' => array(
			'rule' => array('uploadCheckInvalidExt'),
			'check' => true,
			'last' => true
		),
		'MinWidth' => array(
			'rule' => array('uploadCheckMinWidth'),
			'check' => true,
			'last' => true
		),
		'MaxWidth' => array(
			'rule' => array('uploadCheckMaxWidth'),
			'check' => true,
			'last' => true
		),
		'MinHeight' => array(
			'rule' => array('uploadCheckMinHeight'),
			'check' => true,
			'last' => true
		),
		'MaxHeight' => array(
			'rule' => array('uploadCheckMaxHeight'),
			'check' => true,
			'last' => true
		),
	);

/**
 * Array of all possible images that can be converted to thumbnails
 *
 * @var array
 * @access protected
 */
	var $_imageTypes = array('image/jpeg', 'image/pjpeg', 'image/png', 'image/gif', 'image/bmp', 'image/x-icon', 'image/vnd.microsoft.icon');

/**
 * The array that saves the $options for the behavior
 *
 * @var array
 * @access private
 */
	var $__fields = array();

/**
 * Array of files to be removed on the afterSave callback
 *
 * @var access
 * @access private
 */
	var $__filesToRemove = array();

/**
 * Constructor
 *
 */
	function __construct() {
		$messages = array(
			'FieldName' => array(
				'message' => __d('meio_upload', 'This field has not been defined between the parameters of MeioUploadBehavior.', true)
			),
			'Dir' => array(
				'message' => __d('meio_upload', 'The directory where the file would be placed there or is protected against writing.', true)
			),
			'Empty' => array(
				'message' => __d('meio_upload', 'The file can not be empty.', true)
			),
			'UploadError' => array(
				'message' => __d('meio_upload', 'There were problems in uploading the file.', true)
			),
			'MaxSize' => array(
				'message' => __d('meio_upload', 'The maximum file size is exceeded.', true)
			),
			'InvalidMime' => array(
				'message' => __d('meio_upload', 'Invalid file type.', true)
			),
			'InvalidExt' => array(
				'message' => __d('meio_upload', 'Invalid file extension.', true)
			),
			'MinWidth' => array(
				'message' => __d('meio_upload', 'Image width is smaller than minimum allowed.', true)
			),
			'MinHeight' => array(
				'message' => __d('meio_upload', 'Image height is smaller than minimum allowed.', true)
			),
			'MaxWidth' => array(
				'message' => __d('meio_upload', 'Image width is larger than maximum allowed.', true)
			),
			'MaxHeight' => array(
				'message' => __d('meio_upload', 'Image height is larger than maximum allowed.', true)
			)
		);
		$this->_defaultOptions['validations'] = Set::merge($this->_defaultValidations, $messages);
	}

/**
 * Setup the behavior.
 * It stores a reference to the model, merges the default options with the options for each field, and setup the validation rules.
 *
 * @param object $model
 * @param array $settings
 * @return void
 * @access public
 */
	function setup(&$model, $settings = array()) {
		$this->__fields[$model->alias] = array();
		foreach ($settings as $field => $options) {
			if (!is_array($options)) {
				$field = $options;
				$options = array();
			}

			// Auto set useTable
			$options['useTable'] = $model->useTable;

			// Merge given options with defaults
			$options = Set::merge($this->_defaultOptions, $options);

			// Check if given field exists
			if ($options['useTable'] && !$model->hasField($field)) {
				trigger_error(sprintf(__d('meio_upload', 'MeioUploadBehavior Error: The field "%s" doesn\'t exists in the model "%s".', true), $field, $model->alias), E_USER_WARNING);
			}

			// Verifies if the thumbsizes names is alphanumeric
			foreach ($options['thumbsizes'] as $name => $size) {
				if (!ctype_alnum($name)) {
					trigger_error(__d('meio_upload', 'MeioUploadBehavior Error: The thumbsizes names must be alphanumeric.', true), E_USER_ERROR);
				}
			}

			// Process the max_size if it is not numeric
			$options['maxSize'] = $this->_sizeToBytes($options['maxSize']);

			// Replace tokens of the dir and field, check it doesn't have a DS on the end
			$options['dir'] = rtrim($this->_replaceTokens($model, $options['dir'], $field), DS);

			// Create the folders for the uploads
			$this->_createFolders($options['dir'], array_keys($options['thumbsizes']));

			// Replace tokens in the fields names
			if ($options['useTable']) {
				foreach ($options['fields'] as $fieldToken => $fieldName) {
					$options['fields'][$fieldToken] = $this->_replaceTokens($model, $fieldName, $field);
				}
			}
			$this->__fields[$model->alias][$field] = $options;
		}
	}

/**
 * Sets the validation rules for each field.
 *
 * @param object $model
 * @return boolean Always true
 * @access public
 */
	function beforeValidate(&$model) {
		static $setup = false;
		if ($setup === false) {
			foreach ($this->__fields[$model->alias] as $fieldName => $options) {
				$this->_setupValidation($model, $fieldName, $options);
			}
			$setup = true;
		}
		return true;
	}

/**
 * Initializes the upload
 *
 * @param object $model
 * @return boolean Whether the upload completed
 * @access public
 */
	function beforeSave(&$model) {
		$result = $this->_uploadFile($model);
		$allOk = true;
		foreach ($result as $fieldName => $return) {
			if ($return['return'] === false) {
				if ($return['reason'] === 'validation') {
					$model->validationErrors[$fieldName] = $return['error'];
					$allOk = false;
				}
			}
		}
		return $allOk;
	}

/**
 * Deletes the files marked to be deleted in the save method.
 * A file can be marked to be deleted if it is overwriten by
 * another or if the user mark it to be deleted.
 *
 * @param object $model
 * @return void
 * @access public
 */
	function afterSave(&$model) {
		$this->_removeListOfFiles();
	}

/**
 * Mark to delete files that the registers will be deleted
 *
 * @param object $model
 * @return boolean Always true
 * @access public
 */
	function beforeDelete(&$model) {
		$model->read(null, $model->id);
		if (isset($model->data)) {
			foreach ($this->__fields[$model->alias] as $field => $options) {
				$this->_setFileToRemove($model, $field);
			}
		}
		return true;
	}

/**
 * Deletes the files marked to be deleted in the delete method.
 * A file can be marked to be deleted if it is overwriten by
 * another or if the user mark it to be deleted.
 *
 * @param object $model
 * @return void
 * @access public
 */
	function afterDelete(&$model) {
		$this->_removeListOfFiles();
	}

/**
 * Checks if the field was declared in the MeioUpload Behavior setup
 *
 * @param object $model
 * @param array $data
 * @return boolean
 * @access public
 */
	function uploadCheckFieldName(&$model, $data) {
		foreach ($data as $fieldName => $field) {
			if (!$model->validate[$fieldName]['FieldName']['check']) {
				continue;
			}
			if (isset($this->__fields[$model->alias][$fieldName])) {
				continue;
			} else {
				trigger_error(sprintf(__d('meio_upload', 'MeioUploadBehavior Error: The field "%s" wasn\'t declared as part of the MeioUploadBehavior in model "%s".', true), $fieldName, $model->alias), E_USER_WARNING);
				return false;
			}
		}
		return true;
	}

/**
 * Checks if the folder exists or can be created or writable.
 *
 * @param object $model
 * @param array $data
 * @return boolean
 * @access public
 */
	function uploadCheckDir(&$model, $data) {
		foreach ($data as $fieldName => $field) {
			if (!$model->validate[$fieldName]['Dir']['check']) {
				continue;
			}
			$options = $this->__fields[$model->alias][$fieldName];
			if (empty($field['remove']) || empty($field['name'])) {
				// Check if directory exists and create it if required
				if (!is_dir($options['dir'])) {
					$folder = &new Folder();
					if (!$folder->create($options['dir'])) {
						trigger_error(sprintf(__d('meio_upload', 'MeioUploadBehavior Error: The directory %s does not exist and cannot be created.', true), $options['dir']), E_USER_WARNING);
						return false;
					}
				}

				// Check if directory is writable
				if (!is_writable($options['dir'])) {
					trigger_error(sprintf(__d('meio_upload', 'MeioUploadBehavior Error: The directory %s isn\'t writable.', true), $options['dir']), E_USER_WARNING);
					return false;
				}
			}
		}
		return true;
	}

/**
 * Checks if the filename is not empty.
 *
 * @param object $model
 * @param array $data
 * @return boolean
 * @access public
 */
	function uploadCheckEmpty(&$model, $data) {
		foreach ($data as $fieldName => $field) {
			if (!$model->validate[$fieldName]['Empty']['check']) {
				continue;
			}
			if (empty($field['remove'])) {
				if (empty($field['name'])) {
					return false;
				}
			}
		}
		return true;
	}

/**
 * Checks if ocurred erros in the upload.
 *
 * @param object $model
 * @param array $data
 * @return boolean
 * @access public
 */
	function uploadCheckUploadError(&$model, $data) {
		foreach ($data as $fieldName => $field) {
			if (!$model->validate[$fieldName]['UploadError']['check']) {
				continue;
			}
			if (!empty($field['name']) && $field['error'] > 0) {
				return false;
			}
		}
		return true;
	}

/**
 * Checks if the file isn't bigger then the max file size option.
 *
 * @param object $model
 * @param array $data
 * @return boolean
 * @access public
 */
	function uploadCheckMaxSize(&$model, $data) {
		foreach ($data as $fieldName => $field) {
			if (!$model->validate[$fieldName]['MaxSize']['check']) {
				continue;
			}
			$options = $this->__fields[$model->alias][$fieldName];
			if (!empty($field['name']) && $field['size'] > $options['maxSize']) {
				return false;
			}
		}
		return true;
	}

/**
 * Checks if the file is of an allowed mime-type.
 *
 * @param object $model
 * @param array $data
 * @return boolean
 * @access public
 */
	function uploadCheckInvalidMime(&$model, $data) {
		foreach ($data as $fieldName => $field) {
			if (!$model->validate[$fieldName]['InvalidMime']['check']) {
				continue;
			}
			$options = $this->__fields[$model->alias][$fieldName];
			if (!empty($field['name']) && !empty($options['allowedMime']) && !in_array($field['type'], $options['allowedMime'])) {
				return false;
			}
		}
		return true;
	}

/**
 * Checks if the file has an allowed extension.
 *
 * @param object $model
 * @param array $data
 * @return boolean
 * @access public
 */
	function uploadCheckInvalidExt(&$model, $data) {
		foreach ($data as $fieldName => $field) {
			if (!$model->validate[$fieldName]['InvalidExt']['check']) {
				continue;
			}
			$options = $this->__fields[$model->alias][$fieldName];
			if (!empty($field['name'])) {
				if (!empty($options['allowedExt'])) {
					$matches = 0;
					foreach ($options['allowedExt'] as $extension) {
						if (strtolower(substr($field['name'], -strlen($extension))) === strtolower($extension)) {
							$matches = 1;
							break;
						}
					}

					if ($matches === 0) {
						return false;
					}
				}
			}
		}
		return true;
	}

/**
 * Checks if the min width is allowed
 *
 * @param object $model
 * @param array $data
 * @return boolean
 * @access public
 */
	function uploadCheckMinWidth(&$model, $data) {
		return $this->_uploadCheckSize($model, $data, 'minWidth');
	}

/**
 * Checks if the max width is allowed
 *
 * @param object $model
 * @param array $data
 * @return boolean
 * @access public
 */
	function uploadCheckMaxWidth(&$model, $data) {
		return $this->_uploadCheckSize($model, $data, 'maxWidth');
	}

/**
 * Checks if the min height is allowed
 *
 * @param object $model
 * @param array $data
 * @return boolean
 * @access public
 */
	function uploadCheckMinHeight(&$model, $data) {
		return $this->_uploadCheckSize($model, $data, 'minHeight');
	}

/**
 * Checks if the max height is allowed
 *
 * @param object $model
 * @param array $data
 * @return boolean
 * @access public
 */
	function uploadCheckMaxHeight(&$model, $data) {
		return $this->_uploadCheckSize($model, $data, 'maxHeight');
	}

/**
 * Check generic to size of image
 *
 * @param object $model
 * @param array $data
 * @param string $type Values: maxWidth, minWidth, maxHeight, minHeight
 * @return boolean
 * @access protected
 */
	function _uploadCheckSize(&$model, &$data, $type) {
		foreach ($data as $fieldName => $field) {
			if (!$model->validate[$fieldName][ucfirst($type)]['check'] || empty($field['tmp_name'])) {
				continue;
			}
			$options = $this->__fields[$model->alias][$fieldName];
			list($imgWidth, $imgHeight) = getimagesize($field['tmp_name']);
			$imgType = 'img' . substr($type, 3);
			if (substr($type, 0, 3) === 'min') {
				if (!empty($field['name']) && $options['length'][$type] > 0 && $$imgType < $options['length'][$type]) {
					return false;
				}
			} else {
				if (!empty($field['name']) && $options['length'][$type] > 0 && $$imgType > $options['length'][$type]) {
					return false;
				}
			}
		}
		return true;
	}

/**
 * Uploads the files
 *
 * @param object $model
 * @return array
 * @access protected
 */
	function _uploadFile(&$model) {
		$data =& $model->data;
		$return = array();
		foreach ($this->__fields[$model->alias] as $fieldName => $options) {
			if (!empty($data[$model->alias][$fieldName]['remove'])) {
				if (!empty($data[$model->alias][$model->primaryKey])) {
					$this->_setFileToRemove($model, $fieldName);
				}
				$this->_cleanFields($model, $fieldName);
				$return[$fieldName] = array('return' => true);
				continue;
			}
			// If no file was selected we do not need to proceed
			if (empty($data[$model->alias][$fieldName]['name'])) {
				unset($data[$model->alias][$fieldName]);
				$return[$fieldName] = array('return' => true);
				continue;
			}
			list(, $ext) = $this->_splitFilenameAndExt($data[$model->alias][$fieldName]['name']);

			// Check whether or not the behavior is in useTable mode
			if ($options['useTable'] === false) {
				$pos = strrpos($data[$model->alias][$fieldName]['type'], '/');
				$sub = substr($data[$model->alias][$fieldName]['type'], $pos + 1);
				$this->_fixName($model, $fieldName, false);
				$saveAs = $options['dir'] . DS . $sub;
			} else {
				// If no file has been upload, then unset the field to avoid overwriting existant file
				if (!isset($data[$model->alias][$fieldName]) || !is_array($data[$model->alias][$fieldName]) || empty($data[$model->alias][$fieldName]['name'])) {
					if (!empty($data[$model->alias][$model->primaryKey])) {
						unset($data[$model->alias][$fieldName]);
					} else {
						$data[$model->alias][$fieldName] = null;
					}
				}

				$this->_fixName($model, $fieldName);
				$saveAs = $options['dir'] . DS . $data[$model->alias][$fieldName]['name'];
			}

			// Attempt to move uploaded file
			$copyResults = $this->_copyFileFromTemp($data[$model->alias][$fieldName]['tmp_name'], $saveAs);
			if ($copyResults !== true) {
				$return[$fieldName] = array('return' => false, 'reason' => 'validation', 'error' => $copyResults);
				continue;
			}

			// If the file is an image, try to make the thumbnails
			if (!empty($options['thumbsizes']) && !empty($options['allowedExt']) && in_array($data[$model->alias][$fieldName]['type'], $this->_imageTypes)) {
				$this->_createThumbnails($model, $fieldName, $saveAs, $ext, $options);
			}

			// Update model data
			$data[$model->alias][$options['fields']['dir']] = $options['dir'];
			$data[$model->alias][$options['fields']['mimetype']] = $data[$model->alias][$fieldName]['type'];
			$data[$model->alias][$options['fields']['filesize']] = $data[$model->alias][$fieldName]['size'];
			$data[$model->alias][$fieldName] = $data[$model->alias][$fieldName]['name'];

			$return[$fieldName] = array('return' => true);
			continue;
		}
		return $return;
	}

/**
 * Create all the thumbnails
 *
 * @param object $model
 * @param string $fieldName
 * @param string $saveAs
 * @param string $ext
 * @param array $options
 * @return void
 * @access protected
 */
	function _createThumbnails(&$model, $fieldName, $saveAs, $ext, $options) {
		foreach ($options['thumbsizes'] as $key => $value) {
			// Generate the name for the thumbnail
			$thumbSaveAs = $options['dir'] . DS . 'thumb' . DS . $key . DS . $model->data[$model->alias][$fieldName]['name'];

			$checkItems = array('width', 'height', 'thumbnailQuality', 'zoomCrop');
			$params = array();
			foreach ($checkItems as $property) {
				if (isset($value[$property])) {
					$params[$property] = $value[$property];
				}
			}
			$this->_createThumbnail($model, $saveAs, $thumbSaveAs, $fieldName, $params);
		}
	}

/**
 * Function to create Thumbnail images
 *
 * @param object $model
 * @param string $source Source file name (without path)
 * @param string $target Target file name (without path)
 * @param string $fieldName Path to source and destination (no trailing DS)
 * @param array $params
 * @return void
 * @access protected
 */
	function _createThumbnail(&$model, $source, $target, $fieldName, $params = array()) {
		$params = array_merge(
			array(
				'thumbnailQuality' => $this->__fields[$model->alias][$fieldName]['thumbnailQuality'],
				'zoomCrop' => false
			),
			$params);

		// Import phpThumb class
		App::import('Vendor', 'phpthumb', array('file' => 'phpThumb' . DS . 'phpthumb.class.php'));

		// Configuring thumbnail settings
		$phpThumb = new phpthumb;
		$phpThumb->setSourceFilename($source);

		$w = isset($params['width']);
		$h = isset($params['height']);
		if ($w && $h) {
			$phpThumb->w = $params['width'];
			$phpThumb->h = $params['height'];
		} elseif ($w && !$h) {
			$phpThumb->w = $params['width'];
		} elseif ($h && !$w) {
			$phpThumb->h = $params['height'];
		} else {
			trigger_error(__d('meio_upload', 'Width and Height of thumbs not specified.', true), E_USER_WARNING);
			return;
		}

		$phpThumb->setParameter('zc', $params['zoomCrop']);
		$phpThumb->q = $params['thumbnailQuality'];

		list(, $phpThumb->config_output_format) = explode('.', $source, 2);
		$phpThumb->config_prefer_imagemagick = $this->__fields[$model->alias][$fieldName]['useImageMagick'];
		$phpThumb->config_imagemagick_path = $this->__fields[$model->alias][$fieldName]['imageMagickPath'];

		// Setting whether to die upon error
		$phpThumb->config_error_die_on_error = true;
		// Creating thumbnail
		if ($phpThumb->GenerateThumbnail()) {
			if (!$phpThumb->RenderToFile($target)) {
				trigger_error(sprintf(__d('meio_upload', 'Could not render image to: %s', true), $target), E_USER_WARNING);
			}
		}
	}

/**
 * Replaces some tokens. {ModelName} to the underscore version of the model name
 * {fieldName} to the field name, {DS}. / or \ to DS constant value.
 *
 * @param object $model
 * @param string $string
 * @param string $fieldName
 * @return string
 * @access protected
 */
	function _replaceTokens(&$model, $string, $fieldName) {
		return str_replace(
			array('{ModelName}', '{fieldName}', '{DS}', '/', '\\'),
			array(Inflector::underscore($model->name), $fieldName, DS, DS, DS),
			$string
		);
	}

/**
 * Removes the bad characters from the $filename. It updates the $model->data.
 *
 * @param object $model
 * @param string $fieldName
 * @param boolean $checkFile
 * @return void
 * @access protected
 */
	function _fixName(&$model, $fieldName, $checkFile = true) {
		// updates the filename removing the keywords thumb and default name for the field.
		if ($this->__fields[$model->alias][$fieldName]['fixFilename'] !== true) {
			return;
		}
		list ($filename, $ext) = $this->_splitFilenameAndExt($model->data[$model->alias][$fieldName]['name']);
		$filename = Inflector::slug($filename);
		$i = 0;
		$newFilename = $filename;
		if ($checkFile) {
			while (file_exists($this->__fields[$model->alias][$fieldName]['dir'] . DS . $newFilename . '.' . $ext)) {
				$newFilename = $filename . '-' . $i++;
			}
		}
		$model->data[$model->alias][$fieldName]['name'] = $newFilename . '.' . $ext;
	}

/**
 * Splits a filename in two parts: the name and the extension. Returns an array with it respectively.
 *
 * @param string $filename
 * @return array
 * @access protected
 */
	function _splitFilenameAndExt($filename) {
		extract(pathinfo($filename));
		if (!isset($filename)) {
			$filename = substr($basename, 0, -1 - count($extension)); // Remove extension and .
		}
		return array($filename, $extension);
	}

/**
 * Convert a size value to bytes. For example: 2 MB to 2097152.
 *
 * @param string $size
 * @return int
 * @access protected
 */
	function _sizeToBytes($size) {
		if (is_numeric($size) || ctype_digit($size)) {
			return (int)$size;
		}
		if (!preg_match('/^(\d+) ?(kb|mb|gb|tb)$/i', $size, $matches)) {
			trigger_error(__d('meio_upload', 'MeioUploadBehavior Error: The maxSize option format is invalid. Using 2 MB (default).', true), E_USER_WARNING);
			return 2097152;
		}
		switch (strtolower($matches[2])) {
			case 'kb':
				return $matches[1] * 1024;
			case'mb':
				return $matches[1] * 1048576;
			case 'gb':
				return $matches[1] * 1073741824;
			case 'tb':
				return $matches[1] * 1099511627776;
		}
		return 2097152;
	}

/**
 * Sets the validation for each field, based on the options.
 *
 * @param object $model
 * @param string $fieldName
 * @param array $options
 * @return void
 * @access protected
 */
	function _setupValidation(&$model, $fieldName, $options) {
		$options = $this->__fields[$model->alias][$fieldName];

		if (isset($model->validate[$fieldName])) {
			if (isset($model->validate[$fieldName]['rule'])) {
				$model->validate[$fieldName] = array(
					'oldValidation' => $model->validates[$fieldName]
				);
			}
		} else {
			$model->validate[$fieldName] = array();
		}
		$model->validate[$fieldName] = Set::merge($this->_defaultValidations, $options['validations'], $model->validate[$fieldName]);
	}


/**
 * Creates thumbnail folders if they do not already exist
 *
 * @param string $dir Path to uploads
 * @param array $thumbsizes List of names of thumbnail type
 * @return void
 * @access protected
 */
	function _createFolders($dir, $thumbsizes) {
		if ($dir[0] !== '/') {
			$dir = WWW_ROOT . $dir;
		}
		$folder = new Folder();

		if (!$folder->cd($dir)) {
			$folder->create($dir);
		}
		if (!$folder->cd($dir. DS . 'thumb')) {
			$folder->create($dir . DS . 'thumb');
		}
		foreach ($thumbsizes as $thumbName) {
			if (!$folder->cd($dir . DS .'thumb' . DS . $thumbName)) {
				$folder->create($dir . DS . 'thumb' . DS . $thumbName);
			}
		}
	}

/**
 * Copies file from temporary directory to final destination
 *
 * @param string $tmpName full path to temporary file
 * @param string $saveAs full path to move the file to
 * @return mixed true is successful, error message if not
 * @access protected
 */
	function _copyFileFromTemp($tmpName, $saveAs) {
		if (!is_uploaded_file($tmpName)) {
			return false;
		}
		if (!move_uploaded_file($tmpName, $saveAs)) {
			return __d('meio_upload', 'Problems in the copy of the file.', true);
		}
		return true;
	}

/**
 * Set a file to be removed
 *
 * @parma object $model
 * @param string $fieldName
 * @return void
 * @access protected
 */
	function _setFileToRemove(&$model, $fieldName) {
		$filename = $model->field($fieldName);
		if (!empty($filename)) {
			$this->__filesToRemove[] = array(
				'field' => $fieldName,
				'dir' => $this->__fields[$model->alias][$fieldName]['dir'],
				'name' => $filename
			);
			foreach($this->__fields[$model->alias][$fieldName]['thumbsizes'] as $key => $sizes){
				$this->__filesToRemove[] = array(
					'field' => $fieldName,
					'dir' => $this->__fields[$model->alias][$fieldName]['dir'] . DS . 'thumb' . DS . $key,
					'name' => $filename
				);
			}
		}
	}

/**
 * Clean fields from $data
 *
 * @param object $model name of the Model
 * @param string $fieldName name of field that holds a reference to the file
 * @return void
 * @access protected
 */
	function _cleanFields(&$model, $fieldName) {
		$model->data[$model->alias][$fieldName] = '';

		$options = $this->__fields[$model->alias][$fieldName];
		$model->data[$model->alias][$options['fields']['dir']] = '';
		$model->data[$model->alias][$options['fields']['filesize']] = '';
		$model->data[$model->alias][$options['fields']['mimetype']] = '';
	}

/**
 * Remove invalid files
 *
 * @return void
 * @access protected
 */
	function _removeListOfFiles() {
		foreach ($this->__filesToRemove as $info) {
			$file =& new File(WWW_ROOT . DS . $info['dir'] . DS . $info['name']);
			$file->delete();
		}
	}
}
?>