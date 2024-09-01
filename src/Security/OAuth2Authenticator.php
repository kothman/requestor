<?php
/**
 * src/Controller/OAuth2Authenticator.php
 *
 * Morgan Kothman <abkothman@gmail.com>
 *
 * Handles access controller for protected routes.
 */
namespace Kothman\Requestor;

class OAuth2Authenticator {

    protected array $env;
    protected string $authEndpoint;
    
    public function __construct()
    {
        $this->env = $_ENV;
        $this->authEndpoint = $this->getOAuth2Endpoint();
    }

        /**
     * Extracts and validates auth token
     */
    public function authenticate(Request $request): Response
    {
        if (false === $request->query->has('code')) {
            // Redirect to Microsoft login endpoint if the user isn't authenticated and doesn't have a code
            return new RedirectResponse($this->authEndpoint);
        }

        $accessTokenData = $this->getAccessToken($request->query->get('code'));
        $microsoftUserInfo = $this->requestMicrosoftUserInfo($accessTokenData['access_token']);
        $email = $microsoftUserInfo['mail'];

        // If we have the email at this point, the user is authenticated. Otherwise,
        // an error would have been thrown.
        
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

    protected function requestAccessToken(string $code): array
    {
        // Do post for acess token
        $client = HttpClient::create();
        $response = $client->request('POST', $this->env['OAUTH2_TOKEN_ENDPOINT'], [
            'body' => [
                'client_id' => $this->env['OAUTH2_CLIENT_ID'],
                'client_secret' => $this->env['OAUTH2_CLIENT_SECRET'],
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $this->env['APP_URL'] . '/auth',
                'scope' => 'offline_access user.read',
            ],
        ]);
        $statusCode = $response->getStatusCode();
        if ($statusCode != 200) {
            $errorArray = json_decode($response->getContent(false));
            throw new CustomUserMessageAuthenticationException('Invalid authentication: ' . var_export($errorArray, true));
        }
        $data = json_decode($response->getContent(), true);
        return $data;
    }
        
    protected function verifyAccessToken(array $tokenData): void
    {
        // Make sure we got all the expected info
        if ( ! (array_key_exists('access_token', $tokenData) &&
                array_key_exists('token_type', $tokenData) &&
                array_key_exists('expires_in', $tokenData) &&
                array_key_exists('scope', $tokenData) &&
                array_key_exists('refresh_token', $tokenData) &&
                array_key_exists('ext_expires_in', $tokenData))) {
            throw new CustomUserMessageAuthenticationException('Something went wrong: expected resonse data missing from OAuth2 access_token request.<br>' . var_export($tokenData, true));
        }
    }

    protected function requestMicrosoftUserInfo(string $accessToken): array
    {
        $client = HttpClient::create();
        $response = $client->request('GET', 'https://graph.microsoft.com/v1.0/me', [
            'headers' => ['Authorization' => 'Bearer ' . $accessToken],
        ]);
        $statusCode = $response->getStatusCode();
        if ($statusCode != 200) {
            // Something went wrong requesting the user email with our access token
            throw new CustomUserMessageAuthenticationException('Something went wrong trying to retrieve user email: ' . var_export($response->getContent(false), true));
        }
        $userInfo = json_decode($response->getContent(), true);
        if ( !array_key_exists('mail', $userInfo)) {
            throw new CustomUserMEssageAuthenticationException('Something went wrong trying to retrieve the email from response array.<br>' . $userInfo);
        }
        return $userInfo;
    }
        
    protected function getAccessToken(string $code): array
    {
        $tokenData = $this->requestAccessToken($code);
        $this->verifyAccessToken($tokenData);
        return $tokenData;
            
    }    

    
}
