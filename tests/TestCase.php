<?php declare(strict_types=1);
namespace Kothman\Requestor;

class TestCase extends \PHPUnit\Framework\TestCase {
    protected function assertArrayHasAllKeys(array $expectedKeys, array $array, string $message = ''): void
    {
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $array, $message);
        }
    }
}
