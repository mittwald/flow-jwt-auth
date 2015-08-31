<?php
namespace Mw\JwtAuth\Security;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cache\Frontend\FrontendInterface;

/**
 * @package Mw\JwtAuth
 * @subpackage Security
 *
 * @Flow\Scope("singleton")
 */
class KeyProvider {

	/**
	 * @var string
	 * @Flow\Inject(setting="security.keyUrl")
	 */
	protected $keyUrl;

	/**
	 * @var string
	 * @Flow\Inject(setting="security.key")
	 */
	protected $key;

	/**
	 * @var FrontendInterface
	 * @Flow\Inject
	 */
	protected $cache;

	/**
	 * @return string
	 */
	public function getPublicKey() {
		if ($this->key) {
			return $this->key;
		}

		$cacheKey = sha1($this->keyUrl);
		if ($this->cache->has($cacheKey)) {
			return $this->cache->get($cacheKey);
		}

		$key = file_get_contents($this->keyUrl);
		$this->cache->set($cacheKey, $key);

		return $key;
	}
}