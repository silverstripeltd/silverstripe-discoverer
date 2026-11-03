<?php

declare(strict_types=1);

namespace SilverStripe\Discoverer\Service\Blocklist;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;

/**
 * Default provider: reads the blocked keywords from YAML config, so no database or CMS is needed.
 */
class ConfigBlockedKeywordProvider implements BlockedKeywordProvider
{

    use Configurable;
    use Injectable;

    /**
     * Words or short phrases that stop a search when the query contains them as whole words
     *
     * @config
     */
    private static array $blocked_keywords = [];

    public function getBlockedKeywords(): array
    {
        $keywords = [];

        foreach ((array) $this->config()->get('blocked_keywords') as $keyword) {
            if (!is_string($keyword) || trim($keyword) === '') {
                continue;
            }

            $keywords[] = $keyword;
        }

        return $keywords;
    }

}
