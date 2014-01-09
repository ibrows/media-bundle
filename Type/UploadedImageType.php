<?php

namespace Ibrows\MediaBundle\Type;

use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\File;
use Imagick;
use ImagickPixel;

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

    /**
     * @param $max_width
     * @param $max_height
     * @param $max_size
     * @param array $mime_types
     * @param array $formats
     */
    public function __construct($max_width, $max_height, $max_size, array $mime_types, array $formats)
    {
        $this->maxWidth = $max_width;
        $this->maxHeight = $max_height;
        $this->maxSize = $max_size;

        $this->mimeTypes = $mime_types;
        $this->formats = $formats;
    }

    /**
     * @param mixed $file
     * @return bool|int
     */
    public function supports($file)
    {
        $supports = parent::supports($file);
        if ($supports) {
            return 2;
        }

        return $supports;
    }

    /**
     * @param File $file
     * @return bool
     * @throws \Exception
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
     * @param mixed $file
     * @return null|FormError
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
     * @param File $file
     * @return FormError
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
     * @param File $file
     * @return FormError
     */
    protected function validateImgSize(File $file)
    {
        if (!$this->maxHeight && !$this->maxWidth) {
            return;
        }

        $img = new Imagick($file->getPathname());
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
     * @param mixed $file
     * @return array|null
     */
    public function generateExtra($file)
    {
        $extra = parent::generateExtra($file);
        foreach ($this->formats as $name => $format) {
            $width = array_key_exists('width', $format) ? $format['width'] : null;
            $height = array_key_exists('height', $format) ? $format['height'] : null;

            $resizedFile = $this->resizeImage($file, $name, $width, $height);
            $this->addExtraFile($extra, $name, $resizedFile);
        }

        return $extra;
    }

    /**
     * @param File $file
     * @param $format
     * @param $targetwidth
     * @param $targetheight
     * @return File
     */
    protected function resizeImage(File $file, $format, $targetwidth, $targetheight)
    {
        $targetfilename = tempnam(sys_get_temp_dir(), 'ibrows_media_image');

        $img = new Imagick($file->getPathname());

        $height = $img->getimageheight();
        $width = $img->getimagewidth();
        $factor = $height / $width;
        if (!$targetheight) {
            $targetheight = intval($targetwidth * $factor);
        }
        if (!$targetwidth) {
            $targetwidth = intval($targetheight / $factor);
        }

        $img = $this->processOrientation($img);
        $img->cropthumbnailimage($targetwidth, $targetheight);
        $img->writeimage($targetfilename);

        return new File($targetfilename);
    }

    /**
     * @param Imagick $img
     * @return Imagick
     */
    public function processOrientation(Imagick $img)
    {
        $orientation = $img->getimageorientation();
        if ($orientation == Imagick::ORIENTATION_UNDEFINED) {
            return $img;
        }

        $flippedOrientations = array(
            Imagick::ORIENTATION_TOPRIGHT,
            Imagick::ORIENTATION_BOTTOMLEFT,
            Imagick::ORIENTATION_LEFTTOP,
            Imagick::ORIENTATION_RIGHTBOTTOM,
        );

        if (in_array($orientation, array(Imagick::ORIENTATION_BOTTOMLEFT, Imagick::ORIENTATION_BOTTOMRIGHT))) {
            $img->rotateimage(new ImagickPixel(), 180);
        }
        if (in_array($orientation, array(\Imagick::ORIENTATION_LEFTBOTTOM, Imagick::ORIENTATION_LEFTTOP))) {
            $img->rotateimage(new ImagickPixel(), -90);
        }
        if (in_array($orientation, array(\Imagick::ORIENTATION_RIGHTBOTTOM, Imagick::ORIENTATION_RIGHTTOP))) {
            $img->rotateimage(new ImagickPixel(), 90);
        }
        if (in_array($orientation, $flippedOrientations)) {
            $img->flipimage();
        }
        $img->setimageorientation(Imagick::ORIENTATION_UNDEFINED);

        return $img;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'image';
    }
}