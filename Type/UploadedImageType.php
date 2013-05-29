<?php

namespace Ibrows\MediaBundle\Type;

use Ibrows\MediaBundle\Model\MediaInterface;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

use Symfony\Component\DependencyInjection\ContainerInterface;

class UploadedImageType extends AbstractMediaType
{
    /**
     * @var string
     */
    protected $upload_dir;
    /**
     * @var string
     */
    protected $uri_prefix;
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
    
    /**
     * @var ContainerInterface
     */
    protected $container;
    
    public function __construct($upload_dir, $uri_prefix, $max_width, $max_height, $max_size, array $mime_types, array $formats)
    {
        $this->upload_dir = $upload_dir;
        $this->uri_prefix = $uri_prefix;
        
        $this->maxWidth =  $max_width;
        $this->maxHeight = $max_height;
        $this->maxSize = $max_size;
        
        $this->mimeTypes = $mime_types;
        $this->formats = $formats;
    }
    
    /**
     * @param ContainerInterface $container
     * @return \Ibrows\MediaBundle\Type\UploadedImageType
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
        return $this;
    }
    
    /**
     * @param string $link
     */
    public function supports($file)
    {
        return  $file instanceof UploadedFile &&
        $this->supportsMimeType($file);
    }
    
    /**
     * @param UploadedFile $file
     * @return boolean
     */
    protected function supportsMimeType(UploadedFile $file)
    {
        $mime = $file->getMimeType();
    
        return array_search($mime, $this->mimeTypes) !== false;
    }
    
    /**
     * {@inheritdoc}
     */
    public function validate($file)
    {
        /* @var $file UploadedFile */
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
     * @param UploadedFile $file
     * @return void|string
     */
    protected function validateFileSize(UploadedFile $file)
    {
        if (!$this->maxSize) {
            return;
        }
        
        $fileSize = $file->getSize();
        if ($fileSize > $this->maxSize) {
            return 'media.error.fileSize';
        }
    }

    /**
     * 
     * @param UploadedFile $file
     * @return void|string
     */
    protected function validateImgSize(UploadedFile $file)
    {
        if (!$this->maxHeight && !$this->maxWidth) {
            return;
        }
        
        $img = new \Imagick($file->getPathname());
        $height = $img->getimageheight();
        $width = $img->getimagewidth();
        
        if ($height > $this->maxHeight || $width > $this->maxWidth) {
            return 'media.error.imageSize';
        }
    }
    
    /**
     * {@inheritdoc}
     */
    protected function preTransformData($file)
    {
        if(!file_exists($file)){
            throw new FileNotFoundException($file->getPathname());
        }
        
        $newFile = $this->moveToWeb($file);
        return $newFile;
    }

    /**
     * {@inheritdoc}
     */
    protected function postTransformData($file)
    {
        return $file->getPathname();
    }
    
    /**
     * @param UploadedFile $file
     * @return UploadedFile pointing to the new location
     */
    protected function moveToWeb(UploadedFile $file)
    {
        $directory = $this->getWebDir($file);
        $filename = $this->getWebFilename($file);
        $newFile = $file->move($directory, $filename);
        
        return new UploadedFile($newFile->getPathname(), $file->getClientOriginalName());
    }
    
    /**
     * {@inheritdoc}
     */
    public function postLoad(MediaInterface $media)
    {
        $data = $media->getData();
        $extra = $media->getExtra();
        $originalFilename = '';
        if (array_key_exists('originalFilename', $extra)) {
            $originalFilename = $extra['originalFilename'];
        }
        
        $file = null;
        if (file_exists($data)) {
            $file = new UploadedFile($data, $originalFilename);
        }
        
        $media->setData($file);
    }
    
    /**
     * {@inheritdoc}
     */
    public function postRemove(MediaInterface $media)
    {
        $file = $media->getData();
        $extra = $media->getExtra();
        
        if(file_exists($file)){
            unlink($file);
        }
        
        if ($extra && is_array($extra)){
            foreach ($this->formats as $name => $format) { 
                if (array_key_exists($name, $extra)) {
                    $filename = $extra["{$name}_filename"];
                    if (file_exists($filename)) {
                        unlink($filename);
                    }
                }
            }
        }
    }
    
    /**
     * @param UploadedFile $file
     * @return string
     */
    protected function getWebDir(UploadedFile $file)
    {
        if (!is_dir($this->upload_dir)) {
            mkdir($this->upload_dir, 0777, true);
        }
        
        return $this->upload_dir;
    }
    
    /**
     * @param UploadedFile $file
     * @return string
     */
    protected function getWebFilename(UploadedFile $file)
    {
        return uniqid(null, true);
    }
    
    /**
     * {@inheritdoc}
     */
    public function generateExtra($file)
    {
        $extra = array(
                'originalFilename' => $file->getClientOriginalName()
        );
        foreach ($this->formats as $name => $format) {
            $width = array_key_exists('width', $format) ? $format['width'] : null;
            $height = array_key_exists('height', $format) ? $format['height'] : null;
            
            $resizedFile = $this->resizeImage($file, $width, $height);
            $extra = array_merge($extra, array(
                    "{$name}_filename" => $resizedFile->getPathname(),
                    $name => $this->getWebUrl($resizedFile)
            ));
        }
        
        return $extra;
    }
    
    /**
     * @param \Symfony\Component\HttpFoundation\UploadedFile\UploadedFile $file
     * @param number|null $targetwidth
     * @param number|null $targetheight
     * 
     * @return \Symfony\Component\HttpFoundation\UploadedFile\UploadedFile
     */
    protected function resizeImage(UploadedFile $file,  $targetwidth, $targetheight)
    {
        $targetfilename = $this->getWebDir($file).'/'.$this->getWebFilename($file);

        $img = new \Imagick($file->getPathname());
        $height = $img->getimageheight();
        $width = $img->getimagewidth();
        $factor = $height/$width;
        if (!$targetheight) {
            $targetheight = $factor * $targetwidth;
        }
        if (!$targetwidth) {
            $targetwidth = $factor * $targetheight;
        }
        
        $img->cropthumbnailimage($targetwidth, $targetheight);
        $img->writeimage($targetfilename);
        
        return new UploadedFile($targetfilename, $file->getClientOriginalName());
    }
    
    /**
     * {@inheritdoc}
     */
    public function generateUrl($file, $extra)
    {
        return $this->getWebUrl($file);
    }
    
    /**
     * @param UploadedFile $file
     * @return the web url of the file
     */
    protected function getWebUrl(UploadedFile $file)
    {
        $uri_prefix = substr($this->uri_prefix, 1);
        $url = $this->container->get('templating.helper.assets')->getUrl($uri_prefix.'/'.$file->getFilename());
        return $url;
    }
    
    public function getName()
    {
        return 'uploadedimage';
    }
}
