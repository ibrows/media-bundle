<?php

namespace Ibrows\MediaBundle\Model;

interface MediaInterface
{
    /**
     * @return string the media type
     */
    public function getType();
    /**
     *
     * @param string $type
     */
    public function setType($type);
    /**
     * @return mixed the original data of the form
     */
    public function getData();
    /**
     *
     * @param mixed $data
     */
    public function setData($data);
    /**
     * @return array an array of optionally generated data
     */
    public function getExtra();
    /**
     *
     * @param array $extra
    */
    public function setExtra($extra);
    /**
     * @return string the generated url
     */
    public function getUrl();
    /**
     *
     * @param string $url
     */
    public function setUrl($url);
    /**
     * @return string the generated html
     */
    public function getHtml();
    /**
     *
     * @param string $html
     */
    public function setHtml($html);
}
