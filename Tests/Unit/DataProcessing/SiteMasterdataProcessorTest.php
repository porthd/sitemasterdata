<?php

declare(strict_types=1);

namespace porthd\sitemasterdata\Tests\Unit\DataProcessing;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use porthd\sitemasterdata\DataProcessing\SiteMasterdataProcessor;
use porthd\sitemasterdata\Utility\SiteMasterdataDefinitions;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteSettings;

class SiteMasterdataProcessorTest extends TestCase
{
    /** @var SiteMasterdataDefinitions&MockObject */
    private SiteMasterdataDefinitions $definitions;

    private SiteMasterdataProcessor $subject;

    protected function setUp(): void
    {
        $this->definitions = $this->createMock(SiteMasterdataDefinitions::class);
        $this->subject     = new SiteMasterdataProcessor($this->definitions);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_REQUEST']);
    }

    // -------------------------------------------------------------------------
    // Early returns
    // -------------------------------------------------------------------------

    #[Test]
    public function processReturnsContentUnchangedIfNoPlaceholderPresent(): void
    {
        $this->definitions->expects(self::never())->method('getAll');

        $result = $this->subject->process('No placeholder here.', []);

        self::assertSame('No placeholder here.', $result);
    }

    #[Test]
    public function processReturnsContentUnchangedIfNoRequestGlobal(): void
    {
        unset($GLOBALS['TYPO3_REQUEST']);
        $this->definitions->expects(self::never())->method('getAll');

        $result = $this->subject->process('Value: [[sitemasterdata.owner]]', []);

        self::assertSame('Value: [[sitemasterdata.owner]]', $result);
    }

    #[Test]
    public function processReturnsContentUnchangedIfRequestHasNoSiteAttribute(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->with('site')->willReturn(null);
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $this->definitions->expects(self::never())->method('getAll');

        $result = $this->subject->process('Value: [[sitemasterdata.owner]]', []);

        self::assertSame('Value: [[sitemasterdata.owner]]', $result);
    }

    // -------------------------------------------------------------------------
    // Replacement logic
    // -------------------------------------------------------------------------

    #[Test]
    public function processReplacesSinglePlaceholderWithSettingValue(): void
    {
        $this->setupRequestWithSettings(['sitemasterdataOwner' => 'Example GmbH']);

        $this->definitions->method('getAll')->willReturn(['sitemasterdataOwner' => 'Owner']);
        $this->definitions->method('keyToPlaceholder')
            ->with('sitemasterdataOwner')
            ->willReturn('[[sitemasterdata.owner]]');

        $result = $this->subject->process('Owner: [[sitemasterdata.owner]]', []);

        self::assertSame('Owner: Example GmbH', $result);
    }

    #[Test]
    public function processReplacesMultiplePlaceholders(): void
    {
        $this->setupRequestWithSettings([
            'sitemasterdataOwner' => 'Example GmbH',
            'sitemasterdataPhone' => '+49 89 123456',
        ]);

        $this->definitions->method('getAll')->willReturn([
            'sitemasterdataOwner' => 'Owner',
            'sitemasterdataPhone' => 'Phone',
        ]);
        $this->definitions->method('keyToPlaceholder')->willReturnMap([
            ['sitemasterdataOwner', '[[sitemasterdata.owner]]'],
            ['sitemasterdataPhone', '[[sitemasterdata.phone]]'],
        ]);

        $result = $this->subject->process(
            '[[sitemasterdata.owner]], Tel. [[sitemasterdata.phone]]',
            []
        );

        self::assertSame('Example GmbH, Tel. +49 89 123456', $result);
    }

    #[Test]
    public function processReplacesPlaceholderWithEmptyStringIfSettingIsEmpty(): void
    {
        $this->setupRequestWithSettings(['sitemasterdataOwner' => '']);

        $this->definitions->method('getAll')->willReturn(['sitemasterdataOwner' => 'Owner']);
        $this->definitions->method('keyToPlaceholder')
            ->with('sitemasterdataOwner')
            ->willReturn('[[sitemasterdata.owner]]');

        $result = $this->subject->process('Owner: [[sitemasterdata.owner]]', []);

        self::assertSame('Owner: ', $result);
    }

    #[Test]
    public function processLeavesUnknownPlaceholderUnchanged(): void
    {
        $this->setupRequestWithSettings([]);

        $this->definitions->method('getAll')->willReturn([]);
        $this->definitions->method('keyToPlaceholder')->willReturn('');

        $result = $this->subject->process('Value: [[sitemasterdata.unknown]]', []);

        self::assertSame('Value: [[sitemasterdata.unknown]]', $result);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

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
