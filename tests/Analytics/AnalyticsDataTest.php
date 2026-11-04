<?php

namespace SilverStripe\Discoverer\Tests\Analytics;

use SilverStripe\Core\Environment;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Discoverer\Analytics\AnalyticsData;
use SilverStripe\Discoverer\Analytics\AnalyticsSigner;

class AnalyticsDataTest extends SapphireTest
{

    public function testForTemplate(): void
    {
        $data = AnalyticsData::create();
        $data->setIndexName('our-index-main');
        $data->setQueryString('search string');
        $data->setDocumentId('abc123');
        $data->setRequestId('123abc');

        $expectedData = [
            'indexName' => 'our-index-main',
            'queryString' => 'search string',
            'documentId' => 'abc123',
            'requestId' => '123abc',
        ];
        $expectedParams = [
            '_searchAnalytics' => base64_encode(json_encode($expectedData)),
        ];
        $expectedResult = http_build_query($expectedParams);

        $this->assertEquals($expectedResult, $data->forTemplate());
    }

    public function testForTemplateSignedWhenSecretSet(): void
    {
        $envBackup = Environment::getVariables();
        Environment::setEnv(AnalyticsSigner::ENV_SECRET, 'secret');
        try {
            $data = AnalyticsData::create();
            $data->setIndexName('our-index-main');
            $data->setDocumentId('abc123');
            $data->setRequestId('123abc');

            parse_str($data->forTemplate(), $query);
            $encoded = json_decode(base64_decode($query[AnalyticsData::QUERY_PARAM]), true);

            $this->assertSame(['indexName', 'documentId', 'requestId', 'sig'], array_keys($encoded));
            $this->assertTrue(
                AnalyticsSigner::create()->verify($data->getPayload(), $encoded[AnalyticsData::KEY_SIGNATURE])
            );
        } finally {
            Environment::setVariables($envBackup);
        }
    }

    public function testGetPayloadOmitsEmptyFields(): void
    {
        $data = AnalyticsData::create();
        $data->setIndexName('our-index-main');
        $data->setDocumentId('abc123');

        $this->assertSame(['indexName' => 'our-index-main', 'documentId' => 'abc123'], $data->getPayload());
    }

    public function testForTemplateEmptyData(): void
    {
        $data = AnalyticsData::create();

        $this->assertEquals($data->forTemplate(), '');
    }

    public function testJsonSerialize(): void
    {
        $data = AnalyticsData::create();
        $data->setIndexName('our-index-main');
        $data->setQueryString('search string');
        $data->setDocumentId('abc123');
        $data->setRequestId('123abc');

        $expected = [
            'indexName' => 'our-index-main',
            'queryString' => 'search string',
            'documentId' => 'abc123',
            'requestId' => '123abc',
        ];

        $this->assertEqualsCanonicalizing($expected, $data->jsonSerialize());
    }

    public function testJsonSerializeEmpty(): void
    {
        $data = AnalyticsData::create();

        $expected = [
            'indexName' => null,
            'queryString' => null,
            'documentId' => null,
            'requestId' => null,
        ];

        $this->assertEqualsCanonicalizing($expected, $data->jsonSerialize());
    }

}
