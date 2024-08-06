<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Microsoft\Kiota\Authentication\Oauth\AuthorizationCodeContext;
use Microsoft\Graph\Core\Authentication\GraphPhpLeagueAuthenticationProvider;

/**
 * @see https://symfony.com/doc/current/security/custom_authenticator.html
 */
class AccessTokenHandlerAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    /**
     * Called on every request to decide if this authenticator should be
     * used for the request. Returning `false` will cause this authenticator
     * to be skipped.
     */
    public function supports(Request $request): ?bool
    {
        return $request->headers->has('X-AUTH-TOKEN');
    }

    /**
     * Extracts and validates auth token, return user passport
     */
    public function authenticate(Request $request): Passport
    {
        $apiToken = $request->headers->get('X-AUTH-TOKEN');
        if (null === $apiToken) {
        // The token header was empty, authentication fails with HTTP Status
        // Code 401 "Unauthorized"
            throw new CustomUserMessageAuthenticationException('No API token provided');
        }

        // @todo implement your own logic to get the user identifier from `$apiToken`
        // e.g. by looking up a user in the database using its API key
        // $userIdentifier = /** ... */;
        $userEmail = 'abkothman@gmail.com';

        // If user doesn't exist in DB, add new row

        // Return validated passport with UserBadge
        
        return new SelfValidatingPassport(new UserBadge($userEmail));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // on success, let the request continue
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $data = [
            // you may want to customize or obfuscate the message first
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData()),

            // or to translate this message
            // $this->translator->trans($exception->getMessageKey(), $exception->getMessageData())
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        /*
         * If you would like this class to control what happens when an anonymous user accesses a
         * protected page (e.g. redirect to /login), uncomment this method and make this class
         * implement Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface.
         *
         * For more details, see https://symfony.com/doc/current/security/experimental_authenticators.html#configuring-the-authentication-entry-point
         */

        // Check to see if this request contains any OAuth2 info already, since unauthenticated requests
        // get redirected here.

        if ($request->has('code')) {
            // Do post for acess token
            $client = HttpClient::create();
            $response = $client->request('POST', $_ENV['OAUTH2_TOKEN_ENDPOINT'], [
                'body' => [
                    'client_id' => $_ENV['OAUTH2_CLIENT_ID'],
                    'client_secret' => $_ENV['OAUTH2_CLIENT_SECRET'],
                    'grant_type' => 'authorization_code',
                    'code' => $_GET['code'],
                    'redirect_uri' => $_ENV['APP_URL'] . 'auth/callback',
                    'scope' => 'offline_access user.read',
                ],
            ]);
            $statusCode = $response->getStatusCode();
            $content = $response->getContent();
            if ($statusCode != 200) {
                throw new CustomUserMessageAuthenticationException('Invalid authentication: ' . $content);
            }
            $content = $response->toArray();
            // Make sure we got all the expected info
            if ( ! (array_key_exists('access_token', $content) &&
                    array_key_exists('token_type', $content) &&
                    array_key_exists('expires_in', $content) &&
                    array_key_exists('scope', $content) &&
                    array_key_exists('refresh_token', $content) &&
                    array_key_exists('id_token', $content))) {
                throw new CustomUserMessageAuthenticationException('Something went wrong: expected resonse data missing from OAuth2 access_token request');
            }
            
        }

        // Unauthenticated users should be redirected to the Microsoft login
        return new RedirectResponse($this->getOAuth2Endpoint());
    }

    protected function getOAuth2Endpoint(): string {
        return $_ENV['OAUTH2_AUTH_ENDPOINT'] . '?' .
            'client_id=' . $_ENV['OAUTH2_CLIENT_ID'] . '&' .
            'response_type=code&' .
            'redirect_uri=' . urlencode($_ENV['APP_URL'] . '/auth') . '&' .
            'response_mode=query&' .
            '&scope=offline_access%20user.read&' .
            '&state=12345';
    }
}
