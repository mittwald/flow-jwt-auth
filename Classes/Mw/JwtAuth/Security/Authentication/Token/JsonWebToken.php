<?php
namespace Mw\JwtAuth\Security\Authentication\Token;

use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Security\Authentication\Token\AbstractToken;
use TYPO3\Flow\Security\Authentication\Token\SessionlessTokenInterface;
use TYPO3\Flow\Annotations as Flow;

class JsonWebToken extends AbstractToken implements SessionlessTokenInterface {

	/**
	 * @var array
	 * @Flow\Inject(setting="security.tokenSources")
	 */
	protected $tokenSources;

	/**
	 * Updates the authentication credentials, the authentication manager needs to authenticate this token.
	 * This could be a username/password from a login controller.
	 * This method is called while initializing the security context. By returning TRUE you
	 * make sure that the authentication manager will (re-)authenticate the tokens with the current credentials.
	 * Note: You should not persist the credentials!
	 *
	 * @param ActionRequest $actionRequest The current request instance
	 * @return boolean TRUE if this token needs to be (re-)authenticated
	 */
	public function updateCredentials(ActionRequest $actionRequest) {
		$httpRequest = $actionRequest->getHttpRequest();
		$token = NULL;

		foreach ($this->tokenSources as $tokenSource) {
			$name = $tokenSource['name'];
			if ($tokenSource['from'] == 'header') {
				if ($httpRequest->hasHeader($name)) {
					$token = $httpRequest->getHeader($name);
					break;
				}
			} elseif ($tokenSource['from'] == 'cookie') {
				if ($httpRequest->hasCookie($name)) {
					$token = $httpRequest->getCookie($name);
					break;
				}
			} elseif ($tokenSource['from'] == 'query') {
				if ($httpRequest->hasArgument($name)) {
					$token = $httpRequest->getArgument($name);
					break;
				}
			}
		}

		if (NULL !== $token) {
			$this->credentials['encoded'] = $token;
			$this->setAuthenticationStatus(self::AUTHENTICATION_NEEDED);
			return TRUE;
		}

		$this->setAuthenticationStatus(self::NO_CREDENTIALS_GIVEN);
		return FALSE;
	}

	/**
	 * @return string
	 */
	public function getEncodedJwt() {
		return $this->credentials['encoded'];
	}
}