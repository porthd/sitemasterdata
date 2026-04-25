# Developer Guide: sitemetadata

## Architecture Overview

```
packages/sitemetadata/
├── Classes/
│   ├── DataProcessing/
│   │   └── SiteMetadataProcessor.php      # Frontend: placeholder → value
│   ├── EventListener/
│   │   └── InjectSiteMetadataPlaceholdersListener.php  # Backend: CKEditor dropdown
│   └── Utility/
│       └── SiteMetadataDefinitions.php    # Single source of truth: reads YAML
├── Configuration/
│   ├── JavaScriptModules.php              # ES module registration
│   ├── RTE/
│   │   └── Default.yaml                  # CKEditor preset
│   ├── Services.yaml                      # Symfony DI / event listener
│   └── Sets/SiteMetadata/
│       ├── config.yaml                    # Set metadata + dependencies
│       ├── page.tsconfig                  # RTE.default.preset
│       ├── settings.definitions.yaml      # ← only place to add new fields
│       └── setup.typoscript               # parseFunc_RTE postUserFunc
└── Resources/Public/JavaScript/
    └── ckeditor5-sitemetadata-plugin.js   # CKEditor 5 plugin (ES module)
```

---

## Single Source of Truth

**`settings.definitions.yaml` is the only file that needs to be updated for new fields.**
All other components read the definitions at runtime via `SiteMetadataDefinitions::getAll()`.

### Adding a field

1. Add an entry to `settings.definitions.yaml`:

```yaml
settings:
  sitemetadataNewField:
    type: string
    default: ''
    label: 'Label for backend form and dropdown'
```

2. Flush the cache — done.

The new field appears automatically:
- in the backend form (Site Settings),
- in the CKEditor dropdown (as `[[sitemetadata.newField]]`),
- in the frontend placeholder replacement.

### Key convention

Setting keys must start with `sitemetadata` (camelCase).
`SiteMetadataDefinitions::keyToPlaceholder()` handles the conversion:

```
sitemetadataOwner          →  [[sitemetadata.owner]]
sitemetadataContactPerson  →  [[sitemetadata.contactPerson]]
```

---

## Class Reference

### `SiteMetadataDefinitions` (Utility)

Reads `settings.definitions.yaml` via `ExtensionManagementUtility::extPath()` and Symfony YAML.
Returns all entries whose key starts with the `sitemetadata` prefix.

```php
// All definitions: ['sitemetadataOwner' => 'Owner / Operator', ...]
SiteMetadataDefinitions::getAll(): array

// Key → placeholder string
SiteMetadataDefinitions::keyToPlaceholder('sitemetadataOwner'): string
// → '[[sitemetadata.owner]]'
```

### `SiteMetadataProcessor` (DataProcessing)

Registered as `stdWrap.postUserFunc` in `lib.parseFunc_RTE.nonTypoTagStdWrap`.
Replaces all known placeholders in the rendered RTE HTML with the values from
`$site->getSettings()`.

Registered via `setup.typoscript` in the Site Set — no manual TypoScript entry required.

Requires the PHP attribute `#[AsAllowedCallable]` on the method (not the class), because
TYPO3 14 checks via `ReflectionMethod::getAttributes()`.

### `InjectSiteMetadataPlaceholdersListener` (EventListener)

Listens to `AfterPrepareConfigurationForEditorEvent` (rte_ckeditor).
Builds the `siteMetadataPlaceholders` array in the CKEditor config entirely from the
definitions. When site settings are available, the current value is appended to the label:

```
Owner / Operator  →  Owner / Operator (Example GmbH)
```

Registered in `Configuration/Services.yaml`.

### `ckeditor5-sitemetadata-plugin.js` (JavaScript)

CKEditor 5 plugin as an ES module. Registers a dropdown button `siteMetadataPlaceholders`
in the ComponentFactory. The placeholder list is read from
`editor.config.get('siteMetadataPlaceholders')` — the array set by the PHP event listener.

On click, the `value` string (`[[sitemetadata.xxx]]`) is inserted at the current cursor position.

---

## Data Flow

### Backend — building the CKEditor dropdown

