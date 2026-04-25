<?php

declare(strict_types=1);

namespace porthd\sitemasterdata\DataProcessing;

use porthd\sitemasterdata\Utility\SiteMasterdataDefinitions;
use TYPO3\CMS\Core\Attribute\AsAllowedCallable;
use TYPO3\CMS\Core\Site\Entity\Site;

/**
 * stdWrap postUserFunc: replaces master data placeholders in RTE content
 * with the real values from the site configuration.
 *
 * Registered via lib.parseFunc_RTE.nonTypoTagStdWrap.postUserFunc
 *
 * Placeholder format: [[sitemasterdata.key]]
 * Definitions: EXT:sitemasterdata/Configuration/Sets/SiteMasterdata/settings.definitions.yaml
 */
class SiteMasterdataProcessor
{
    public function __construct(
        private readonly SiteMasterdataDefinitions $definitions
    ) {}

    /**
     * Called by stdWrap.postUserFunc.
     */
    #[AsAllowedCallable]
    public function process(string $content, array $conf): string
    {
        // Early return if no placeholder is present
        if (!str_contains($content, '[[sitemasterdata.')) {
            return $content;
        }

        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        if ($request === null) {
            return $content;
        }

        $site = $request->getAttribute('site');
        if (!$site instanceof Site) {
            return $content;
        }

        $settings    = $site->getSettings();
        $definitions = $this->definitions->getAll();

        $search  = [];
        $replace = [];
        foreach ($definitions as $settingKey => $label) {
            $search[]  = $this->definitions->keyToPlaceholder($settingKey);
            $replace[] = (string)($settings->get($settingKey) ?? '');
        }

        return str_replace($search, $replace, $content);
    }
}
