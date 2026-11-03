<?php

declare(strict_types=1);

namespace SilverStripe\Discoverer\Tests\Service\Blocklist;

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Discoverer\Service\Blocklist\BlockedKeywordProvider;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Discoverer\Service\Blocklist\ConfigBlockedKeywordProvider;
use SilverStripe\Discoverer\Service\Blocklist\KeywordMatcher;
use SilverStripe\Discoverer\Service\Blocklist\QueryBlocklist;
use SilverStripe\Discoverer\Service\Blocklist\WholeWordKeywordMatcher;

class QueryBlocklistTest extends SapphireTest
{

    protected $usesDatabase = false;

    #[DataProvider('provideQueries')]
    public function testIsBlocked(?string $query, bool $expected): void
    {
        // Ärger is written with a single precomposed character, so queries using a combining character must still match
        ConfigBlockedKeywordProvider::config()->set('blocked_keywords', ['casino', 'Adult Video', "\u{00C4}rger"]);

        $this->assertSame($expected, QueryBlocklist::create()->isBlocked($query));
    }

    public static function provideQueries(): array
    {
        return [
            'keyword on its own' => ['casino', true],
            'keyword among other words' => ['best online casino today', true],
            'different case' => ['Online CASINO', true],
            'surrounded by punctuation' => ['win at "casino"!', true],
            'phrase' => ['free adult video clips', true],
            'phrase with extra spacing and punctuation' => ['adult -- video', true],
            'non-latin keyword' => ['kein ärger hier', true],
            'keyword inside a longer word' => ['casinos nearby', false],
            'keyword prefix of a word' => ['casinoroyale', false],
            'phrase words not together' => ['adult education video', false],
            'unrelated query' => ['library opening hours', false],
            'empty query' => ['', false],
            'null query' => [null, false],
            'punctuation only' => ['!!!', false],
            'accent typed as a combining character' => ["kein a\u{0308}rger", true],
        ];
    }

    public function testCombiningCharacterDoesNotSplitWord(): void
    {
        ConfigBlockedKeywordProvider::config()->set('blocked_keywords', ['sume']);

        // "résumé" typed with combining accents must stay one word, not become "re sume"
        $this->assertFalse(QueryBlocklist::create()->isBlocked("re\u{0301}sume\u{0301}"));
    }

    public function testNothingIsBlockedWithoutKeywords(): void
    {
        ConfigBlockedKeywordProvider::config()->set('blocked_keywords', []);

        $this->assertFalse(QueryBlocklist::create()->isBlocked('casino'));
    }

    public function testEmptyAndNonStringKeywordsAreIgnored(): void
    {
        ConfigBlockedKeywordProvider::config()->set('blocked_keywords', ['', '   ', '!!!', 123, null]);

        $blocklist = QueryBlocklist::create();

        $this->assertFalse($blocklist->isBlocked('anything at all'));
        $this->assertFalse($blocklist->isBlocked('123'));
    }

    public function testProviderFailureAllowsQueryAndLogsWarning(): void
    {
        $handler = new TestHandler();
        $provider = new class implements BlockedKeywordProvider {

            public function getBlockedKeywords(): array
            {
                throw new RuntimeException('Keyword source unavailable');
            }

        };

        $blocklist = QueryBlocklist::create();
        $blocklist->setKeywordProvider($provider);
        $blocklist->setLogger(new Logger('test', [$handler]));

        $this->assertFalse($blocklist->isBlocked('casino'));
        $this->assertTrue($handler->hasWarningThatContains('Keyword source unavailable'));
    }

    public function testMatcherFailureAllowsQueryAndLogsWarning(): void
    {
        ConfigBlockedKeywordProvider::config()->set('blocked_keywords', ['casino']);
        $handler = new TestHandler();
        $matcher = new class implements KeywordMatcher {

            public function findMatch(string $queryString, array $keywords): ?string
            {
                throw new RuntimeException('Matcher failed');
            }

        };

        $blocklist = QueryBlocklist::create();
        $blocklist->setKeywordMatcher($matcher);
        $blocklist->setLogger(new Logger('test', [$handler]));

        $this->assertFalse($blocklist->isBlocked('casino'));
        $this->assertTrue($handler->hasWarningThatContains('Matcher failed'));
    }

    public function testDefaultMatcherIsWholeWord(): void
    {
        $this->assertInstanceOf(WholeWordKeywordMatcher::class, Injector::inst()->get(KeywordMatcher::class));
    }

    public function testMatcherCanBeReplaced(): void
    {
        ConfigBlockedKeywordProvider::config()->set('blocked_keywords', ['赌场']);
        $matcher = new class implements KeywordMatcher {

            public function findMatch(string $queryString, array $keywords): ?string
            {
                foreach ($keywords as $keyword) {
                    if (str_contains($queryString, $keyword)) {
                        return $keyword;
                    }
                }

                return null;
            }

        };
        Injector::inst()->registerService($matcher, KeywordMatcher::class);

        $this->assertTrue(QueryBlocklist::create()->isBlocked('在线赌场'));
        $this->assertFalse(QueryBlocklist::create()->isBlocked('图书馆'));
    }

    public function testBlockedQueryIsLoggedWithKeywordOnly(): void
    {
        ConfigBlockedKeywordProvider::config()->set('blocked_keywords', ['casino']);
        $handler = new TestHandler();

        $blocklist = QueryBlocklist::create();
        $blocklist->setLogger(new Logger('test', [$handler]));
        $blocklist->isBlocked('my email is someone@example.com casino');

        $records = $handler->getRecords();

        $this->assertCount(1, $records);
        $this->assertSame(['keyword' => 'casino'], $records[0]->context);
    }

}
