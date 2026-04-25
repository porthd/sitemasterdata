<?php

declare(strict_types=1);

namespace porthd\sitemasterdata\Tests\Unit\EventListener;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use porthd\sitemasterdata\EventListener\InjectSiteMasterdataPlaceholdersListener;
use porthd\sitemasterdata\Utility\SiteMasterdataDefinitions;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteSettings;
use TYPO3\CMS\RteCKEditor\Form\Element\Event\AfterPrepareConfigurationForEditorEvent;

class InjectSiteMasterdataPlaceholdersListenerTest extends TestCase
{
    /** @var SiteMasterdataDefinitions&MockObject */
    private SiteMasterdataDefinitions $definitions;

    private InjectSiteMasterdataPlaceholdersListener $subject;

    protected function setUp(): void
    {
        $this->definitions = $this->createMock(SiteMasterdataDefinitions::class);
        $this->subject     = new InjectSiteMasterdataPlaceholdersListener($this->definitions);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_REQUEST'], $GLOBALS['LANG']);
    }

    // -------------------------------------------------------------------------
    // Early return
    // -------------------------------------------------------------------------

    #[Test]
    public function invokeReturnsEarlyIfDefinitionsAreEmpty(): void
    {
        $this->definitions->method('getAll')->willReturn([]);

        $event = $this->createEvent(['siteMasterdataPlaceholders' => [], 'other' => 'value']);
        ($this->subject)($event);

        self::assertSame(
            ['siteMasterdataPlaceholders' => [], 'other' => 'value'],
            $event->getConfiguration()
        );
    }

    // -------------------------------------------------------------------------
    // Building the placeholder list
    // -------------------------------------------------------------------------

    #[Test]
    public function invokeSetsPlaceholderListFromDefinitionsWithoutSite(): void
    {
        unset($GLOBALS['TYPO3_REQUEST']);

        $this->definitions->method('getAll')->willReturn([
            'sitemasterdataOwner' => 'Owner / Operator',
            'sitemasterdataPhone' => 'Phone',
        ]);
        $this->definitions->method('keyToPlaceholder')->willReturnMap([
            ['sitemasterdataOwner', '[[sitemasterdata.owner]]'],
            ['sitemasterdataPhone', '[[sitemasterdata.phone]]'],
        ]);

        $event = $this->createEvent([]);
        ($this->subject)($event);

        self::assertSame(
            [
                ['label' => 'Owner / Operator', 'value' => '[[sitemasterdata.owner]]'],
                ['label' => 'Phone',             'value' => '[[sitemasterdata.phone]]'],
            ],
            $event->getConfiguration()['siteMasterdataPlaceholders']
        );
    }

    #[Test]
    public function invokeEnrichesLabelsWithCurrentSettingValues(): void
    {
        $this->setupRequestWithSettings([
            'sitemasterdataOwner' => 'Example GmbH',
            'sitemasterdataPhone' => '+49 89 123456',
        ]);

        $this->definitions->method('getAll')->willReturn([
            'sitemasterdataOwner' => 'Owner / Operator',
            'sitemasterdataPhone' => 'Phone',
        ]);
        $this->definitions->method('keyToPlaceholder')->willReturnMap([
            ['sitemasterdataOwner', '[[sitemasterdata.owner]]'],
            ['sitemasterdataPhone', '[[sitemasterdata.phone]]'],
        ]);

        $event = $this->createEvent([]);
        ($this->subject)($event);

        $placeholders = $event->getConfiguration()['siteMasterdataPlaceholders'];

        self::assertSame('Owner / Operator (Example GmbH)', $placeholders[0]['label']);
        self::assertSame('Phone (+49 89 123456)',            $placeholders[1]['label']);
    }

    #[Test]
    public function invokeDoesNotEnrichLabelIfSettingValueIsEmpty(): void
    {
        $this->setupRequestWithSettings(['sitemasterdataOwner' => '']);

        $this->definitions->method('getAll')->willReturn(['sitemasterdataOwner' => 'Owner / Operator']);
        $this->definitions->method('keyToPlaceholder')->willReturn('[[sitemasterdata.owner]]');

        $event = $this->createEvent([]);
        ($this->subject)($event);

        $placeholders = $event->getConfiguration()['siteMasterdataPlaceholders'];

        self::assertSame('Owner / Operator', $placeholders[0]['label']);
    }

    #[Test]
    public function invokePreservesOtherConfigurationKeys(): void
    {
        unset($GLOBALS['TYPO3_REQUEST']);

        $this->definitions->method('getAll')->willReturn(['sitemasterdataOwner' => 'Owner']);
        $this->definitions->method('keyToPlaceholder')->willReturn('[[sitemasterdata.owner]]');

        $event = $this->createEvent(['toolbar' => ['bold', 'italic'], 'language' => 'de']);
        ($this->subject)($event);

        $config = $event->getConfiguration();
        self::assertSame(['bold', 'italic'], $config['toolbar']);
        self::assertSame('de',              $config['language']);
    }

