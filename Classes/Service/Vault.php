<?php
namespace Ttree\ContentRepositoryImporter\Service;

use Neos\Cache\Frontend\FrontendInterface;
use Neos\Flow\Annotations as Flow;

class Vault
{
    /**
     * @var FrontendInterface
     * @Flow\Inject
     */
    protected $storage;

    /**
     * @var string
     */
    protected $preset;

    public function __construct($preset)
    {
        $this->preset = (string)$preset;
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function set($key, $value)
    {
        $this->storage->set(md5($this->preset . $key), $value, [$this->preset]);
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        return $this->storage->get(md5($this->preset . $key));
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return $this->storage->has(md5($this->preset . $key));
    }

    /**
     * @return void
     */
    public function flush()
    {
        $this->storage->flushByTag($this->preset);
    }
}
