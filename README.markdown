# MeioUpload Behavior

This behavior provides to upload files in your application, as well as the possibility to translate the error message (originally only in portuguese) and the use of phpThumb as a better thumbnail generator.


## Installation
- Clone from github : in your behaviors directory type `git clone git://github.com/josegonzalez/MeioUpload.git meio_upload`
- Add as a git submodule : in your behaviors directory type `git submodule add git://github.com/josegonzalez/MeioUpload.git meio_upload`
- Download an archive from github and extract it in `/app`

# Usage
In a model that needs uploading, replace the class declaration with :

    <?php
    class Image extends AppModel {
        ...
            var $actsAs = array(
                'MeioUpload' => array(
                    'filename' => array(
                        'dir' => 'files/images',
                        'create_directory' => false,
                        'max_size' => 2097152,
                        'max_dimension' => 'w',
                        'thumbnailQuality' => 50,
                        'useImageMagick' => true,
                        'imageMagickPath' => '/usr/bin/convert',
                        'allowed_mime' => array( 'image/gif', 'image/jpeg', 'image/pjpeg', 'image/png'),
                        'allowed_ext' => array('.jpg', '.jpeg', '.png', '.gif'),
                        'thumbsizes' => array(
                            'small'  => array('width' => 100, 'height' => 100),
                            'medium' => array('width' => 220, 'height' => 220),
                            'large'  => array('width' => 800, 'height' => 600)
                        ),
                    )
                )
            );
    }

More documentation to come. Check the behavior for more information :)