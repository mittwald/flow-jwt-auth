<?php
namespace Mw\JwtAuth\Security\Authentication;

use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Mw\JwtAuth\Security\Authentication\Token\JsonWebToken;
use Mw\JwtAuth\Security\JwtAccount;
use Mw\JwtAuth\Security\KeyProvider;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Security\Authentication\AuthenticationProviderInterface;
use TYPO3\Flow\Security\Authentication\TokenInterface;
use TYPO3\Flow\Security\Policy\PolicyService;

class JwtAuthenticationProvider implements AuthenticationProviderInterface {

	/**
	 * @var array
	 * @Flow\Inject(setting="security.algorithms")
	 */
	protected $algorithms;

	/**
	 * @var KeyProvider
	 * @Flow\Inject
	 */
	protected $keyProvider;

	/**
	 * @var array
	 * @Flow\Inject(setting="claimMapping")
	 */
	protected $claimMapping;

	/**
	 * @var PolicyService
	 * @Flow\Inject
	 */
	protected $policyService;

	/**
	 * @var SystemLoggerInterface
	 * @Flow\Inject
	 */
	protected $systemLogger;

	/**
	 * Returns TRUE if the given token can be authenticated by this provider
	 *
	 * @param TokenInterface $token The token that should be authenticated
	 * @return boolean TRUE if the given token class can be authenticated by this provider
	 */
	public function canAuthenticate(TokenInterface $token) {
		return ($token instanceof JsonWebToken);
	}

	/**
	 * Returns the classnames of the tokens this provider is responsible for.
	 *
	 * @return array The classname of the token this provider is responsible for
	 */
	public function getTokenClassNames() {
		return [JsonWebToken::class];
	}

	/**
	 * Tries to authenticate the given token. Sets isAuthenticated to TRUE if authentication succeeded.
	 *
	 * @param TokenInterface $authenticationToken The token to be authenticated
	 * @return void
	 */
	public function authenticate(TokenInterface $authenticationToken) {
		if (!$authenticationToken instanceof JsonWebToken) {
			throw new \InvalidArgumentException('$authenticationToken is not an instance of ' . JsonWebToken::class . '!');
		}

		try {
			$encoded = $authenticationToken->getEncodedJwt();
			$claims = JWT::decode($encoded, $this->keyProvider->getPublicKey(), $this->algorithms);
		} catch (ExpiredException $expired) {
			$authenticationToken->setAuthenticationStatus(TokenInterface::WRONG_CREDENTIALS);
			return;
		} catch (\Exception $err) {
			$this->systemLogger->logException($err);
			$authenticationToken->setAuthenticationStatus(TokenInterface::WRONG_CREDENTIALS);
			return;
		}

		$account = new JwtAccount();
		$account->setClaims($claims);
		$account->setAccountIdentifier($claims->sub);
		$account->setAuthenticationProviderName('JwtAuthenticationProvider');

		if (isset($this->claimMapping['roleField']) && $this->claimMapping['roleField'] !== NULL) {
			$rolesClaimName = $this->claimMapping['roleField'];
			$rolesClaim = $claims->{$rolesClaimName};

			if (!is_array($rolesClaim)) {
				$rolesClaim = array($rolesClaim);
			}

			foreach ($rolesClaim as $roleClaim) {
				if (isset($this->claimMapping['roles'][$roleClaim])) {
					$flowRoleName = $this->claimMapping['roles'][$roleClaim];
					$role = $this->policyService->getRole($flowRoleName);
					$account->addRole($role);
				}
			}
		}

		$authenticationToken->setAccount($account);
		$authenticationToken->setAuthenticationStatus(TokenInterface::AUTHENTICATION_SUCCESSFUL);
	}
}