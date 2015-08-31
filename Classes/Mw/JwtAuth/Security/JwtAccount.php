<?php
namespace Mw\JwtAuth\Security;

use TYPO3\Flow\Security\Account;

class JwtAccount extends Account {

	/**
	 * @var \stdClass
	 */
	protected $claims;

	public function setClaims($claims) {
		$this->claims = $claims;
	}

	public function __call($name, $args) {
		if (substr($name, 0, 3) === 'get') {
			$name = lcfirst(substr($name, 3));
			return $this->claims->{$name};
		}

		throw new \BadMethodCallException($name . ' is not callable on this object');
	}

}