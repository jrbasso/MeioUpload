<?php
/**
 * MeioUpload Behavior
 *
 * This behavior is based on Vincius Mendes'  MeioUpload Behavior
 *  (http://www.meiocodigo.com/projects/meioupload/)
 * Which is in turn based upon Tane Piper's improved uplaod behavior
 *  (http://digitalspaghetti.tooum.net/switchboard/blog/2497:Upload_Behavior_for_CakePHP_12)
 *
 * @author Jose Diaz-Gonzalez (support@savant.be)
 * @author Juan Basso (jrbasso@gmail.com)
 * @package app
 * @subpackage app.models.behaviors
 * @filesource http://github.com/jrbasso/MeioUpload/tree/master
 * @version 2.1
 * @lastmodified 2010-01-03
 */
App::import('Core', array('File', 'Folder'));

class MeioUploadBehavior extends ModelBehavior {
/**
 * The default options for the behavior
 */
	var $defaultOptions = array(
		'useTable' => true,
		'createDirectory' => true,
		'dir' => 'uploads{DS}{ModelName}{DS}{fieldName}',
		'folderAsField' => null, // Can be the name of any field in $this->data
		'uploadName' => null, // Can also be the tokens {ModelName} or {fieldName}
		'maxSize' => 2097152, // 2MB
		'allowedMime' => array('image/jpeg', 'image/pjpeg', 'image/png', 'image/gif', 'image/bmp', 'image/x-icon', 'image/vnd.microsoft.icon'),
		'allowedExt' => array('.jpg', '.jpeg', '.png', '.gif', '.bmp', '.ico'),
		'default' => false, // Not sure what this does
		'zoomCrop' => false, // Whether to use ZoomCrop or not with PHPThumb
		'thumbnails' => true,
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

	var $defaultValidations = array(
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
 * The array that saves the $options for the behavior
 */
	var $__fields = array();

/**
 * Patterns of reserved words
 */
	var $patterns = array(
		'thumb',
		'default'
	);

/**
 * Words to replace the patterns of reserved words
 */
	var $replacements = array(
		't_umb',
		'd_fault'
	);

/**
 * Array of files to be removed on the afterSave callback
 */
	var $__filesToRemove = array();

/**
 * Array of all possible images that can be converted to thumbnails
 *
 * @var array
 **/
	var $_imageTypes = array('image/jpeg', 'image/pjpeg', 'image/png', 'image/gif', 'image/bmp', 'image/x-icon', 'image/vnd.microsoft.icon');

/**
 * Constructor
 *
 * @author Juan Basso
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
		$this->defaultValidations = $this->_arrayMerge($this->defaultValidations, $messages);
		$this->defaultOptions['validations'] = $this->defaultValidations;
	}

/**
 * Setup the behavior. It stores a reference to the model, merges the default options with the options for each field, and setup the validation rules.
 *
 * @param $model Object
 * @param $settings Array[optional]
 * @return null
 * @author Vinicius Mendes
 */
	function setup(&$model, $settings = array()) {
		$this->__fields[$model->alias] = array();
		foreach ($settings as $field => $options) {
			// Check if they even PASSED IN parameters
			if (!is_array($options)) {
				// You jerks!
				$field = $options;
				$options = array();
			}

			// Inherit model's lack of table use if not set in options
			// regardless of whether or not we set the table option
			if (!$model->useTable) {
				$options['useTable'] = false;
			}

			// Merge given options with defaults
			$options = $this->_arrayMerge($this->defaultOptions, $options);

			// Check if given field exists
			if ($options['useTable'] && !$model->hasField($field)) {
				trigger_error(sprintf(__d('meio_upload', 'MeioUploadBehavior Error: The field "%s" doesn\'t exists in the model "%s".', true), $field, $model->alias), E_USER_WARNING);
			}

			// Including the default name to the replacements
			if ($options['default']) {
				if (strpos($options['default'], '.') !== false) {
					trigger_error(__d('meio_upload', 'MeioUploadBehavior Error: The default option must be the filename with extension.', true), E_USER_ERROR);
				}
				$this->_includeDefaultReplacement($options['default']);
			}

			// Verifies if the thumbsizes names is alphanumeric
			if ($options['thumbnails'] == true) {
				foreach ($options['thumbsizes'] as $name => $size) {
					if (empty($name) || !ctype_alnum($name)) {
						trigger_error(__d('meio_upload', 'MeioUploadBehavior Error: The thumbsizes names must be alphanumeric.', true), E_USER_ERROR);
					}
				}
			}

			// Process the max_size if it is not numeric
			$options['maxSize'] = $this->_sizeToBytes($options['maxSize']);

			// Replace tokens of the dir and field, check it doesn't have a DS on the end
			$tokens = array('{ModelName}', '{fieldName}', '{DS}', '/', '\\');
			$options['dir'] = rtrim($this->_replaceTokens($model, $options['dir'], $field, $tokens), DS);
			$options['uploadName'] = rtrim($this->_replaceTokens($model, $options['uploadName'], $field, $tokens), DS);

			// Create the folders for the uploads
			// Create the folders for the uploads
			if (!empty($options['thumbsizes'])) {
				$this->_createFolders($options['dir'], array_keys($options['thumbsizes']));
			} else {
				$this->_createFolders($options['dir'], array());
			}

			// Replace tokens in the fields names
			if ($options['useTable']) {
				foreach ($options['fields'] as $fieldToken => $fieldName) {
					$options['fields'][$fieldToken] = $this->_replaceTokens($model, $fieldName, $field, $tokens);
				}
			}
			$this->__fields[$model->alias][$field] = $options;
		}
	}

/**
 * Sets the validation rules for each field.
 *
 * @param $model Object
 * @return true
 */
	function beforeValidate(&$model) {
		foreach ($this->__fields[$model->alias] as $fieldName => $options) {
			$this->_setupValidation($model, $fieldName, $options);
		}
		return true;
	}

/**
 * Initializes the upload
 *
 * @param $model Object
 * @return boolean Whether the upload completed
 * @author Jose Diaz-Gonzalez
 **/
	function beforeSave(&$model) {
		return $this->upload($model, null);
	}

/**
 * Deletes the files marked to be deleted in the save method.
 * A file can be marked to be deleted if it is overwriten by
 * another or if the user mark it to be deleted.
 *
 * @param $model Object
 * @author Vinicius Mendes
 */
	function afterSave(&$model) {
		foreach ($this->__filesToRemove as $file) {
			if (!empty($file['name'])) {
				$this->_deleteFiles($model, $file['field'], $file['name'], $file['dir']);
			}
		}
		// Reset the filesToRemove array
		$this->__filesToRemove = array();
	}

/**
 * Performs a manual upload
 *
 * @param $model Object
 * @param $data Array data to be saved
 * @return boolean Whether the upload completed
 * @author Jose Diaz-Gonzalez
 **/
	function upload(&$model, $data) {
		$result = $this->_uploadFile($model, $data);
		if (is_bool($result)) {
			return $result;
		} elseif (is_array($result)) {
			if ($result['return'] === false) {
				// Upload failed, lets see why
				switch($result['reason']) {
					case 'validation':
						$model->validationErrors[$result['extra']['field']] = $result['extra']['error'];
						break;
				}
				return false;
			} else {
				$this->data = $result['data'];
				return true;
			}
		} else {
			return false;
		}
	}

/**
 * Deletes all files associated with the record beforing delete it.
 *
 * @param $model Object
 * @author Vinicius Mendes
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
 * Checks if the field was declared in the MeioUpload Behavior setup
 *
 * @param $model Object
 * @param $data Array
 * @return boolean
 * @author Vinicius Mendes
 */
	function uploadCheckFieldName(&$model, $data) {
		foreach ($data as $fieldName => $field) {
			if (!$model->validate[$fieldName]['FieldName']['check']) {
				return true;
			}
			if (isset($this->__fields[$model->alias][$fieldName])) {
				return true;
			} else {
				$this->log(sprintf(__d('meio_upload', 'MeioUploadBehavior Error: The field "%s" wasn\'t declared as part of the MeioUploadBehavior in model "%s".', true), $fieldName, $model->alias));
				return false;
			}
		}
		return true;
	}

/**
 * Checks if the folder exists or can be created or writable.
 *
 * @return boolean
 * @param $model Object
 * @param $data Array
 * @author Vinicius Mendes
 */
	function uploadCheckDir(&$model, $data) {
		foreach ($data as $fieldName => $field) {
			if (!$model->validate[$fieldName]['Dir']['check']) {
				return true;
			}
			$options = $this->__fields[$model->alias][$fieldName];
			if (empty($field['remove']) || empty($field['name'])) {
				// Check if directory exists and create it if required
				if (!is_dir($options['dir'])) {
					if ($options['createDirectory']) {
						$folder = &new Folder();
						if (!$folder->create($options['dir'])) {
							trigger_error(sprintf(__d('meio_upload', 'MeioUploadBehavior Error: The directory %s does not exist and cannot be created.', true), $options['dir']), E_USER_WARNING);
							return false;
						}
					} else {
						trigger_error(sprintf(__d('meio_upload', 'MeioUploadBehavior Error: The directory %s does not exist.', true), $options['dir']), E_USER_WARNING);
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
 * @param $model Object
 * @param $data Array
 * @return boolean
 * @author Vinicius Mendes
 */
	function uploadCheckEmpty(&$model, $data) {
		foreach ($data as $fieldName => $field) {
			if (!$model->validate[$fieldName]['Empty']['check']) {
				return true;
			}
			if (empty($field['remove'])) {
				if (!is_array($field) || empty($field['name'])) {
					return false;
				}
			}
		}
		return true;
	}

/**
 * Checks if ocurred erros in the upload.
 *
 * @param $model Object
 * @param $data Array
 * @return boolean
 * @author Vinicius Mendes
 */
	function uploadCheckUploadError(&$model, $data) {
		foreach ($data as $fieldName => $field) {
			if (!$model->validate[$fieldName]['UploadError']['check']) {
				return true;
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
 * @param $model Object
 * @param $data Array
 * @return boolean
 * @author Vinicius Mendes
 */
	function uploadCheckMaxSize(&$model, $data) {
		foreach ($data as $fieldName => $field) {
			if (!$model->validate[$fieldName]['MaxSize']['check']) {
				return true;
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
 * @param $model Object
 * @param $data Array
 * @return boolean
 * @author Vinicius Mendes
 */
	function uploadCheckInvalidMime(&$model, $data) {
		foreach ($data as $fieldName => $field) {
			if (!$model->validate[$fieldName]['InvalidMime']['check']) {
				return true;
			}
			$options = $this->__fields[$model->alias][$fieldName];
			if (!empty($field['name']) && count($options['allowedMime']) > 0 && !in_array($field['type'], $options['allowedMime'])) {
				return false;
			}
		}
		return true;
	}

/**
 * Checks if the file has an allowed extension.
 *
 * @param $model Object
 * @param $data Array
 * @return boolean
 * @author Vinicius Mendes
 */
	function uploadCheckInvalidExt(&$model, $data) {
		foreach ($data as $fieldName => $field) {
			if (!$model->validate[$fieldName]['InvalidExt']['check']) {
				return true;
			}
			$options = $this->__fields[$model->alias][$fieldName];
			if (!empty($field['name'])) {
				if (count($options['allowedExt']) > 0) {
					$matches = 0;
					foreach ($options['allowedExt'] as $extension) {
						if (strtolower(substr($field['name'], -strlen($extension))) == strtolower($extension)) {
							$matches++;
						}
					}

					if ($matches == 0) {
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
 * @param $model Object
 * @param $data Array
 * @return boolean
 * @author Juan Basso
 */
	function uploadCheckMinWidth(&$model, $data) {
		return $this->_uploadCheckSize($model, $data, 'minWidth');
	}

/**
 * Checks if the max width is allowed
 *
 * @param $model Object
 * @param $data Array
 * @return boolean
 * @author Juan Basso
 */
	function uploadCheckMaxWidth(&$model, $data) {
		return $this->_uploadCheckSize($model, $data, 'maxWidth');
	}

/**
 * Checks if the min height is allowed
 *
 * @param $model Object
 * @param $data Array
 * @return boolean
 * @author Juan Basso
 */
	function uploadCheckMinHeight(&$model, $data) {
		return $this->_uploadCheckSize($model, $data, 'minHeight');
	}

/**
 * Checks if the max height is allowed
 *
 * @param $model Object
 * @param $data Array
 * @return boolean
 * @author Juan Basso
 */
	function uploadCheckMaxHeight(&$model, $data) {
		return $this->_uploadCheckSize($model, $data, 'maxHeight');
	}

/**
 * Check generic to size of image
 *
 * @param $model Object
 * @param $data Array
 * @param $type String Values: maxWidth, minWidth, maxHeight, minHeight
 * @return boolean
 * @author Juan Basso
 */
	function _uploadCheckSize(&$model, &$data, $type) {
		foreach ($data as $fieldName => $field) {
			if (!$model->validate[$fieldName][ucfirst($type)]['check'] || empty($field['tmp_name'])) {
				return true;
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
 * @param $model Object
 * @param $data Array Optional Containing data to be saved
 * @return array
 * @author Vinicius Mendes
 */
	function _uploadFile(&$model, $data = null) {
		if (!isset($data) || !is_array($data)) {
			$data =& $model->data;
		}
		foreach ($this->__fields[$model->alias] as $fieldName => $options) {
			// Take care of removal flagged field
			// However, this seems to be kind of code duplicating, see line ~711
			if (!empty($data[$model->alias][$fieldName]['remove'])) {
				$this->_markForDeletion($model, $fieldName, $data, $options['default']);
				$data = $this->_unsetDataFields($model->alias, $fieldName, $data, $options);
				$result = array('return' => true, 'data' => $data);
				continue;
			}
			// If no file was selected we do not need to proceed
			if (empty($data[$model->alias][$fieldName]['name'])) {
				unset($data[$model->alias][$fieldName]);
				$result = array('return' => true, 'data' => $data);
				continue;
			}
			$pos = strrpos($data[$model->alias][$fieldName]['type'], '/');
			$sub = substr($data[$model->alias][$fieldName]['type'], $pos+1);
			list(,$ext) = $this->_splitFilenameAndExt($data[$model->alias][$fieldName]['name']);

			// Put in a subfolder if the user wishes it
			if (isset($options['folderAsField']) && !empty($options['folderAsField']) && is_string($options['folderAsField'])) {
				$options['dir'] = $options['dir'] . DS . $data[$model->alias][$options['folderAsField']];
				$this->__fields[$model->alias][$fieldName]['dir'] = $options['dir'];
			}

			// Check whether or not the behavior is in useTable mode
			if ($options['useTable'] == false) {
				$this->_includeDefaultReplacement($options['default']);
				$this->_fixName($model, $fieldName, false);
				$saveAs = $options['dir'] . DS . $data[$model->alias][$options['uploadName']] . '.' . $sub;

				// Attempt to move uploaded file
				$copyResults = $this->_copyFileFromTemp($data[$model->alias][$fieldName]['tmp_name'], $saveAs);
				if ($copyResults !== true) {
					$result = array('return' => false, 'reason' => 'validation', 'extra' => array('field' => $field, 'error' => $copyResults));
					continue;
				}

				// If the file is an image, try to make the thumbnails
				if ((count($options['thumbsizes']) > 0) && count($options['allowedExt']) > 0 && in_array($data[$model->alias][$fieldName]['type'], $this->_imageTypes)) {
					$this->_createThumbnails($model, $data, $fieldName, $saveAs, $ext, $options);
				}

				$data = $this->_unsetDataFields($model->alias, $fieldName, $model->data, $options);
				$result = array('return' => true, 'data' => $data);
				continue;
			} else {
				// if the file is marked to be deleted, use the default or set the field to null
				if (!empty($data[$model->alias][$fieldName]['remove'])) {
					if ($options['default']) {
						$data[$model->alias][$fieldName] = $options['default'];
					} else {
						$data[$model->alias][$fieldName] = null;
					}
					//if the record is already saved in the database, set the existing file to be removed after the save is sucessfull
					if (!empty($data[$model->alias][$model->primaryKey])) {
						$this->_setFileToRemove($model, $fieldName);
					}
				}

				// If no file has been upload, then unset the field to avoid overwriting existant file
				if (!isset($data[$model->alias][$fieldName]) || !is_array($data[$model->alias][$fieldName]) || empty($data[$model->alias][$fieldName]['name'])) {
					if (!empty($data[$model->alias][$model->primaryKey]) || !$options['default']) {
						unset($data[$model->alias][$fieldName]);
					} else {
						$data[$model->alias][$fieldName] = $options['default'];
					}
				}

				//if the record is already saved in the database, set the existing file to be removed after the save is sucessfull
				if (!empty($data[$model->alias][$model->primaryKey])) {
					$this->_setFileToRemove($model, $fieldName);
				}

				// Fix the filename, removing bad characters and avoiding from overwriting existing ones
				if ($options['default'] == true) {
					$this->_includeDefaultReplacement($options['default']);
				}
				$this->_fixName($model, $fieldName);
				// Also save the original image as uploadName if that option is not empty
				if (isset($options['uploadName']) && !empty($options['uploadName'])) {
					$saveAs = $options['dir'] . DS . $data[$model->alias][$options['uploadName']].'.'.$ext;
				} else {
					$saveAs = $options['dir'] . DS . $data[$model->alias][$fieldName]['name'];
				}

				// Attempt to move uploaded file
				$copyResults = $this->_copyFileFromTemp($data[$model->alias][$fieldName]['tmp_name'], $saveAs);
				if ($copyResults !== true) {
					$result = array('return' => false, 'reason' => 'validation', 'extra' => array('field' => $field, 'error' => $copyResults));
					continue;
				}

				// If the file is an image, try to make the thumbnails
				if ((count($options['thumbsizes']) > 0) && count($options['allowedExt']) > 0 && in_array($data[$model->alias][$fieldName]['type'], $this->_imageTypes)) {
					$this->_createThumbnails($model, $data, $fieldName, $saveAs, $ext, $options);
				}

				// Update model data
				$data[$model->alias][$options['fields']['dir']] = $options['dir'];
				$data[$model->alias][$options['fields']['mimetype']] = $data[$model->alias][$fieldName]['type'];
				$data[$model->alias][$options['fields']['filesize']] = $data[$model->alias][$fieldName]['size'];
				if (isset($options['uploadName']) && !empty($options['uploadName'])) {
					$data[$model->alias][$fieldName] = $data[$model->alias][$options['uploadName']].'.'.$ext;
				} else {
					$data[$model->alias][$fieldName] = $data[$model->alias][$fieldName]['name'];
				}
				$result = array('return' => true, 'data' => $data);
				continue;
			}
		}
		if (isset($result)) {
			return $result;
		} else {
			return true;
		}
	}

/**
 * Create all the thumbnails
 *
 * @return void
 * @author Jose Diaz-Gonzalez
 **/
	function _createThumbnails(&$model, $data, $fieldName, $saveAs, $ext, $options) {
		foreach ($options['thumbsizes'] as $key => $value) {
			// Generate the name for the thumbnail
			if (isset($options['uploadName']) && !empty($options['uploadName'])) {
				$thumbSaveAs = $this->_getThumbnailName($saveAs, $options['dir'], $key, $data[$model->alias][$options['uploadName']], $ext);
			} else {
				$thumbSaveAs = $this->_getThumbnailName($saveAs, $options['dir'], $key, $data[$model->alias][$fieldName]['name']);
			}
			$params = array();
			if (isset($value['width'])) {
				$params['thumbWidth'] = $value['width'];
			}
			if (isset($value['height'])) {
				$params['thumbHeight'] = $value['height'];
			}
			if (isset($value['maxDimension'])) {
				$params['maxDimension'] = $value['maxDimension'];
			}
			if (isset($value['thumbnailQuality'])) {
				$params['thumbnailQuality'] = $value['thumbnailQuality'];
			}
			if (isset($value['zoomCrop'])) {
				$params['zoomCrop'] = $value['zoomCrop'];
			}
			$this->_createThumbnail($model, $saveAs, $thumbSaveAs, $fieldName, $params);
		}
	}

/**
 * Function to create Thumbnail images
 *
 * @author Jose Diaz-Gonzalez
 * @param String source file name (without path)
 * @param String target file name (without path)
 * @param String path to source and destination (no trailing DS)
 * @param Array
 * @return void
 */
	function _createThumbnail(&$model, $source, $target, $fieldName, $params = array()) {
		$params = array_merge(
			array(
				'thumbWidth' => 150,
				'thumbHeight' => 225,
				'maxDimension' => '',
				'thumbnailQuality' => $this->__fields[$model->alias][$fieldName]['thumbnailQuality'],
				'zoomCrop' => false
			),
			$params);

		// Import phpThumb class
		App::import('Vendor','phpthumb', array('file' => 'phpThumb'.DS.'phpthumb.class.php'));

		// Configuring thumbnail settings
		$phpThumb = new phpthumb;
		$phpThumb->setSourceFilename($source);

		if ($params['maxDimension'] == 'w') {
			$phpThumb->w = $params['thumbWidth'];
		} else if ($params['maxDimension'] == 'h') {
			$phpThumb->h = $params['thumbHeight'];
		} else {
			$phpThumb->w = $params['thumbWidth'];
			$phpThumb->h = $params['thumbHeight'];
		}

		$phpThumb->setParameter('zc', $this->__fields[$model->alias][$fieldName]['zoomCrop']);
		if (isset($params['zoomCrop'])){
			$phpThumb->setParameter('zc', $params['zoomCrop']);
		}
		$phpThumb->q = $params['thumbnailQuality'];

		$imageArray = explode(".", $source);
		$phpThumb->config_output_format = $imageArray[1];
		unset($imageArray);

		$phpThumb->config_prefer_imagemagick = $this->__fields[$model->alias][$fieldName]['useImageMagick'];
		$phpThumb->config_imagemagick_path = $this->__fields[$model->alias][$fieldName]['imageMagickPath'];

		// Setting whether to die upon error
		$phpThumb->config_error_die_on_error = true;
		// Creating thumbnail
		if ($phpThumb->GenerateThumbnail()) {
			if (!$phpThumb->RenderToFile($target)) {
				$this->_addError('Could not render image to: '.$target);
			}
		}
	}

/**
 * Merges two arrays recursively
 * primeminister / 2009-11-13 : Added fix for numeric arrays like allowedMime and allowedExt.
 * These values will remain intact even if the passed options were shorter.
 * Solved that with array_splice to keep intact the previous indexes (already merged)
 *
 * @param $arr Array
 * @param $ins Array
 * @return array
 * @author Vinicius Mendes
 */
	function _arrayMerge($arr, $ins) {
		if (is_array($arr)) {
			if (is_array($ins)) {
				foreach ($ins as $k => $v) {
					if (isset($arr[$k]) && is_array($v) && is_array($arr[$k])) {
						$arr[$k] = $this->_arrayMerge($arr[$k], $v);
					} elseif (is_numeric($k)) {
						array_splice($arr, $k, count($arr));
						$arr[$k] = $v;
					} else {
						$arr[$k] = $v;
					}
				}
			}
		} elseif (!is_array($arr) && (strlen($arr) == 0 || $arr == 0)) {
			$arr = $ins;
		}
		return $arr;
	}

/**
 * Replaces some tokens. {ModelName} to the underscore version of the model name
 * {fieldName} to the field name, {DS}. / or \ to DS constant value.
 *
 * @param $string String
 * @param $fieldName String
 * @return string
 * @author Vinicius Mendes
 */
	function _replaceTokens(&$model, $string, $fieldName, $tokens = array()) {
		return str_replace(
			$tokens,
			array(Inflector::underscore($model->name), $fieldName, DS, DS, DS),
			$string
		);
	}

/**
 * Removes the bad characters from the $filename and replace reserved words. It updates the $model->data.
 *
 * @param $fieldName String
 * @return void
 * @author Vinicius Mendes
 */
	function _fixName(&$model, $fieldName, $checkFile = true) {
		// updates the filename removing the keywords thumb and default name for the field.
		list ($filename, $ext) = $this->_splitFilenameAndExt($model->data[$model->alias][$fieldName]['name']);
		$filename = str_replace($this->patterns, $this->replacements, $filename);
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
 * Include a pattern of reserved word based on a filename, and it's replacement.
 *
 * @param $default String
 * @return void
 * @author Vinicius Mendes
 */
	function _includeDefaultReplacement($default) {
		$replacements = $this->replacements;
		list ($newPattern, $ext) = $this->_splitFilenameAndExt($default);
		if (!in_array($newPattern, $this->patterns)) {
			$this->patterns[] = $newPattern;
			$newReplacement = $newPattern;
			if (isset($newReplacement[1])) {
				if ($newReplacement[1] != '_') {
					$newReplacement[1] = '_';
				} else {
					$newReplacement[1] = 'a';
				}
			} elseif ($newReplacement != '_') {
				$newReplacement = '_';
			} else {
				$newReplacement = 'a';
			}
			$this->replacements[] = $newReplacement;
		}
	}

/**
 * Splits a filename in two parts: the name and the extension. Returns an array with it respectively.
 *
 * @param $filename String
 * @return array
 * @author Juan Basso
 */
	function _splitFilenameAndExt($filename) {
		extract(pathinfo($filename));
		if (!isset($filename)) {
			$filename = substr($basename, 0, -1 - count($extension)); // Remove extension and .
		}
		return array($filename, $extension);
	}

/**
 * Generate the name for the thumbnail
 * If a 'normal' thumbnail is set, then it will overwrite the original file
 *
 * @param $saveAs String name for original file
 * @param $dir String directory for all uploads
 * @param $key String thumbnail size
 * @param $fieldToSaveAs String field in model to save as
 * @param $sub String substring to append to directory for naming
 * @return string
 * @author Jose Diaz-Gonzalez
 **/
	function _getThumbnailName($saveAs, $dir, $key, $fieldToSaveAs, $sub = null) {
		if ($key == 'normal') {
			return $saveAs;
		}
		// Otherwise, set the thumb filename to thumb.$key.$filename.$ext
		$result = $dir . DS . 'thumb' . DS . $key . DS . $fieldToSaveAs;
		if (isset($sub)) {
			return $result . '.' . $sub;
		}
		return $result;
	}

/**
 * Convert a size value to bytes. For example: 2 MB to 2097152.
 *
 * @param $size String
 * @return int
 * @author Vinicius Mendes
 */
	function _sizeToBytes($size) {
		if (is_numeric($size)) {
			return $size;
		}
		if (!preg_match('/^([1-9][0-9]*) (kb|mb|gb|tb)$/i', $size, $matches)) {
			trigger_error(__d('meio_upload', 'MeioUploadBehavior Error: The max_size option format is invalid.', true), E_USER_ERROR);
			return 0;
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
			default:
				trigger_error(__d('meio_upload', 'MeioUploadBehavior Error: The max_size unit is invalid.', true), E_USER_ERROR);
		}
		return 0;
	}

/**
 * Sets the validation for each field, based on the options.
 *
 * @param $fieldName String
 * @param $options Array
 * @return void
 * @author Vinicius Mendes
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
		$model->validate[$fieldName] = $this->_arrayMerge($this->defaultValidations, $model->validate[$fieldName]);
		$model->validate[$fieldName] = $this->_arrayMerge($options['validations'], $model->validate[$fieldName]);
	}


/**
 * Creates thumbnail folders if they do not already exist
 *
 * @param $dir string Path to uploads
 * @param $key string Name for particular thumbnail type
 * @return void
 * @author Jose Diaz-Gonzalez
 **/
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
		foreach ($thumbsizes as $thumbsize) {
			if ($thumbsize != 'normal' && !$folder->cd($dir . DS .'thumb' . DS . $thumbsize)) {
				$folder->create($dir . DS . 'thumb' . DS . $thumbsize);
			}
		}
	}

/**
 * Copies file from temporary directory to final destination
 *
 * @param $tmpName string full path to temporary file
 * @param $saveAs string full path to move the file to
 * @return mixed true is successful, error message if not
 * @author Jose Diaz-Gonzalez
 **/
	function _copyFileFromTemp($tmpName, $saveAs) {
		$results = true;
		if (!is_uploaded_file($tmpName)) {
			return false;
		}
		$file = new File($tmpName, $saveAs);
		$temp = new File($saveAs, true);
		if (!$temp->write($file->read())) {
			$results = __d('meio_upload', 'Problems in the copy of the file.', true);
		}
		$file->close();
		$temp->close();
		return $results;
	}

/**
 * Set a file to be removed in afterSave() callback
 *
 * @param $fieldName String
 * @return void
 * @author Vinicius Mendes
 */
	function _setFileToRemove(&$model, $fieldName) {
		$filename = $model->field($fieldName);
		if (!empty($filename) && $filename != $this->__fields[$model->alias][$fieldName]['default']) {
			$this->__filesToRemove[] = array(
				'field' => $fieldName,
				'dir' => $this->__fields[$model->alias][$fieldName]['dir'],
				'name' => $filename
			);
			foreach($this->__fields[$model->alias][$fieldName]['thumbsizes'] as $key => $sizes){
				if ($key === 'normal') {
					$subpath = '';
				} else {
					$subpath = DS . 'thumb' . DS . $key;
				}
				$this->__filesToRemove[] = array(
					'field' => $fieldName,
					'dir' => $this->__fields[$model->alias][$fieldName]['dir'] . $subpath,
					'name' => $filename
				);
			}
		}
	}

/**
 * Marks files for deletion in the beforeSave() callback
 *
 * @param $model Reference to model
 * @param $fieldName string name of field that holds a reference to the file
 * @param $data array
 * @param $default
 * @return void
 * @author Jose Diaz-Gonzalez
 **/
	function _markForDeletion(&$model, $fieldName, $data, $default) {
		if (!empty($data[$model->alias][$fieldName]['remove'])) {
			if ($default) {
				$data[$model->alias][$fieldName] = $default;
			} else {
				$data[$model->alias][$fieldName] = '';
			}
			//if the record is already saved in the database, set the existing file to be removed after the save is sucessfull
			if (!empty($data[$model->alias][$model->primaryKey])) {
				$this->_setFileToRemove($model, $fieldName);
			}
		}
	}

/**
 * Delete the $filename inside the $dir and the thumbnails.
 * Returns true if the file is deleted and false otherwise.
 *
 * @param $filename Object
 * @param $dir Object
 * @return boolean
 * @author Vinicius Mendes
 */
	function _deleteFiles(&$model, $field, $filename, $dir) {
		$saveAs = $dir . DS . $filename;
		if (is_file($saveAs) && !unlink($saveAs)) {
			return false;
		}
		foreach ($this->__fields[$model->alias][$field]['thumbsizes'] as $size => &$config) {
			$file =& new File($dir . DS . $size . DS . $filename);
			$file->delete();
		}
		return true;
	}

/**
 * Unsets data from $data
 * Useful for no-db upload
 *
 * @param $modelName string name of the Model
 * @param $fieldName string name of field that holds a reference to the file
 * @param $data array
 * @param $options array
 * @return array
 * @author Jose Diaz-Gonzalez
 **/
	function _unsetDataFields($modelName, $fieldName, $data, $options) {
		unset($data[$modelName][$fieldName]);
		unset($data[$modelName][$options['fields']['dir']]);
		unset($data[$modelName][$options['fields']['filesize']]);
		unset($data[$modelName][$options['fields']['mimetype']]);
		return $data;
	}

/**
 * Adds an error, legacy from the component
 *
 * @param $msg string error message
 * @return void
 * @author Jose Diaz-Gonzalez
 **/
	function _addError($msg) {
		$this->errors[] = $msg;
	}
}
?>