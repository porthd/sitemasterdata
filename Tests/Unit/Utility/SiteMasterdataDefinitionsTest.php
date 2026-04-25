<?php

declare(strict_types=1);

namespace porthd\sitemasterdata\Tests\Unit\Utility;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use porthd\sitemasterdata\Utility\SiteMasterdataDefinitions;

class SiteMasterdataDefinitionsTest extends TestCase
{
    private SiteMasterdataDefinitions $subject;

    private string $fixtureFile;

    protected function setUp(): void
    {
        $this->subject     = new SiteMasterdataDefinitions();
        $this->fixtureFile = __DIR__ . '/../Fixtures/settings.definitions.yaml';
    }

    // -------------------------------------------------------------------------
    // getAll()
    // -------------------------------------------------------------------------

    #[Test]
    public function getAllReturnsEmptyArrayIfFileDoesNotExist(): void
    {
        $result = $this->subject->getAll('/non/existent/path.yaml');

        self::assertSame([], $result);
    }

    #[Test]
    public function getAllReturnsKeyLabelMapForSitemasterdataKeys(): void
    {
        $result = $this->subject->getAll($this->fixtureFile);

        self::assertSame(
            [
                'sitemasterdataOwner' => 'Inhaber / Betreiber',
                'sitemasterdataPhone' => 'Telefon',
                'sitemasterdataEmail' => 'E-Mail',
            ],
            $result
        );
    }

    #[Test]
    public function getAllFiltersOutKeysWithoutSitemasterdataPrefix(): void
    {
        $result = $this->subject->getAll($this->fixtureFile);

        self::assertArrayNotHasKey('unrelatedKey', $result);
    }

    #[Test]
    public function getAllPreservesOrderFromYamlFile(): void
    {
        $result = $this->subject->getAll($this->fixtureFile);

        self::assertSame(
            ['sitemasterdataOwner', 'sitemasterdataPhone', 'sitemasterdataEmail'],
            array_keys($result)
        );
    }

    // -------------------------------------------------------------------------
    // keyToPlaceholder()
    // -------------------------------------------------------------------------

    #[Test]
    #[DataProvider('keyToPlaceholderDataProvider')]
    public function keyToPlaceholderConvertsKeyCorrectly(string $key, string $expected): void
    {
        self::assertSame($expected, $this->subject->keyToPlaceholder($key));
    }

    public static function keyToPlaceholderDataProvider(): array
    {
        return [
            'simple key'               => ['sitemasterdataOwner',                  '[[sitemasterdata.owner]]'],
            'two-part key'             => ['sitemasterdataContactPerson',           '[[sitemasterdata.contactPerson]]'],
            'three-part key'           => ['sitemasterdataYouthProtectionOfficer',  '[[sitemasterdata.youthProtectionOfficer]]'],
            'short key'                => ['sitemasterdataPhone',                   '[[sitemasterdata.phone]]'],
            'suffix starts upper-case' => ['sitemasterdataEmail',                   '[[sitemasterdata.email]]'],
        ];
    }
}
