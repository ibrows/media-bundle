<?php

namespace Ibrows\MediaBundle\Type;

class YoutubeType extends AbstractMediaType
{
    /**
     * @param string $link
     */
    public function supports($url)
    {
        return $this->isFullLink($url) ||
                    $this->isEmbeddLink($url);
    }

    protected function isFullLink($url)
    {
        return strpos($url, 'youtube.com/') !== false;
    }

    protected function isEmbeddLink($url)
    {
        return strpos($url, 'youtu.be/') !== false;
    }

    protected function generateExtra($url)
    {
        if ($this->isFullLink($url)) {
            $matches = array();
            preg_match(
                '/[\\?\\&]v=([^\\?\\&]+)/',
                $url,
                $matches
            );
            if (array_key_exists(1, $matches))
                $videoid = $matches[1];
        }

        if ($this->isEmbeddLink($url)) {
            $videoid = substr(strrchr($url, '/' ), 1);
        }

        if (!$videoid) {
            return null;
        }

        $content = file_get_contents("https://gdata.youtube.com/feeds/api/videos/$videoid?v=2");
        $dom = new \DOMDocument();
        $dom->loadXML($content);

        $title = $dom->getElementsByTagName('title');
        $published = $dom->getElementsByTagName('published');
        $updated = $dom->getElementsByTagName('updated');
        $author = $dom->getElementsByTagName('author');

        $parameters = 'rel=0&wmode=opaque';

        return array(
            'videoid' => $videoid,
            'parameters' => $parameters,
            'thumbnails' => array(
                "http://img.youtube.com/vi/$videoid/1.jpg",
                "http://img.youtube.com/vi/$videoid/2.jpg",
                "http://img.youtube.com/vi/$videoid/3.jpg"
            ),
            'default' => "http://img.youtube.com/vi/$videoid/default.jpg",
            'hqdefault' => "http://img.youtube.com/vi/$videoid/hqdefault.jpg",
            'mqdefault' => "http://img.youtube.com/vi/$videoid/mqdefault.jpg",
            'maxresdefault' => "http://img.youtube.com/vi/$videoid/maxresdefault.jpg",
            'published' => $published->item(0)->nodeValue,
            'updated' => $updated->item(0)->nodeValue,
            'title' => $title->item(0)->nodeValue,
            'author' => array(
                'name' => $author->item(0)->childNodes->item(0)->nodeValue,
                'uri' => $author->item(0)->childNodes->item(1)->nodeValue,
                'user_id' => $author->item(0)->childNodes->item(2)->nodeValue
            )
        );
    }

    protected function generateUrl($url, $extra)
    {
        $videoid = $extra['videoid'];
        $parameters = $extra['parameters'];
        $embedurl = 'http://www.youtube.com/embed/'.$videoid.'?'.$parameters;

        return $embedurl;
    }

    public function getName()
    {
        return 'youtube';
    }
}
