<?php

namespace Ibrows\MediaBundle\Type;

use Symfony\Component\Templating\Asset\PackageInterface;

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
    protected $upload_location;
    /**
     * @var string
     */
    protected $upload_root;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var PackageInterface
     */
    protected $assetHelper;

    /**
     * @param  ContainerInterface                            $container
     * @return \Ibrows\MediaBundle\Type\AbstractUploadedType
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * @param  PackageInterface                              $helper
     * @return \Ibrows\MediaBundle\Type\AbstractUploadedType
     */
    public function setAssetHelper(PackageInterface $helper)
    {
        $this->assetHelper = $helper;

        return $this;
    }

    public function getAssetHelper()
    {
        if ($this->assetHelper) {
            return $this->assetHelper;
        }

        if ($this->container) {
            return $this->container->get('templating.helper.assets');
        }
    }

    /**
     * @param  string                                        $dir
     * @return \Ibrows\MediaBundle\Type\AbstractUploadedType
     */
    public function setUploadLocation($dir)
    {
        $this->upload_location = $dir;

        return $this;
    }

    /**
     * @param  string                                        $prefix
     * @return \Ibrows\MediaBundle\Type\AbstractUploadedType
     */
    public function setUploadRoot($root)
    {
        $this->upload_root = $root;

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
     * @param  File    $file
     * @return boolean
     */
    abstract protected function supportsMimeType(File $file);

    /**
     * {@inheritdoc}
     */
    protected function preTransformData($file)
    {
        if (!file_exists($file)) {
            throw new FileNotFoundException($file);
        }

        $newFile = $this->moveToUpload($file);

        return $newFile;
    }

    /**
     * {@inheritdoc}
     */
    protected function postTransformData($file)
    {
        return $file->getFilename();
    }

    /**
     * @param  File $file
     * @return File pointing to the new location
     */
    protected function moveToUpload(File $file)
    {
        $directory = $this->getAbsolutePath();
        $filename = $this->getUploadFilename($file);
        $newFile = $file->move($directory, $filename);
        $originalFilename = $file->getFilename();
        if ($file instanceof UploadedFile) {
            $originalFilename = $file->getClientOriginalName();
        }

        return new UploadedFile($newFile->getPathname(), $originalFilename);
    }

    /**
     * {@inheritdoc}
     */
    public function postLoad(MediaInterface $media)
    {
        $this->media = $media;

        $data = $media->getData();
        $extra = $media->getExtra();
        $originalFilename = '';
        if (array_key_exists('originalFilename', $extra)) {
            $originalFilename = $extra['originalFilename'];
        }

        $file = null;
        $path = $this->upload_location.
                DIRECTORY_SEPARATOR.$media->getUrl();

        if (file_exists($path) && !is_dir($path)) {
            $file = new File($path);
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

        if (!$file instanceof File) {
            $file = $this->getAbsolutePath($file);
        }

        if (file_exists($file) && !is_dir($file)) {
            unlink($file);
        }

        if ($extra) {
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
    protected function getUploadPath()
    {
        $dir = $this->upload_root.
                DIRECTORY_SEPARATOR.$this->getUploadFolder();

        return $dir;
    }

    protected function getUploadFolder()
    {
        return $this->getName();
    }

    /**
     * @param  File   $file
     * @return string
     */
    protected function getUploadFilename(File $file, $format = null)
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
        $filename = $file->getFilename();
        if ($file instanceof UploadedFile) {
            $filename = $file->getClientOriginalName();
        }

        $extra = array(
            'originalFilename' => $filename
        );

        return $extra;
    }

    /**
     * @param  File $file
     * @return the  web url of the file
     */
    protected function getWebUrl(File $file)
    {
        //TODO: remove dependency on assetHelper (use twig for that)
        $root = $this->getUploadPath();
        $url = $this->getAssetHelper()->getUrl(
            $root.DIRECTORY_SEPARATOR.$file->getFilename()
        );

        return $url;
    }

    protected function getAbsolutePath($filename = null)
    {
        $dir = $this->upload_location.
            DIRECTORY_SEPARATOR.$this->getUploadPath();

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        return $dir.DIRECTORY_SEPARATOR.$filename;
    }

    public function preUpdate(MediaInterface $media, array $changeSet)
    {
        $olddata = $changeSet['data'][0];
        $newdata = $changeSet['data'][1];

        if ($newdata instanceof UploadedFile) {
            return parent::preUpdate($media, $changeSet);
        }

        $this->revertLoadData($media, $changeSet);
        $this->revertLoadExtra($media, $changeSet);
    }

    protected function revertLoadData(MediaInterface $media, $changeSet)
    {
        $olddata = $changeSet['data'][0];
        $newdata = $changeSet['data'][1];

        if ($newdata instanceof File) {
            $media->setData($olddata);
        }
    }

    protected function revertLoadExtra(MediaInterface $media, $changeSet)
    {

    }
}
