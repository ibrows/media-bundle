<?php

namespace Ibrows\MediaBundle\Type;

use Ibrows\MediaBundle\Model\MediaInterface;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractUploadedType extends AbstractMediaType
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
     * @var ContainerInterface
     */
    protected $container;
    
    /**
     * @param ContainerInterface $container
     * @return \Ibrows\MediaBundle\Type\AbstractUploadedType
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
        return $this;
    }
    
    /**
     * @param string $dir
     * @return \Ibrows\MediaBundle\Type\AbstractUploadedType
     */
    public function setUploadDir($dir)
    {
        $this->upload_dir = $dir.DIRECTORY_SEPARATOR.$this->getName();
        return $this;
    }
    
    /**
     * @param string $prefix
     * @return \Ibrows\MediaBundle\Type\AbstractUploadedType
     */
    public function setUriPrefix($prefix)
    {
        $this->uri_prefix = $prefix.DIRECTORY_SEPARATOR.$this->getName();
        return $this;
    }
    
    /**
     * @param string $link
     */
    public function supports($file)
    {
        return  $file instanceof File &&
                    $this->supportsMimeType($file);
    }
    
    /**
     * @param File $file
     * @return boolean
     */
    abstract protected function supportsMimeType(File $file);
    
    /**
     * {@inheritdoc}
     */
    protected function preTransformData($file)
    {
        if(!file_exists($file)){
            throw new FileNotFoundException($file);
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
        $directory = $this->getWebDir();
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
            $file = new File($data);
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
        
        if ($extra){
            $this->postRemoveExtra($extra);
        }
    }

    /**
     * Overwrite this method in order to clean up the additional
     * data created in generateExtra
     * 
     * @param unknown $extra
     */
    protected function postRemoveExtra($extra)
    {
    }
     
    /**
     * @return string
     */
    protected function getWebDir()
    {
        $dir = $this->upload_dir;
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        
        return $dir;
    }
    
    /**
     * @param File $file
     * @return string
     */
    protected function getWebFilename(File $file)
    {
        return uniqid(null, true);
    }
    
    /**
     * {@inheritdoc}
     */
    protected function generateUrl($file, $extra)
    {
        return $this->getWebUrl($file);
    }
    
    /**
     * {@inheritdoc}
     */
    protected function generateExtra($file)
    {
        $extra = array(
                'originalFilename' => $file->getClientOriginalName()
        );
        
        return $extra;
    }
    
    /**
     * @param File $file
     * @return the web url of the file
     */
    protected function getWebUrl(File $file)
    {
        $uri_prefix = substr($this->uri_prefix, 1);
        $url = $this->container->get('templating.helper.assets')->getUrl(
                $uri_prefix.DIRECTORY_SEPARATOR.$file->getFilename()
        );
        return $url;
    }
    
    public function preUpdate(MediaInterface $media, array $changeSet)
    {
        $olddata = $changeSet['data'][0];
        $newdata = $changeSet['data'][1];
        if ($olddata === $newdata->getPathname()) {
            return;
        }
        
        parent::preUpdate($media, $changeSet);
    }
}
