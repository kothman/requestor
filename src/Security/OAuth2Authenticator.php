<?php
/**
 * src/Security/OAuth2Authenticator.php
 *
 * Morgan Kothman <abkothman@gmail.com>
 *
 * Handles access control for protected routes.
 */
namespace Kothman\Requestor\Security;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

class OAuth2Authenticator {

    protected array $tokenData;
    protected array $userData;
    protected string $authEndpoint;
    protected string $tokenEndpoint;
    
    public function __construct(
        protected Session $session,
        protected Request $request,
        protected array $env
    )
    {
        $this->authEndpoint = $this->getOAuth2Endpoint();
        $this->tokenEndpoint = $this->env['OAUTH2_TOKEN_ENDPOINT'];
    }
    
    /**
     * Extracts and validates auth token
     */
    public function authenticate(): ?Response
    {
        if ( true === $this->request->query->get('code')) {
            // If they have the code, save it and request access token 
            $this->code = $request->query->get('code');
            $this->getAccessToken();
            $this->requestMicrosoftUserInfo();
            // Set the tokenData and userData on the session, so that the user is authenticated during this session,
            // and so this data can be later accesses
            $this->session->set('tokenData', $this->tokenData);
            $this->session->set('userData', $this->userData);

            // Redirect to main page
            return new RedirectResponse('/');
        } else if (false === $this->request->query->get('code') || !$this->isAuthenticated()) {
            // Redirect to Microsoft login endpoint if the user isn't authenticated and doesn't have a code
            return new RedirectResponse($this->authEndpoint);
        } 

        // The user is already authenticated, don't return a redirect response
        return null;
    }

    protected function isAuthenticated(): bool
    {
        
        return (!empty($this->tokenData) && !empty($this->userData));
    }
    
    protected function getOAuth2Endpoint(): string {
        return $this->env['OAUTH2_AUTH_ENDPOINT'] . '?' .
            'client_id=' . $this->env['OAUTH2_CLIENT_ID'] . '&' .
            'response_type=code&' .
            'redirect_uri=' . urlencode($this->env['APP_URL'] . '/auth') . '&' .
            'response_mode=query&' .
            '&scope=offline_access%20user.read&' .
            '&state=12345';
    }
    
    protected function requestAccessToken(): void
    {
        // Do post for access token
        $client = HttpClient::create();
        $response = $client->request(
            'POST',
            $this->tokenEndpoint,
            [ 'body' => [
                'client_id' => $this->env['OAUTH2_CLIENT_ID'],
                'client_secret' => $this->env['OAUTH2_CLIENT_SECRET'],
                'grant_type' => 'authorization_code',
                'code' => $this->code,
                'redirect_uri' => $this->env['APP_URL'] . '/auth',
                'scope' => 'offline_access user.read',
            ]]
        );
        $statusCode = $response->getStatusCode();
        if ($statusCode != 200) {
            $errorArray = json_decode($response->getContent(false));
            throw new CustomUserMessageAuthenticationException('Invalid authentication: ' . var_export($errorArray, true));
        }
        $data = $response->getContent();
        $this->tokenData = $response->toArray();
    }
        
    protected function verifyAccessToken(): void
    {
        // Make sure we got all the expected info
        if ( ! (array_key_exists('access_token', $this->tokenData) &&
                array_key_exists('token_type', $this->tokenData) &&
                array_key_exists('expires_in', $this->tokenData) &&
                array_key_exists('scope', $this->tokenData) &&
                array_key_exists('refresh_token', $this->tokenData) &&
                array_key_exists('ext_expires_in', $this->tokenData))) {
            throw new CustomUserMessageAuthenticationException('Something went wrong: expected resonse data missing from OAuth2 access_token request.<br>' . var_export($this->tokenData, true));
        }
    }

    protected function requestMicrosoftUserInfo(): void
    {
        $client = HttpClient::create();
        $response = $client->request('GET', 'https://graph.microsoft.com/v1.0/me', [
            'headers' => ['Authorization' => 'Bearer ' . $this->tokenData['access_token']],
        ]);
        $statusCode = $response->getStatusCode();
        if ($statusCode != 200) {
            // Something went wrong requesting the user email with our access token
            throw new CustomUserMessageAuthenticationException('Something went wrong trying to retrieve user email: ' . var_export($response->getContent(false), true));
        }
        $userData = $response->toArray();
        if ( !array_key_exists('mail', $userData)) {
            throw new CustomUserMEssageAuthenticationException('Something went wrong trying to retrieve the email from response array.<br>' . $userData);
        }
        $this->userData = $userData;
    }
        
    protected function getAccessToken(): void
    {
        $this->requestAccessToken();
        $this->verifyAccessToken();
    }
    
}