```
RTE YAML loaded
  → editor.config.siteMetadataPlaceholders = []
AfterPrepareConfigurationForEditorEvent
  → InjectSiteMetadataPlaceholdersListener::__invoke()
  → SiteMetadataDefinitions::getAll()          // reads settings.definitions.yaml
  → $site->getSettings()->get($key)            // current values for labels
  → configuration['siteMetadataPlaceholders'] = [{'label': ..., 'value': ...}, ...]
CKEditor initialised
  → plugin reads siteMetadataPlaceholders
  → dropdown registered
```

### Frontend — replacing placeholders

```
TYPO3 renders RTE content
  → lib.parseFunc_RTE
  → nonTypoTagStdWrap.postUserFunc
  → SiteMetadataProcessor::process()
  → SiteMetadataDefinitions::getAll()          // reads settings.definitions.yaml
  → str_replace([[sitemetadata.xxx]], $value, $content)
  → output with real values
```

---

## Site Set Dependency

The Site Set declares `typo3/theme-camino` as a dependency so that TYPO3's Dependency
Ordering Service loads the Camino set before `porthd/sitemetadata`. This is required because
the RTE preset `sitemetadata_default` imports the Camino preset
(`imports: - resource: 'EXT:theme_camino/...'`).

Without this dependency, alphabetical ordering (`p` < `t`) could load the sets in the wrong
sequence and the Camino preset would override ours.

---

## JavaScript Module Registration

`Configuration/JavaScriptModules.php` registers the plugin as an ES module under the import
specifier `@porthd/sitemetadata/`:

```php
return [
    'dependencies' => ['backend', 'rte-ckeditor'],
    'imports' => [
        '@porthd/sitemetadata/' => [
            'path' => 'EXT:sitemetadata/Resources/Public/JavaScript/',
        ],
    ],
];
```

The RTE YAML references the module via this specifier:

```yaml
importModules:
  - { module: '@porthd/sitemetadata/ckeditor5-sitemetadata-plugin.js',
      exports: ['SiteMetadataPlaceholders'] }
```

> **Note:** After the first `composer install`, TYPO3 must know the `PackageArtifact.php`.
> If `Resources/Public/` only exists after the first artifact build, run `composer install` again.

---

## Using the Extension in Additional RTE Presets

To make the plugin available in a preset other than `sitemetadata_default`, add the
`importModules` and `siteMetadataPlaceholders` configuration to that preset's YAML:

```yaml
editor:
  config:
    importModules:
      - { module: '@porthd/sitemetadata/ckeditor5-sitemetadata-plugin.js',
          exports: ['SiteMetadataPlaceholders'] }
    siteMetadataPlaceholders: []
    toolbar:
      items:
        - siteMetadataPlaceholders
        # … further items
```

The event listener populates `siteMetadataPlaceholders` automatically for every RTE whose
configuration contains that key.

---

## Extending the Field Set from Another Extension

The field list provided by `SiteMetadataDefinitions::getAll()` can be extended **without
modifying this extension**. The recommended approach is **Symfony Service Decoration**.

A separate extension creates a decorator class that extends `SiteMetadataDefinitions`,
overrides `getAll()` to call `parent::getAll()` and merge in additional fields, and registers
itself in `Configuration/Services.yaml`:

```yaml
MyVendor\MyExt\Utility\ExtendedSiteMetadataDefinitions:
  decorates: porthd\sitemetadata\Utility\SiteMetadataDefinitions
```

Because `SiteMetadataProcessor` and `InjectSiteMetadataPlaceholdersListener` both receive
`SiteMetadataDefinitions` via constructor injection, the decorator automatically extends both
the frontend replacement and the CKEditor dropdown.

See `EXT:sitemetadata_extend` for a fully worked example.

### Why `parent::getAll()` instead of `.inner` injection

TYPO3's `CheckExceptionOnInvalidReferenceBehaviorPass` validates all service references before
the container is compiled. At that point, the virtual `.inner` service (which Symfony creates
during compilation) does not yet exist as a named reference, causing a fatal DI error.

Calling `parent::getAll()` via PHP inheritance instead avoids this entirely: the original
implementation is reached through the class hierarchy, not through the DI container.
