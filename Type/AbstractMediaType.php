<?php

namespace Ibrows\MediaBundle\Type;

use Ibrows\MediaBundle\Model\MediaInterface;

abstract class AbstractMediaType implements MediaTypeInterface
{
    /**
     * @var \Ibrows\MediaBundle\Model\MediaInterface
     */
    protected $media;

    public function validate($data)
    {
        return null;
    }

    /**
     * Hook to allow transformation of form submitted data before
     * the available generater methods will be called.
     *
     * @param  mixed $data
     * @return mixed
     */
    protected function preTransformData($data)
    {
        return $data;
    }

    /**
     * Hook to allow transformation of form submitted data after
     * the available generater methods will be called.
     *
     * @param  mixed $data
     * @return mixed
     */
    protected function postTransformData($data)
    {
        return $data;
    }

    /**
     * Allows you to generate an arry of additional information
     * to be stored.
     *
     * @param  mixed      $data
     * @return array|null
     */
    protected function generateExtra($data)
    {
        return null;
    }

    /**
     * Allows you to generate the url to be stored.
     *
     * @param  mixed       $data
     * @param  array       $extra
     * @return string|null
     */
    protected function generateUrl($data, $extra)
    {
        return null;
    }

    /**
     * Allows you to generate the html to be stored.
     *
     * @param  mixed       $data
     * @param  array       $extra
     * @return string|null
     */
    protected function generateHtml($data, $extra)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function prePersist(MediaInterface $media)
    {
        $this->media = $media;
        $data = $media->getData();
        $data = $this->preTransformData($data);
        $extra = $this->generateExtra($data);

        $media->setExtra($extra);
        $media->setUrl($this->generateUrl($data, $extra));
        $media->setHtml($this->generateHtml($data, $extra));

        $data = $this->postTransformData($data);
        $media->setData($data);
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdate(MediaInterface $media, array $changeSet)
    {
        $this->media = $media;
        $olddata = $changeSet['data'][0];
        $newdata = $changeSet['data'][1];

        $media->setData($olddata);
        $this->postRemove($media);

        $media->setData($newdata);
        $this->prePersist($media);
    }

    /**
     * {@inheritdoc}
     */
    public function postRemove(MediaInterface $media)
    {
        $this->media = $media;
    }

    public function postUpdate(MediaInterface $media)
    {
        $this->postLoad($media);
    }

    /**
     * {@inheritdoc}
     */
    public function postPersist(MediaInterface $media)
    {
        $this->postLoad($media);
    }

    /**
     * {@inheritdoc}
     */
    public function postLoad(MediaInterface $media)
    {
        $this->media = $media;
    }
}
