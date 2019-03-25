<?php

namespace Ibrows\MediaBundle\Type;

use Ibrows\MediaBundle\Model\MediaInterface;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

abstract class AbstractUploadedType extends AbstractMediaType
{
    /**
     * The absolute path to the root location where all the files
     * will be moved to after upload.
     *
     * @var string
     */
    protected $upload_location;
    /**
     * The directory which will be used additionally to the upload
     * location
     *
     * @var string
     */
    protected $upload_root;

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
     * @return string
     */
    public function getUploadLocation()
    {
        return $this->upload_location;
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
     * @return string
     */
    public function getUploadRoot()
    {
        return $this->upload_root;
    }

    /**
     * {@inheritdoc}
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
    protected function moveToUpload(File $file, $format = null)
    {
        $directory = $this->getAbsoluteUploadPath();
        $filename = $this->getUploadFilename($file, $format);
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

        $file = $media->getData();

        $extra = $media->getExtra();

        if (!$file instanceof File) {
            $path = $this->getAbsoluteUploadPath($file);

            if (file_exists($path) && !is_dir($path))
            {
                $file = new File($path);
            } else {
                $file = null;
            }
        }

        $media->setData($file);
        $this->postLoadExtra($extra);
        $media->setExtra($extra);
    }

    protected function postLoadExtra(array &$extra)
    {
        if (array_key_exists('files', $extra)) {
            $this->postLoadExtraFiles($extra['files']);
        }
    }

    protected function postLoadExtraFiles(array &$files)
    {
        foreach ($files as &$file) {
            $data = $file['data'];
            $path = $this->getAbsoluteUploadPath($data);
            if (file_exists($path) && !is_dir($path)) {
                $file['data'] = new File($path);
            }
        }
    }

    /**
     * Confinience method which allows easy adding of additional
     * files.
     *
     * @param  array  $extra
     * @param  string $key   which represents the file
     * @param  File   $file  wich will be added
     * @return array  $extra containing the additional file
     */
    protected function addExtraFile(array &$extra, $key, File $file)
    {
        $file = $this->moveToUpload($file, $key);
        if (!array_key_exists('files', $extra)) {
            $extra['files'] = array();
        }
        $files = &$extra['files'];
        $files[$key] = array(
            'url' => $this->generateUrl($file, $extra),
            'data' => $file->getFilename(),
        );

        return $extra;
    }

    /**
     * Remove a previeously added file.
     *
     * @param  array   $extra
     * @param  string  $key
     * @return boolean whether or not the file existed and was removed
     */
    protected function removeExtraFile(array &$extra, $key)
    {
        $files = array();
        if (!array_key_exists('files', $extra)) {
            return false;
        }
        $files = &$extra['files'];
        if (array_key_exists($key, $files)) {
            $file = $files[$key]['data'];
            unset($files[$key]);
        }

        if (file_exists($file) && !is_dir($file)) {
            unlink($file);
        }

        return true;
    }

    /**
     * Returns the previously added file if it exists, null otherwise.
     *
     * @param  MediaInterface $media
     * @param  string         $key
     * @return File|null
     */
    public function getExtraFile(MediaInterface $media, $key)
    {
        $extra = $media->getExtra();
        if (!$extra || !array_key_exists('files', $extra)) {
            return null;
        }
        $files = $extra['files'];
        if (array_key_exists($key, $files)) {
            return $files[$key]['data'];
        }

        return null;
    }

    /**
     * Get the url from the added File. The url was generated using
     * the generateUrl method.
     *
     * @param  MediaInterface $media
     * @param  string         $key
     * @return string|null
     */
    public function getExtraFileUrl(MediaInterface $media, $key)
    {
        $extra = $media->getExtra();
        if (!$extra || !array_key_exists('files', $extra)) {
            return null;
        }
        $files = $extra['files'];
        if (array_key_exists($key, $files)) {
            return $files[$key]['url'];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function postRemove(MediaInterface $media)
    {
        parent::postRemove($media);
        $file = $media->getData();
        $extra = $media->getExtra();

        if (!$file instanceof File) {
            $path = $this->getAbsoluteUploadPath()
                    .DIRECTORY_SEPARATOR.$file;

            if (file_exists($path) && !is_dir($path)) {
                $file = new File($path);
            }
        }

        if (file_exists($file) && !is_dir($file)) {
            unlink($file);
        }

        if ($extra) {
            $this->postRemoveExtra($media, $extra);
        }
    }

    /**
     * Overwrite this method in order to clean up the additional
     * data created in generateExtra
     *
     * @param mixed $extra
     */
    protected function postRemoveExtra(MediaInterface $media, $extra)
    {
        if (array_key_exists('files', $extra)) {
            $this->postRemoveExtraFiles($media, $extra['files']);
        }
    }

    protected function postRemoveExtraFiles(MediaInterface $media, array &$files)
    {
        $extra = $media->getExtra();
        foreach ($files as $key => $file) {
            $this->removeExtraFile($extra, $key);
        }
        $media->setExtra($extra);
    }

    /**
     * @return string
     */
    protected function getRelativeUploadPath()
    {
        $dir = $this->getUploadRoot().
                DIRECTORY_SEPARATOR.$this->getUploadFolder();

        if ($dir[0] === DIRECTORY_SEPARATOR) {
            $dir = substr($dir, 1);
        }

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
        $extension = $file->guessExtension();
        if ($file instanceof UploadedFile) {
            $extension = $file->getClientOriginalExtension();
        }
        $filename = uniqid();
        if ($extension) {
            $filename .= $filename.'.'.$extension;
        }

        return $filename;
    }

    /**
     * {@inheritdoc}
     */
    protected function generateUrl($file, $extra)
    {
        return $this->getRelativeUploadPath()
                .DIRECTORY_SEPARATOR.$file->getFilename();
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

    protected function getAbsoluteUploadPath($filename = null)
    {
        $dir = $this->getUploadLocation().
            DIRECTORY_SEPARATOR.$this->getRelativeUploadPath();

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        return $dir.DIRECTORY_SEPARATOR.$filename;
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdate(MediaInterface $media, array $changeSet)
    {
        $newdata = $changeSet['data'][1];

        if ($newdata instanceof UploadedFile) {
            parent::preUpdate($media, $changeSet);
            return;
        }

        $this->revertLoad($media);
    }

    protected function revertLoad(MediaInterface $media)
    {
        $file = $media->getData();
        $extra = $media->getExtra();

        if ($file instanceof File) {
            $media->setData($file->getFilename());
        }

        if ($extra) {
            $this->revertLoadExtra($extra);
            $media->setExtra($extra);
        }
    }

    protected function revertLoadExtra(array &$extra)
    {
        if (array_key_exists('files', $extra)) {
            $this->revertLoadExtraFiles($extra['files']);
        }
    }

    protected function revertLoadExtraFiles(array &$files)
    {
        foreach ($files as &$file) {
            $data = $file['data'];
            if ($data instanceof File) {
                $file['data'] = $data->getFilename();
            }
        }
    }
}
