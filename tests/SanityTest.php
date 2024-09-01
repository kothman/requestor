<?php declare(strict_types=1);
namespace Kothman\Requestor;

use Symfony\Component\Dotenv\Dotenv;

final class SanityTest extends TestCase
{
    public function testBootstrapConfiguration(): void
    {
        // Local .env variables
        $env = (new Dotenv())->load(__DIR__.'/../.env');
        $this->assertArrayHasAllKeys(
            ['DATABASE_URL', 'OAUTH2_AUTH_ENDPOINT', 'OAUTH2_TOKEN_ENDPOINT', 'OAUTH2_CLIENT_ID', 'OAUTH2_CLIENT_SECRET', 'APP_URL'],
            $_ENV
        );

        // make sure we can create a new App object
        //        $this->assertInstanceOf(App::class, new App());
    }
}
