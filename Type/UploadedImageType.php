<?php

namespace Ibrows\MediaBundle\Type;

use Symfony\Component\HttpFoundation\File\File;

use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

use Symfony\Component\HttpFoundation\File\UploadedFile;

use Symfony\Component\DependencyInjection\ContainerInterface;

class UploadedImageType extends AbstractMediaType
{
    protected $upload_dir;
    protected $uri_prefix;
    protected $container;
    protected $formats;
    protected $maxHeight;
    protected $maxWidth;
    
    public function __construct($upload_dir, $uri_prefix, $max_width, $max_height, $formats)
    {
        $this->upload_dir = $upload_dir;
        $this->uri_prefix = $uri_prefix;
        //TODO: parameter
        $this->maxWidth =  $max_width;
        $this->maxHeight = $max_height;
        $this->formats = $formats;
    }
    
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    public function prePersist($path)
    {
        if(!file_exists($path)){
            throw new FileNotFoundException($path);
        }
        
        $newpath = $this->resizeImage($path->getPathname(), $this->maxWidth, $this->maxHeight);
        return $newpath;
    }
    
    public function postDelete($data, $extra)
    {
        if(file_exists($data)){
            unlink($data);
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
    
    protected function getDir($data)
    {
        if (!is_dir($this->upload_dir)) {
            mkdir($this->upload_dir, 0777, true);
        }
        
        return $this->upload_dir;
    }
    
    protected function getFilename($data)
    {
        return uniqid(null, true);
    }
    
    /**
     * @param string $link
     */
    public function supports($file)
    {
        return  $file instanceof UploadedFile &&
                    $this->supportsMimeType($file);
    }
    
    protected function supportsMimeType($file)
    {
        $mime = $file->getMimeType();
        
        return $mime === 'image/jpeg' || $mime === 'image/png';
    }
    
    public function generateExtra($data)
    {
        $extra = array();
        foreach ($this->formats as $name => $format) {
            $width = array_key_exists('width', $format) ? $format['width'] : null;
            $height = array_key_exists('height', $format) ? $format['height'] : null;
            
            $filename = $this->resizeImage($data, $width, $height);
            $extra = array_merge($extra, array(
                    "{$name}_filename" => $filename,
                    $name => $this->getUrl($filename)
            ));
        }
        
        return $extra;
    }
    
    protected function resizeImage($data,  $targetwidth, $targetheight)
    {
        $targetfile = $this->getDir($data).'/'.$this->getFilename($data);

        $img = new \Imagick($data);
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
        $img->writeimage($targetfile);
        
        return $targetfile;
    }
    
    public function generateUrl($data, $extra)
    {
        return $this->getUrl($data);
    }
    
    protected function getUrl($data)
    {
        $file = new File($data);
        
        $uri_prefix = substr($this->uri_prefix, 1);
        $url = $this->container->get('templating.helper.assets')->getUrl($uri_prefix.'/'.$file->getFilename());
        return $url;
    }
    
    public function getName()
    {
        return 'uploadedimage';
    }
}
