<?php

namespace Ibrows\MediaBundle\Type;

use Symfony\Component\Form\FormError;

use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\File;
use Ibrows\MediaBundle\Model\MediaInterface;

class UploadedImageType extends AbstractUploadedType
{
    /**
     * @var number
     */
    protected $maxSize;
    /**
     * @var number
     */
    protected $maxHeight;
    /**
     * @var number
     */
    protected $maxWidth;
    /**
     * @var array
     */
    protected $formats;
    /**
     * @var array
     */
    protected $mimeTypes;
    
    public function __construct($max_width, $max_height, $max_size, array $mime_types, array $formats)
    {
        $this->maxWidth =  $max_width;
        $this->maxHeight = $max_height;
        $this->maxSize = $max_size;
        
        $this->mimeTypes = $mime_types;
        $this->formats = $formats;
    }
    
    /**
     * @param File $file
     * @return boolean
     */
    protected function supportsMimeType(File $file)
    {
        try {
            $mime = $file->getMimeType();
        } catch (FileNotFoundException $e) {
            // this might happen if upload_max_filesize is set too small
            throw new \Exception('File could not be uploaded. Did you set the upload_max_filesize too small?');
        }
    
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
        
        $imgSizeError = $this->validateImgSize($file);
        if ($imgSizeError) {
            return $imgSizeError;
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
            return new FormError(null, 'media.error.fileSize', array(
                    '%size%' => $this->maxSize
            ));
        }
    }

    /**
     * 
     * @param File $file
     * @return void|string
     */
    protected function validateImgSize(File $file)
    {
        if (!$this->maxHeight && !$this->maxWidth) {
            return;
        }
        
        $img = new \Imagick($file->getPathname());
        $height = $img->getimageheight();
        $width = $img->getimagewidth();
        
        if ($this->maxHeight && $height > $this->maxHeight) {
            return new FormError(null, 'media.error.imageHeight', array(
                    '%height%' => $this->maxHeight
            ));
        }
        
        if ($this->maxWidth && $width > $this->maxWidth) {
            return new FormError(null, 'media.error.imageWidth', array(
                    '%width%' => $this->maxWidth
            ));
        }
    }
    
    /**
     * {@inheritdoc}
     */
    protected function postRemoveExtra($extra)
    {
        if (is_array($extra)){
            foreach ($this->formats as $name => $format) { 
                if (array_key_exists($name, $extra)) {
                    $file = $extra["{$name}_file"];
                    if (file_exists($file)) {
                        unlink($file);
                    }
                }
            }
        }
    }
    
    public function postLoad(MediaInterface $media)
    {
        parent::postLoad($media);
        
        $extra = $media->getExtra();
        if (is_array($extra)){
            foreach ($this->formats as $name => $format) {
                if (array_key_exists($name, $extra)) {
                    $filekey = "{$name}_file";
                    $filename = $extra[$filekey];
                    $path = $this->getAbsolutePath($filename);
                    if (file_exists($path)) {
                        $extra[$filekey] = new File($path);
                    }
                }
            }
        }
        $media->setExtra($extra);
    }
    
    protected function revertLoadExtra(MediaInterface $media, $changeSet)
    {
        $extra = $media->getExtra();
        if (is_array($extra)){
            foreach ($this->formats as $name => $format) {
                if (array_key_exists($name, $extra)) {
                    $filekey = "{$name}_file";
                    $file = $extra[$filekey];
                    if ($file instanceof File) {
                        $extra[$filekey] = $file->getFilename();
                    }
                }
            }
        }
        $media->setExtra($extra);
    }
    
    /**
     * {@inheritdoc}
     */
    public function generateExtra($file)
    {
        $extra = parent::generateExtra($file);
        foreach ($this->formats as $name => $format) {
            $width = array_key_exists('width', $format) ? $format['width'] : null;
            $height = array_key_exists('height', $format) ? $format['height'] : null;
            
            $resizedFile = $this->resizeImage($file, $name, $width, $height);
            $extra = array_merge($extra, array(
                    "{$name}_file" => $resizedFile->getFilename(),
                    $name => $this->getWebUrl($resizedFile)
            ));
        }
        
        return $extra;
    }
    
    /**
     * @param \Symfony\Component\HttpFoundation\File\File $file
     * @param number|null $targetwidth
     * @param number|null $targetheight
     * 
     * @return \Symfony\Component\HttpFoundation\File\File
     */
    protected function resizeImage(File $file, $format, $targetwidth, $targetheight)
    {
        $targetfilename = $this->getAbsolutePath($this->getUploadFilename($file, $format));

        $img = new \Imagick($file->getPathname());
        $height = $img->getimageheight();
        $width = $img->getimagewidth();
        $factor = $height/$width;
        if (!$targetheight) {
            $targetheight = intval($targetwidth * $factor);
        }
        if (!$targetwidth) {
            $targetwidth = intval($targetheight / $factor);
        }
        
        $img->cropthumbnailimage($targetwidth, $targetheight);
        $img->writeimage($targetfilename);
        
        return new File($targetfilename);
    }
    
    public function getName()
    {
        return 'image';
    }
}
