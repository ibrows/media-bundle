<?php

namespace Ibrows\MediaBundle\Model;

use Doctrine\ORM\Mapping as ORM;

class Media implements MediaInterface
{
    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $url;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $html;

    /**
     * @var string
     *
     * @ORM\Column(type="string", name="data")
     */
    protected $data;

    /**
     * @var array
     *
     * @ORM\Column(type="array", name="extra", nullable=true)
     */
    protected $extra;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $type;

    /**
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->data;
    }

    /**
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     *
     * @param  string                          $type
     * @return \Ibrows\MediaBundle\Model\Media
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     *
     * @param  string                          $url
     * @return \Ibrows\MediaBundle\Model\Media
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     *
     * @return string
     */
    public function getHtml()
    {
        return $this->html;
    }

    /**
     *
     * @param  string                          $html
     * @return \Ibrows\MediaBundle\Model\Media
     */
    public function setHtml($html)
    {
        $this->html = $html;

        return $this;
    }

    /**
     *
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     *
     * @param  mixed                           $data
     * @return \Ibrows\MediaBundle\Model\Media
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     *
     * @return array
     */
    public function getExtra()
    {
        return $this->extra;
    }

    /**
     *
     * @param  array                           $extra
     * @return \Ibrows\MediaBundle\Model\Media
     */
    public function setExtra($extra)
    {
        $this->extra = $extra;

        return $this;
    }
}
