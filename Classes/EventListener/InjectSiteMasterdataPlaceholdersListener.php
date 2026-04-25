<?php

declare(strict_types=1);

namespace porthd\sitemasterdata\EventListener;

use porthd\sitemasterdata\Utility\SiteMasterdataDefinitions;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\RteCKEditor\Form\Element\Event\AfterPrepareConfigurationForEditorEvent;

/**
 * Dynamically builds the placeholder list for the CKEditor dropdown from
 * settings.definitions.yaml and translates all labels into the current backend language.
 *
 * Existing site settings values are appended to labels as a preview:
 * "Owner / Operator" → "Owner / Operator (Example GmbH)"
 */
class InjectSiteMasterdataPlaceholdersListener
{
    private const LLL_PREFIX = 'LLL:EXT:sitemasterdata/Resources/Private/Language/locallang.xlf:';

    public function __construct(
        private readonly SiteMasterdataDefinitions $definitions
    ) {}

    public function __invoke(AfterPrepareConfigurationForEditorEvent $event): void
    {
        $definitions = $this->definitions->getAll();
        if (empty($definitions)) {
            return;
        }

        // Load site settings for enriched labels (backend request only)
        $request  = $GLOBALS['TYPO3_REQUEST'] ?? null;
        $site     = $request?->getAttribute('site');
        $settings = ($site instanceof Site) ? $site->getSettings() : null;

        $placeholders = [];
        foreach ($definitions as $settingKey => $rawLabel) {
            $label       = $this->translate($rawLabel);
            $placeholder = $this->definitions->keyToPlaceholder($settingKey);

            if ($settings !== null) {
                $currentValue = (string)($settings->get($settingKey) ?? '');
                if ($currentValue !== '') {
                    $label .= ' (' . $currentValue . ')';
                }
            }

            $placeholders[] = ['label' => $label, 'value' => $placeholder];
        }

        $configuration                                = $event->getConfiguration();
        $configuration['siteMasterdataPlaceholders']  = $placeholders;
        $configuration['siteMasterdataDropdownLabel']   = $this->translate(self::LLL_PREFIX . 'dropdown.label');
        $configuration['siteMasterdataDropdownTooltip'] = $this->translate(self::LLL_PREFIX . 'dropdown.tooltip');
        $event->setConfiguration($configuration);
    }

    /**
     * Resolves an LLL reference to the current backend language.
     * Returns plain strings unchanged.
     */
    private function translate(string $label): string
    {
        if (!str_starts_with($label, 'LLL:')) {
            return $label;
        }

        $languageService = $GLOBALS['LANG'] ?? null;
        if ($languageService === null) {
            return $label;
        }

        return $languageService->sL($label) ?: $label;
    }
}
