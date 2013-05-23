<?php

namespace Ibrows\MediaBundle\Type;

class SoundcloudType extends AbstractMediaType
{
    /**
     * @param string $link
     */
    public function supports($data)
    {
        return $this->isFullLink($data) ||
                    $this->isEmbeddLink($data);
    }
    
    protected function isFullLink($data)
    {
        return strpos($data, 'soundcloud.com/') !== false;
    }
    
    protected function isEmbeddLink($data)
    {
        return strpos($data, 'snd.sc/') !== false;
    }
    
    public function generateExtra($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://soundcloud.com/oembed?format=json&url=$url");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $jsondata = curl_exec($ch);
        
        $data = json_decode($jsondata, true);
        
        return $data;
    }
    
    public function generateHtml($data, $extra)
    {
        return $extra['html'];
    }
    
    public function getName()
    {
        return 'soundcloud';
    }
}
