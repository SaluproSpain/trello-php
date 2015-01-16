<?php namespace Trello;

use League\OAuth1\Client\Server\Server;
use League\OAuth1\Client\Credentials\CredentialsInterface;
use League\OAuth1\Client\Server\Trello as TrelloServer;

/**
 * Handles authorization tasks for trello
 *
 * @package    Trello
 * @subpackage Authorization
 * @copyright  2014 Steven Maguire
 */
abstract class Authorization extends Trello
{
    /**
     * Permission scope for authorization
     *
     * @var string
     */
    protected static $scope = null;


    /**
     * Authorize application
     *
     * First part of OAuth 1.0 authentication is retrieving temporary
     * credentials. These identify you as a client to the server. Store the
     * credentials in the session. Second part of OAuth 1.0 authentication is
     * to redirect the resource owner to the login screen on the server.
     *
     * @param  League\OAuth1\Client\Server\Server $server
     *
     * Redirects to authorization url to fetch authorization
     */
    public static function authorize(Server $server)
    {
        $session_key = self::getCredentialSessionKey();
        $temporaryCredentials = $server->getTemporaryCredentials();
        $_SESSION[$session_key] = serialize($temporaryCredentials);
        session_write_close();
        $server->authorize($temporaryCredentials);
    }

    /**
     * Get authorization link
     *
     * @return string
     */
    public static function getAuthorizationUrl($expiration = null)
    {
        $config = [
            'key' => Configuration::key(),
            'name' => Configuration::applicationName(),
            'response_type' => 'token',
            'expiration' => self::parseExpiration($expiration),
            'scope' => static::$scope
        ];
        return self::getBasePath().'?'.Util::buildQueryStringFromArray($config);
    }

    /**
     * Get authorization base url
     *
     * @return string Base url
     */
    protected static function getBasePath()
    {
        return 'https://trello.com'.Configuration::versionPath().'/authorize';
    }

    /**
     * Get the key for temporary credentials stored in session
     *
     * @return string
     */
    public static function getCredentialSessionKey()
    {
        return get_class().':temporary_credentials';
    }

    /**
     * Get OAuth server implementation
     *
     * @param  string $expiration
     *
     * @return Stevenmaguire\OAuth1\Client\Server\Trello
     */
    public static function getOAuthServer($expiration = null)
    {
        return new TrelloServer(array(
            'identifier' => Configuration::key(),
            'secret' => Configuration::secret(),
            'callback_uri' => Configuration::oauthCallbackUrl(),
            'name' => Configuration::applicationName(),
            'expiration' => self::parseExpiration($expiration),
            'scope' => static::$scope
        ));
    }

    /**
     * Verify and fetch token
     *
     * Retrieve the temporary credentials from step 2. Third and final part to
     * OAuth 1.0 authentication is to retrieve token credentials (formally
     * known as access tokens in earlier OAuth 1.0 specs). Now, we'll store
     * the token credentials and discard the temporary ones - they're
     * irrelevant at this stage.
     *
     * @param  League\OAuth1\Client\Server\Server $server
     * @param  string $oauth_token
     * @param  string $oauth_verifier
     *
     * @return CredentialsInterface
     */
    public static function getToken(Server $server, $oauth_token, $oauth_verifier)
    {
        $session_key = self::getCredentialSessionKey();
        $temporaryCredentials = unserialize($_SESSION[$session_key]);
        $tokenCredentials = $server->getTokenCredentials(
            $temporaryCredentials,
            $oauth_token,
            $oauth_verifier
        );
        unset($_SESSION[$session_key]);
        session_write_close();
        return serialize($tokenCredentials);
    }

    /**
     * Parse expiration value for fixed Trello values
     *
     * @param  string $expiration
     *
     * @return string|null
     */
    protected static function parseExpiration($expiration)
    {
        if (is_numeric($expiration)) {
            $expiration = round(abs($expiration), 0, PHP_ROUND_HALF_UP);
            return $expiration.'day'.($expiration == 1 ? '' : 's');
        }

        return $expiration;
    }
}
