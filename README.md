iBROWS Media Bundle
========================

## Installation

Add the following lines in your composer.json:

```
{
    "require": {
        "ibrows/media-bundle": "dev-master"
    }
}
```

For composer to find the bundle, additionally you need to add the following to the repositories section in your composer.json
```
"repositories": [
	{
        "type": "vcs",
        "url": "git@codebasehq.com:ibrows/ibrowsch/ibrows-media-bundle.git"
    }
],
```

To start using the bundle, register the bundle in your application's kernel class:

``` php
// app/AppKernel.php
public function registerBundles()
{
    $bundles = array(
        // ...
        new Ibrows\MediaBundle\IbrowsMediaBundle(),
    );
)
```

## Configuration

### Entity
```
use Ibrows\MediaBundle\Model\Media as AbstractMedia;
use Doctrine\ORM\Mapping as ORM;

/**
 * Media
 *
 * @ORM\Table(name="media")
 * @ORM\Entity
 */
class Media extends AbstractMedia
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;
    
    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
}
```

Now you can add the Media entity as a relation anywhere you want.


### Options
```
ibrows_media:
    uri_prefix: /uploads
    upload_dir: %kernel.root_dir%/../web/uploads
      
    enabled_types: [youtube, soundcloud, image, file]
    
    image:
        max_width: 560
        max_height: ~
        
        mime_types: [image/jpeg, image/png]
        formats:
            small:
                height: 63
                width: 63
            medium:
                height: 72
                width: 72
            large:
                height: 300
                width: ~
    
    file:
        max_size: ~
        mime_types: ~ 
```

## Usage