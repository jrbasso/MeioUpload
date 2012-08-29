<?php
/**
 * MeioUpload Behavior
 *
 * This behavior is based on Vincius Mendes'  MeioUpload Behavior
 *  (http://www.meiocodigo.com/projects/meioupload/)
 * Which is in turn based upon Tane Piper's improved upload behavior
 *  (http://digitalspaghetti.tooum.net/switchboard/blog/2497:Upload_Behavior_for_CakePHP_12)
 *
 * @author Jose Diaz-Gonzalez (support@savant.be)
 * @author Juan Basso (jrbasso@gmail.com)
 * @author Vinicius Mendes (vbmendes@gmail.com)
 * @package meio_upload
 * @subpackage meio_upload.models.behaviors
 * @filesource http://github.com/jrbasso/MeioUpload
 * @version 2.2
 */

App::uses('File', 'Utility');
App::uses('Folder', 'Utility');

/**
 * MeioUpload Behavior
 *
 */
class MeioUploadBehavior extends ModelBehavior {
/**
 * The default options for the behavior
 *
 * @var array
 * @access public
 */
	var $defaultOptions = array(
		'useTable' => true,
		'createDirectory' => true,
		'encryptedFolder' => false,
		'dir' => 'uploads{DS}{ModelName}{DS}{fieldName}',
		'folderPermission' => 0755, // Set permission of dynamically created folder
		'folderAsField' => null, // Can be the name of any field in $this->data
		'uploadName' => null, // Can also be the tokens {ModelName} or {fieldName}
		'filePermission' => 0755, // Set permission of uploaded files in the server
		'removeOriginal' => false,
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
		'thumbnailDir' => 'thumb',
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
 * @access public
 */
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
		'HttpPost' => array(
			'rule' => array('uploadCheckHttpPost'),
			'check' => true,
			'last' => true
		),
	);

/**
 * The array that saves the $options for the behavior
 *
 * @var array
 * @access private
 */
	var $__fields = array();

/**
 * Patterns of reserved words
 *
 * @var array
 * @access public
 */
	var $patterns = array(
		'thumb',
		'default'
	);

/**
 * Words to replace the patterns of reserved words
 *
 * @var array
 * @access public
 */
	var $replacements = array(
		't_umb',
		'd_fault'
	);

/**
 * Array of files to be removed on the afterSave callback
 *
 * @var array
 * @access private
 */
	var $__filesToRemove = array();

/**
 * Array of all possible images that can be converted to thumbnails
 *
 * @var array
 * @access protected
 */
	var $_imageTypes = array('image/jpeg', 'image/pjpeg', 'image/png', 'image/gif', 'image/bmp', 'image/x-icon', 'image/vnd.microsoft.icon');

/**
 * Constructor
 *
 */
	function __construct() {
		$messages = array(
			'FieldName' => array(
				'message' => __d('meio_upload', 'This field has not been defined between the parameters of MeioUploadBehavior.')
			),
			'Dir' => array(
				'message' => __d('meio_upload', 'The directory where the file would be placed there or is protected against writing.')
			),
			'Empty' => array(
				'message' => __d('meio_upload', 'The file can not be empty.')
			),
			'UploadError' => array(
				'message' => __d('meio_upload', 'There were problems in uploading the file.')
			),
			'MaxSize' => array(
				'message' => __d('meio_upload', 'The maximum file size is exceeded.')
			),
			'InvalidMime' => array(
				'message' => __d('meio_upload', 'Invalid file type.')
			),
			'InvalidExt' => array(
				'message' => __d('meio_upload', 'Invalid file extension.')
			),
			'MinWidth' => array(
				'message' => __d('meio_upload', 'Image width is smaller than minimum allowed.')
			),
			'MinHeight' => array(
				'message' => __d('meio_upload', 'Image height is smaller than minimum allowed.')
			),
			'MaxWidth' => array(
				'message' => __d('meio_upload', 'Image width is larger than maximum allowed.')
			),
			'MaxHeight' => array(
				'message' => __d('meio_upload', 'Image height is larger than maximum allowed.')
			),
			'HttpPost' => array(
				'message' => __d('meio_upload', 'The uploaded file did not use http POST. Suspected security issue.')
			)
		);
		$this->defaultValidations = $this->_arrayMerge($this->defaultValidations, $messages);
		$this->defaultOptions['validations'] = $this->defaultValidations;
	}

/**
 * Setup the behavior. It stores a reference to the model, merges the default options with the options for each field, and setup the validation rules.
 *
 * @param object $model Reference to model
 * @param array $settings Settings (optional)
 * @return void
 * @access public
 */
	function setup(Model $model, $settings = array()) {
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
				trigger_error(__d('meio_upload', 'MeioUploadBehavior Error: The field "%s" doesn\'t exists in the model "%s".', $field, $model->alias), E_USER_WARNING);
			}

