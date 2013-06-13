<?php

namespace Ibrows\MediaBundle\Type;

use Symfony\Component\HttpFoundation\File\File;

class UploadedFileType extends AbstractUploadedType
{
    /**
     * @var number
     */
    protected $maxSize;
    /**
     * @var array
     */
    protected $mimeTypes;
    
    public function __construct($max_size, array $mime_types)
    {
        $this->maxSize = $max_size;
        $this->mimeTypes = $mime_types;
    }
    
    /**
     * @param File $file
     * @return boolean
     */
    protected function supportsMimeType(File $file)
    {
        $mime = $file->getMimeType();
    
        return array_search($mime, $this->mimeTypes) !== false;
    }
    
    /**
     * {@inheritdoc}
     */
    public function validate($file)
    {
        /* @var $file File */
        $fileSizeError = $this->validateFileSize($file);
        if ($fileSizeError) {
            return $fileSizeError;
        }
    }
    
    /**
     *
     * @param File $file
     * @return void|string
     */
    protected function validateFileSize(File $file)
    {
        if (!$this->maxSize) {
            return;
        }
    
        $fileSize = $file->getSize();
        if ($fileSize > $this->maxSize) {
            return 'media.error.fileSize';
        }
    }
    
    public function getName()
    {
        return 'file';
    }
}