    #[Test]
    public function invokeSetsPlaceholderValuesCorrectly(): void
    {
        unset($GLOBALS['TYPO3_REQUEST']);

        $this->definitions->method('getAll')->willReturn(['sitemasterdataOwner' => 'Owner']);
        $this->definitions->method('keyToPlaceholder')
            ->with('sitemasterdataOwner')
            ->willReturn('[[sitemasterdata.owner]]');

        $event = $this->createEvent([]);
        ($this->subject)($event);

        $placeholders = $event->getConfiguration()['siteMasterdataPlaceholders'];

        self::assertSame('[[sitemasterdata.owner]]', $placeholders[0]['value']);
    }

    // -------------------------------------------------------------------------
    // Translation
    // -------------------------------------------------------------------------

    #[Test]
    public function invokeSetsDropdownLabelAndTooltipFromLanguageService(): void
    {
        unset($GLOBALS['TYPO3_REQUEST']);

        $languageService = $this->createMock(LanguageService::class);
        $languageService->method('sL')->willReturnMap([
            ['LLL:EXT:sitemasterdata/Resources/Private/Language/locallang.xlf:dropdown.label',   'Platzhalter'],
            ['LLL:EXT:sitemasterdata/Resources/Private/Language/locallang.xlf:dropdown.tooltip', 'Stammdaten-Platzhalter einfügen'],
        ]);
        $GLOBALS['LANG'] = $languageService;

        $this->definitions->method('getAll')->willReturn(['sitemasterdataOwner' => 'Owner']);
        $this->definitions->method('keyToPlaceholder')->willReturn('[[sitemasterdata.owner]]');

        $event = $this->createEvent([]);
        ($this->subject)($event);

        $config = $event->getConfiguration();
        self::assertSame('Platzhalter',                    $config['siteMasterdataDropdownLabel']);
        self::assertSame('Stammdaten-Platzhalter einfügen', $config['siteMasterdataDropdownTooltip']);
    }

    #[Test]
    public function invokeFallsBackToRawLabelIfLllReferenceCannotBeResolved(): void
    {
        unset($GLOBALS['TYPO3_REQUEST']);

        $languageService = $this->createMock(LanguageService::class);
        $languageService->method('sL')->willReturn('');
        $GLOBALS['LANG'] = $languageService;

        $lllRef = 'LLL:EXT:sitemasterdata/Resources/Private/Language/locallang.xlf:setting.owner';
        $this->definitions->method('getAll')->willReturn(['sitemasterdataOwner' => $lllRef]);
        $this->definitions->method('keyToPlaceholder')->willReturn('[[sitemasterdata.owner]]');

        $event = $this->createEvent([]);
        ($this->subject)($event);

        $placeholders = $event->getConfiguration()['siteMasterdataPlaceholders'];
        self::assertSame($lllRef, $placeholders[0]['label']);
    }

    #[Test]
    public function invokeFallsBackToRawLabelIfLanguageServiceIsUnavailable(): void
    {
        unset($GLOBALS['TYPO3_REQUEST'], $GLOBALS['LANG']);

        $lllRef = 'LLL:EXT:sitemasterdata/Resources/Private/Language/locallang.xlf:setting.owner';
        $this->definitions->method('getAll')->willReturn(['sitemasterdataOwner' => $lllRef]);
        $this->definitions->method('keyToPlaceholder')->willReturn('[[sitemasterdata.owner]]');

        $event = $this->createEvent([]);
        ($this->subject)($event);

        $placeholders = $event->getConfiguration()['siteMasterdataPlaceholders'];
        self::assertSame($lllRef, $placeholders[0]['label']);
    }

    #[Test]
    public function invokeSetsDropdownLabelToFallbackIfLanguageServiceIsUnavailable(): void
    {
        unset($GLOBALS['TYPO3_REQUEST'], $GLOBALS['LANG']);

        $this->definitions->method('getAll')->willReturn(['sitemasterdataOwner' => 'Owner']);
        $this->definitions->method('keyToPlaceholder')->willReturn('[[sitemasterdata.owner]]');

        $event = $this->createEvent([]);
        ($this->subject)($event);

        $config = $event->getConfiguration();
        self::assertStringStartsWith('LLL:', $config['siteMasterdataDropdownLabel']);
        self::assertStringStartsWith('LLL:', $config['siteMasterdataDropdownTooltip']);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * @param array<string, mixed> $configuration
     */
    private function createEvent(array $configuration): AfterPrepareConfigurationForEditorEvent
    {
        return new AfterPrepareConfigurationForEditorEvent($configuration, []);
    }

    /**
     * @param array<string, string> $settingValues
     */
    private function setupRequestWithSettings(array $settingValues): void
    {
        $settings = $this->createMock(SiteSettings::class);
        $settings->method('get')->willReturnCallback(
            static fn(string $key) => $settingValues[$key] ?? null
        );

        $site = $this->createMock(Site::class);
        $site->method('getSettings')->willReturn($settings);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->with('site')->willReturn($site);

        $GLOBALS['TYPO3_REQUEST'] = $request;
    }
}