			// Including the default name to the replacements
			if ($options['default']) {
				if (strpos($options['default'], '.') === false) {
					trigger_error(__d('meio_upload', 'MeioUploadBehavior Error: The default option must be the filename with extension.'), E_USER_ERROR);
				}
				$this->_includeDefaultReplacement($options['default']);
			}

			// Verifies if the thumbsizes names is alphanumeric
			if ($options['thumbnails'] == true) {
				foreach ($options['thumbsizes'] as $name => $size) {
					if (empty($name) || !ctype_alnum($name)) {
						trigger_error(__d('meio_upload', 'MeioUploadBehavior Error: The thumbsizes names must be alphanumeric.'), E_USER_ERROR);
					}
				}
			}

			// Process the max_size if it is not numeric
			$options['maxSize'] = $this->_sizeToBytes($options['maxSize']);

			// Replace tokens of the dir and field, check it doesn't have a DS on the end
			$tokens = array('{ModelName}', '{fieldName}', '{DS}', '/', '\\');
			$options['dir'] = rtrim($this->_replaceTokens($model, $options['dir'], $field, $tokens), DS);
			$options['uploadName'] = rtrim($this->_replaceTokens($model, $options['uploadName'], $field, $tokens), DS);

			// Create the folders for the uploads only if you want
			if($options['createDirectory']) {
				// Create the folders for the uploads
				if (!empty($options['thumbsizes'])) {
					$this->_createFolders($options['dir'], $options['thumbnailDir'], array_keys($options['thumbsizes']), $options['folderPermission']);
				} else {
					$this->_createFolders($options['dir'], $options['thumbnailDir'], array(), $options['folderPermission']);
				}
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
 * @param object $model Reference to model
 * @return boolean Always true
 * @access public
 */
	function beforeValidate(Model $model) {
		foreach ($this->__fields[$model->alias] as $fieldName => $options) {
			$this->_setupValidation($model, $fieldName, $options);
		}
		return true;
	}

/**
 * Initializes the upload
 *
 * @param object $model Reference to model
 * @return boolean Whether the upload completed
 * @access public
 */
	function beforeSave(Model $model) {
		return $this->upload($model, null);
	}

/**
 * Deletes the files marked to be deleted in the save method.
 * A file can be marked to be deleted if it is overwriten by
 * another or if the user mark it to be deleted.
 *
 * @param object $model Reference to model
 * @return void
 * @access protected
 */
	function _deleteFilesList(&$model) {
		foreach ($this->__filesToRemove as $file) {
			if (!empty($file['name'])) {
				$this->_deleteFiles($model, $file['field'], $file['name'], $file['dir']);
			}
		}
		// Reset the filesToRemove array
		$this->__filesToRemove = array();
	}

/**
 * After save (callback)
 *
 * @param object $model Reference to model
 * @return void
 * @access public
 */
	function afterSave(Model $model, $created) {
		$this->_deleteFilesList($model);
	}

/**
 * After delete (callback)
 *
 * @param object $model Reference to model
 * @return void
 * @access public
 */
	function afterDelete(Model $model) {
		$this->_deleteFilesList($model);
	}
/**
 * Performs a manual upload
 *
 * @param object $model Reference to model
 * @param array $data Array data to be saved
 * @return boolean Whether the upload completed
 * @access public
 */
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
 * @param object $model Reference to model
 * @return boolean Always true
 * @access public
 */
	function beforeDelete(Model $model, $cascade = true) {
		$model->read(null, $model->id);
		if (isset($model->data)) {
			foreach ($this->__fields[$model->alias] as $field => $options) {
				$this->_setFileToRemove($model, $field, $options['thumbnailDir']);
			}
		}
		return true;
	}

/**
 * Checks if the field was declared in the MeioUpload Behavior setup
 *
 * @param object $model Reference to model
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
				$this->log(__d('meio_upload', 'MeioUploadBehavior Error: The field "%s" wasn\'t declared as part of the MeioUploadBehavior in model "%s".', $fieldName, $model->alias));
				return false;
			}
		}
		return true;
	}

/**
 * Checks if the folder exists or can be created or writable.
 *
 * @param object $model Reference to model
 * @param array $data
 * @return boolean
 * @access public
 */
	function uploadCheckDir(&$model, $data) {
		/**
		 * when running unit tests for model, the current working directory is not necessarily WWW_ROOT
		 * the code is_dir assumed that the current working directory is WWW_ROOT
		 * if current working directory is NOT WWW_ROOT, we need to change it to WWW_ROOT (temporarily??)
		 */
		$currentWorkingDirectory = getcwd();
		if ($currentWorkingDirectory != WWW_ROOT) {
			chdir(WWW_ROOT);
		}
		
		foreach ($data as $fieldName => $field) {
			if (!$model->validate[$fieldName]['Dir']['check']) {
				continue;
			}
			$options = $this->__fields[$model->alias][$fieldName];
			if (empty($field['remove']) || empty($field['name'])) {
				// Check if directory exists and create it if required
				if (!is_dir($options['dir'])) {
					if ($options['createDirectory']) {
						$folder = &new Folder();
						if (!$folder->create($options['dir'], $options['folderPermission'])) {
							trigger_error(__d('meio_upload', 'MeioUploadBehavior Error: The directory %s does not exist and cannot be created.', $options['dir']), E_USER_WARNING);
							return false;
						}
					} else {
						trigger_error(__d('meio_upload', 'MeioUploadBehavior Error: The directory %s does not exist.', $options['dir']), E_USER_WARNING);
						return false;
					}
				}

				// Check if directory is writable
				if (!is_writable($options['dir'])) {
					trigger_error(__d('meio_upload', 'MeioUploadBehavior Error: The directory %s isn\'t writable.', $options['dir']), E_USER_WARNING);
					return false;
				}
			}
		}
		return true;
	}

/**
 * Checks if the filename is not empty.
 *
 * @param object $model Reference to model
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
 * @param object $model Reference to model
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
 * @param object $model Reference to model
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
 * @param object $model Reference to model
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
			if (!empty($field['name']) && count($options['allowedMime']) > 0 && !in_array($field['type'], $options['allowedMime'])) {
				$info = @getimagesize($field['tmp_name']);
				if ($info !== false && in_array($info['mime'], $options['allowedMime'])) {
					continue;
				}
				if (function_exists('finfo_open')) {
					$finfo = finfo_open(FILEINFO_MIME_TYPE);
					$info = finfo_file($finfo, $field['tmp_name']);
					if ($info !== false && in_array($info, $options['allowedMime'])) {
						continue;
					}
				}
				if (function_exists('mime_content_type')) { // @deprecated
					$info = mime_content_type($field['tmp_name']);
					if ($info !== false && in_array($info, $options['allowedMime'])) {
						continue;
					}
				}
				return false;
			}
		}
		return true;
	}

/**
 * Checks if the file has an allowed extension.
 *
 * @param object $model Reference to model
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
 * @param object $model Reference to model
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
 * @param object $model Reference to model
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
 * @param object $model Reference to model
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
 * @param object $model Reference to model
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
 * @param object $model Reference to model
 * @param array $data Reference to data
 * @param string $type Type of check: minHeight, minWidth, maxHeight, maxWidth
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
 * Checks if the file is uploaded via HTTP POST
 *
 * @param object $model Reference to model
 * @param array $data
 * @return boolean
 * @access public
 */
	function uploadCheckHttpPost(&$model, $data) {
		
		foreach ($data as $fieldName => $field) {
			if (!$model->validate[$fieldName]['HttpPost']['check']) {
				continue;
			}

			if (!empty($field['tmp_name'])) {
				return is_uploaded_file($field['tmp_name']);
			}
		}
		return true;
	}

/**
 * Uploads the files
 *
 * @param object $model Reference to model
 * @param array $data Optional Containing data to be saved
 * @return mixed
 * @access protected
 */
	function _uploadFile(&$model, $data = null) {
		if (!isset($data) || !is_array($data)) {
			$data =& $model->data;
		}
		foreach ($this->__fields[$model->alias] as $fieldName => $options) {
			// Take care of removal flagged field
			// However, this seems to be kind of code duplicating, see line ~711
			if (!empty($data[$model->alias][$fieldName]['remove'])) {
				$this->_markForDeletion($model, $fieldName, $data, $options['default'], $options['thumbnailDir']);
				$data = $this->_nullifyDataFields($model, $fieldName, $data, $options);
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
				$copyResults = $this->_copyFileFromTemp($data[$model->alias][$fieldName]['tmp_name'], $saveAs, $options['filePermission']);
				if ($copyResults !== true) {
					$result = array('return' => false, 'reason' => 'validation', 'extra' => array('field' => $fieldName, 'error' => $copyResults));
					continue;
				}

				// If the file is an image, try to make the thumbnails
				if ((count($options['thumbsizes']) > 0) && count($options['allowedExt']) > 0 && in_array($data[$model->alias][$fieldName]['type'], $this->_imageTypes)) {
					$this->_createThumbnails($model, $data, $fieldName, $saveAs, $ext, $options);
				}
				if ($options['removeOriginal']) {
					$this->_removeOriginal($saveAs);
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
						$this->_setFileToRemove($model, $fieldName, $options['thumbnailDir']);
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
					$this->_setFileToRemove($model, $fieldName, $options['thumbnailDir']);
				}

				// save in encrypted folder if specified
				if ($options['encryptedFolder']) {
					// setup UUID as a unique folder name
					$options['dir'] .= DS . String::uuid();
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
				$copyResults = $this->_copyFileFromTemp($data[$model->alias][$fieldName]['tmp_name'], $saveAs, $options['filePermission']);
				if ($copyResults !== true) {
					$result = array('return' => false, 'reason' => 'validation', 'extra' => array('field' => $fieldName, 'error' => $copyResults));
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
 * Regenerate all the thumbnails, for all fields that have thumbSizes, for records in the table. (used, eg, when you want to add or change thumbnail sizes)
 * This will overwrite existing thumbnails, when they exist, so a backup of thumbnails should be done before hand.
 * This will not delete any redundant thumbnails - redundant thumbnails can be deleted manually.
 * From your controller, call this method like: $this->MyModel->regenerateThumbnails();
 *
 * @param object $model Reference to model
 * @access public
 */
	function regenerateThumbnails(Model $model) {
		$allRows = $model->find('all');
		
		foreach($allRows as $data){

			// Loop through each Model field passed in MeioUpload's options via the $actsAs variable of the Model
			foreach($this->__fields[$model->alias] as $fieldName => $options){

				// don't try to regenerate thumbnails if the field is empty
				if(!empty($data[$model->alias][$fieldName])){
					
					// set 'name' key, because _createThumbnails methods expects it to exist (it exists when the file is uploaded - but here the file isn't uploaded, so we just fake it).
					$data[$model->alias][$fieldName] = array('name' => $data[$model->alias][$fieldName]);
					
					$saveAs = $options['dir'] . DS . $data[$model->alias][$fieldName]['name'];
					list(,$ext) = $this->_splitFilenameAndExt($data[$model->alias][$fieldName]['name']);
					$this->_createThumbnails($model, $data, $fieldName, $saveAs, $ext, $options);
				}
			}
		}
	}

/**
 * Create all the thumbnails
 *
 * @param object $model Reference to model
 * @param array $data
 * @param string $fieldName
 * @param string $ext
 * @param array $options
 * @return void
 * @access protected
 */
	function _createThumbnails(&$model, $data, $fieldName, $saveAs, $ext, $options) {
		foreach ($options['thumbsizes'] as $key => $value) {
			// Generate the name for the thumbnail
			if (isset($options['uploadName']) && !empty($options['uploadName'])) {
				$thumbSaveAs = $this->_getThumbnailName($saveAs, $options['dir'], $options['thumbnailDir'], $key, $data[$model->alias][$options['uploadName']], $ext);
			} else {
				$thumbSaveAs = $this->_getThumbnailName($saveAs, $options['dir'], $options['thumbnailDir'], $key, $data[$model->alias][$fieldName]['name']);
			}

			// Make the thumbnail reference absolute to avoid problems with phpThumb in CakePHP 2.0
			$thumbSaveAs = WWW_ROOT . $thumbSaveAs;

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
			if (isset($value['watermark'])) {
				$params['watermark'] = $value['watermark'];
			}
			$this->_createThumbnail($model, $saveAs, $thumbSaveAs, $fieldName, $params);
		}
	}

/**
 * Function to create Thumbnail images
 *
 * @param object $model Reference to model
 * @param string $source File name (without path)
 * @param string $target File name (without path)
 * @param string $fieldName
 * @param array $params
 * @return void
 * @access protected
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
		$test = App::import('Vendor','phpthumb', array('file' => 'phpThumb' . DS . 'phpthumb.class.php'));

		// Configuring thumbnail settings
		$phpThumb = new phpthumb;
		$phpThumb->setSourceFilename($source);
		$phpThumb->config_disable_debug = !Configure::read('debug');

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
		if (isset($params['watermark'])){
			$phpThumb->fltr = array("wmi|". IMAGES . $params['watermark']."|BR|50|5");
		}
		$phpThumb->q = $params['thumbnailQuality'];

		$imageArray = explode(".", $source);
		$phpThumb->config_output_format = end($imageArray);
		unset($imageArray);

		$phpThumb->config_prefer_imagemagick = $this->__fields[$model->alias][$fieldName]['useImageMagick'];
		$phpThumb->config_imagemagick_path = $this->__fields[$model->alias][$fieldName]['imageMagickPath'];

		// Setting whether to die upon error
		$phpThumb->config_error_die_on_error = true;
		// Creating thumbnail
		if ($phpThumb->GenerateThumbnail()) {
			if (!$phpThumb->RenderToFile($target)) {
				$this->_addError('Could not render image to: ' . $target);
			}
		}
	}

/**
 * Merges two arrays recursively
 *
 * These values will remain intact even if the passed options were shorter.
 * Solved that with array_splice to keep intact the previous indexes (already merged)
 *
 * @param array $arr
 * @param array $ins
 * @return array
 * @access protected
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
 * @param object $model Reference to model
 * @param string $string
 * @param string $fieldName
 * @param array $tokens
 * @return string
 * @access protected
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
 * @param object $model Reference to model
 * @param string $fieldName
 * @param boolean $checkFile
 * @return void
 * @access protected
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
 * @param string $default
 * @return void
 * @access protected
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
 * Generate the name for the thumbnail
 * If a 'normal' thumbnail is set, then it will overwrite the original file
 *
 * @param string $saveAs name for original file
 * @param string $dir directory for all uploads
 * @param string $thumbDir Path to thumbnails
 * @param string $key thumbnail size
 * @param string $fieldToSaveAs field in model to save as
 * @param string $sub substring to append to directory for naming
 * @return string
 * @access protected
 */
	function _getThumbnailName($saveAs, $dir, $thumbDir, $key, $fieldToSaveAs, $sub = null) {
		if ($key == 'normal') {
			return $saveAs;
		}
		// Otherwise, set the thumb filename to thumb.$key.$filename.$ext
		$result = $dir . DS . $thumbDir . DS . $key . DS . $fieldToSaveAs;
		if (isset($sub)) {
			return $result . '.' . $sub;
		}
		return $result;
	}

/**
 * Convert a size value to bytes. For example: 2 MB to 2097152.
 *
 * @param string $size
 * @return integer
 * @access protected
 */
	function _sizeToBytes($size) {
		if (is_numeric($size)) {
			return $size;
		}
		if (!preg_match('/^([1-9][0-9]*) (kb|mb|gb|tb)$/i', $size, $matches)) {
			trigger_error(__d('meio_upload', 'MeioUploadBehavior Error: The max_size option format is invalid.'), E_USER_ERROR);
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
				trigger_error(__d('meio_upload', 'MeioUploadBehavior Error: The max_size unit is invalid.'), E_USER_ERROR);
		}
		return 0;
	}

/**
 * Sets the validation for each field, based on the options.
 *
 * @param object $model Reference to model
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
		$model->validate[$fieldName] = $this->_arrayMerge($this->defaultValidations, $model->validate[$fieldName]);
		$model->validate[$fieldName] = $this->_arrayMerge($options['validations'], $model->validate[$fieldName]);
	}


/**
 * Creates thumbnail folders if they do not already exist
 *
 * @param string $dir Path to uploads
 * @param string $thumbDir Path to thumbnails
 * @param array $thumbsizes
 * @param integer $folderPermission octal value of created folder permission
 * @return void
 * @access protected
 */
	function _createFolders($dir, $thumbDir, $thumbsizes, $folderPermission) {
		if ($dir[0] !== '/') {
			$dir = WWW_ROOT . $dir;
		}
		$folder = new Folder();

		if (!$folder->cd($dir)) {
			$folder->create($dir, $folderPermission);
		}
		if (!$folder->cd($dir. DS . $thumbDir)) {
			$folder->create($dir . DS . $thumbDir, $folderPermission);
		}
		foreach ($thumbsizes as $thumbsize) {
			if ($thumbsize != 'normal' && !$folder->cd($dir . DS . $thumbDir . DS . $thumbsize)) {
				$folder->create($dir . DS . $thumbDir . DS . $thumbsize, $folderPermission);
			}
		}
	}

/**
 * Copies file from temporary directory to final destination
 *
 * @param string $tmpName full path to temporary file
 * @param string $saveAs full path to move the file to
 * @param integer $filePermission octal value of created file permission
 * @return mixed true is successful, error message if not
 * @access protected
 */
	function _copyFileFromTemp($tmpName, $saveAs, $filePermission) {
		$results = true;
		
		$file = new File($tmpName, $saveAs);
		$temp = new File($saveAs, true, $filePermission);
		if (!$temp->write($file->read())) {
			$results = __d('meio_upload', 'Problems in the copy of the file.');
		}
		$file->close();
		$temp->close();
		return $results;
	}

/**
 * Set a file to be removed in afterSave() callback
 *
 * @param object $model Reference to model
 * @param sting $fieldName
 * @param string $thumbDir Path to thumbnails
 * @return void
 * @access protected
 */
	function _setFileToRemove(&$model, $fieldName, $thumbDir) {
		$filename = $model->field($fieldName);
		if (!empty($filename) && $filename != $this->__fields[$model->alias][$fieldName]['default']) {
			$this->__filesToRemove[] = array(
				'field' => $fieldName,
				'dir' => $this->__fields[$model->alias][$fieldName]['dir'],
				'name' => $filename
			);
			if ($this->__fields[$model->alias][$fieldName]['thumbnails'] && !empty($this->__fields[$model->alias][$fieldName]['thumbsizes'])) {
				foreach($this->__fields[$model->alias][$fieldName]['thumbsizes'] as $key => $sizes){
					if ($key === 'normal') {
						$subpath = '';
					} else {
						$subpath = DS . $thumbDir . DS . $key;
					}
					$this->__filesToRemove[] = array(
						'field' => $fieldName,
						'dir' => $this->__fields[$model->alias][$fieldName]['dir'] . $subpath,
						'name' => $filename
					);
				}
			}
		}
	}

/**
 * Marks files for deletion in the beforeSave() callback
 *
 * @param object $model Reference to model
 * @param string $fieldName name of field that holds a reference to the file
 * @param array $data
 * @param strng $default
 * @param string $thumbDir Thumb directory
 * @return void
 * @access protected
 */
	function _markForDeletion(&$model, $fieldName, $data, $default, $thumbDir) {
		if (!empty($data[$model->alias][$fieldName]['remove'])) {
			if ($default) {
				$data[$model->alias][$fieldName] = $default;
			} else {
				$data[$model->alias][$fieldName] = '';
			}
			//if the record is already saved in the database, set the existing file to be removed after the save is sucessfull
			if (!empty($data[$model->alias][$model->primaryKey])) {
				$this->_setFileToRemove($model, $fieldName, $thumbDir);
			}
		}
	}

/**
 * Delete the $filename inside the $dir and the thumbnails.
 * Returns true if the file is deleted and false otherwise.
 *
 * @param object $model Reference to model
 * @param string $field
 * @param string $filename
 * @param stirng $dir
 * @return boolean
 * @access protected
 */
	function _deleteFiles(&$model, $field, $filename, $dir) {
		$saveAs = $dir . DS . $filename;
		if (is_file($saveAs) && !unlink($saveAs)) {
			return false;
		}
		if ($this->__fields[$model->alias][$field]['thumbnails'] && !empty($this->__fields[$model->alias][$field]['thumbsizes'])) {
			$sizes = array_keys($this->__fields[$model->alias][$field]['thumbsizes']);
			foreach ($sizes as $size) {
				$file =& new File($dir . DS . $size . DS . $filename);
				$file->delete();
			}
		}
		return true;
	}

/**
 * Remove original file
 *
 * @param string $saveAs
 * @return boolean
 */
	function _removeOriginal($saveAs) {
		if (is_file($saveAs) && unlink($saveAs)) {
			return true;
		}
		return false;
	}

/**
 * Unsets data from $data
 * Useful for no-db upload
 *
 * @param string $modelName name of the Model
 * @param string $fieldName name of field that holds a reference to the file
 * @param array $data
 * @param array $options
 * @return array
 * @access protected
 */
	function _unsetDataFields($modelName, $fieldName, $data, $options) {
		unset($data[$modelName][$fieldName]);
		unset($data[$modelName][$options['fields']['dir']]);
		unset($data[$modelName][$options['fields']['filesize']]);
		unset($data[$modelName][$options['fields']['mimetype']]);
		return $data;
	}

/**
 * Sets as NULL data from $data
 * Useful when deleting a file.
 *
 * @param object $model Reference to model
 * @param string $fieldName name of field that holds a reference to the file
 * @param array $data
 * @param array $options
 * @return array
 * @access protected
 */
	function _nullifyDataFields(&$model, $fieldName, $data, $options) {
		$data[$model->alias][$fieldName] = null;
		foreach ($options['fields'] as $field) {
			if ($model->hasField($field)) {
				$data[$model->alias][$field] = null;
			}
		}
		return $data;
	}

/**
 * Adds an error, legacy from the component
 *
 * @param string $msg error message
 * @return void
 * @access protected
 */
	function _addError($msg) {
		$this->errors[] = $msg;
	}
}
