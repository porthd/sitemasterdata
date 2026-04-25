<?php

declare(strict_types=1);

namespace porthd\sitemasterdata\Utility;

use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Reads settings.definitions.yaml as the single source of truth.
 * All other classes use this class via constructor injection –
 * never maintain their own constant lists.
 */
class SiteMasterdataDefinitions
{
    private const PREFIX = 'sitemasterdata';

    /**
     * Returns all sitemasterdata definitions as [settingKey => label].
     * Order matches the order in the YAML file.
     *
     * @param string $filePath  Optional path – for unit tests with fixtures.
     * @return array<string, string>
     */
    public function getAll(string $filePath = ''): array
    {
        $path = $filePath !== ''
            ? $filePath
            : ExtensionManagementUtility::extPath('sitemasterdata')
                . 'Configuration/Sets/SiteMasterdata/settings.definitions.yaml';

        if (!is_file($path)) {
            return [];
        }

        $data     = Yaml::parseFile($path);
        $settings = $data['settings'] ?? [];

        $result = [];
        foreach ($settings as $key => $definition) {
            if (str_starts_with((string)$key, self::PREFIX)) {
                $result[(string)$key] = (string)($definition['label'] ?? $key);
            }
        }

        return $result;
    }

    /**
     * Converts a settings key into the placeholder string.
     * Example: 'sitemasterdataOwner' → '[[sitemasterdata.owner]]'
     */
    public function keyToPlaceholder(string $key): string
    {
        $suffix = substr($key, strlen(self::PREFIX));
        return '[[sitemasterdata.' . lcfirst($suffix) . ']]';
    }
}
