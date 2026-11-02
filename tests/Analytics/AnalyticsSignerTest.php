<?php

declare(strict_types=1);

namespace SilverStripe\Discoverer\Tests\Analytics;

use LogicException;
use SilverStripe\Core\Environment;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Discoverer\Analytics\AnalyticsSigner;

class AnalyticsSignerTest extends SapphireTest
{

    protected $usesDatabase = false;

    private array $envBackup = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->envBackup = Environment::getVariables();
    }

    protected function tearDown(): void
    {
        Environment::setVariables($this->envBackup);

        parent::tearDown();
    }

    public function testDisabledWithoutSecret(): void
    {
        Environment::setEnv(AnalyticsSigner::ENV_SECRET, '');
        $signer = AnalyticsSigner::create();

        $this->assertFalse($signer->isEnabled());
        $this->assertFalse($signer->verify(['documentId' => 'doc-1'], 1000, 'anything'));
    }

    public function testSignWithoutSecretThrows(): void
    {
        Environment::setEnv(AnalyticsSigner::ENV_SECRET, '');

        $this->expectException(LogicException::class);

        AnalyticsSigner::create()->sign(['documentId' => 'doc-1'], 1000);
    }

    public function testSignatureVerifies(): void
    {
        Environment::setEnv(AnalyticsSigner::ENV_SECRET, 'secret');
        $signer = AnalyticsSigner::create();
        $payload = ['indexName' => 'main', 'documentId' => 'doc-1', 'requestId' => 'request-1'];

        $signature = $signer->sign($payload, 1000);

        $this->assertTrue($signer->verify($payload, 1000, $signature));
    }

    public function testSignatureIsIndependentOfKeyOrder(): void
    {
        Environment::setEnv(AnalyticsSigner::ENV_SECRET, 'secret');
        $signer = AnalyticsSigner::create();

        $this->assertSame(
            $signer->sign(['documentId' => 'doc-1', 'indexName' => 'main'], 1000),
            $signer->sign(['indexName' => 'main', 'documentId' => 'doc-1'], 1000)
        );
    }

    public function testSignatureRejectsChanges(): void
    {
        Environment::setEnv(AnalyticsSigner::ENV_SECRET, 'secret');
        $signer = AnalyticsSigner::create();
        $payload = ['documentId' => 'doc-1'];
        $signature = $signer->sign($payload, 1000);

        $this->assertFalse($signer->verify(['documentId' => 'doc-2'], 1000, $signature));
        $this->assertFalse($signer->verify($payload, 1001, $signature));

        Environment::setEnv(AnalyticsSigner::ENV_SECRET, 'rotated');

        $this->assertFalse($signer->verify($payload, 1000, $signature));
    }

}
