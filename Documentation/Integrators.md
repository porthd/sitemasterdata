# Integrator Guide: sitemetadata

## Requirements

| Requirement    | Version   |
|----------------|-----------|
| TYPO3          | ^14.0     |
| PHP            | ^8.2      |
| Required Set   | `typo3/theme-camino` (must be loaded before `porthd/sitemetadata`) |

---

## Installation

The extension lives as a local Composer package in the monorepo under `packages/sitemetadata/`
and is already registered as a path repository in the root `composer.json`.

```bash
composer require porthd/sitemetadata
vendor/bin/typo3 cache:flush
```

---

## Activating the Site Set

The Site Set `porthd/sitemetadata` is activated per site in the site configuration.
Open `config/sites/<your-site>/config.yaml` and add the dependency:

```yaml
dependencies:
  - typo3/theme-camino
  - porthd/sitemetadata
```

After that, the master data fields are available in the backend under
**Sites → \<Site\> → Site Sets → Site Metadata**.

---

## Maintaining Master Data

1. TYPO3 backend → **Sites** → select the desired site.
2. Tab **Site Sets** → expand **Site Metadata**.
3. Fill in the fields and save.

The values are stored in `config/sites/<site>/config.yaml` under `settings:`, e.g.:

```yaml
settings:
  sitemetadataOwner: 'Example GmbH'
  sitemetadataPhone: '+49 89 123456'
  sitemetadataEmail: 'info@example.com'
```

---

## RTE Preset

The Site Set registers the preset `sitemetadata_default` and activates it as the default RTE
preset via `page.tsconfig`:

```
RTE.default.preset = sitemetadata_default
```

The preset extends `EXT:theme_camino/Configuration/RTE/Default.yaml` and adds the placeholder
dropdown button to the toolbar.

To restrict the preset to specific fields or tables, use PageTSconfig:

```
RTE.config.tt_content.bodytext.preset = sitemetadata_default
```

---

## Frontend Rendering

Placeholders in RTE content are replaced via a `postUserFunc` hook in the
`lib.parseFunc_RTE` pipeline. The TypoScript is included with the Site Set:

```typoscript
lib.parseFunc_RTE.nonTypoTagStdWrap {
    postUserFunc = porthd\sitemetadata\DataProcessing\SiteMetadataProcessor->process
}
```

This hook applies to all text nodes between HTML tags. It only runs when the content contains
a `[[sitemetadata.` substring (early return for performance).

---

## Multisite

Each site activates the Site Set independently. Master data is stored per site — different
sites can have different values.

---

## Extending with Additional Fields

Additional fields can be added to the system **without modifying this extension** by installing
a separate extension that uses Symfony Service Decoration. See `EXT:sitemetadata_extend` for a
fully worked example.

---

## Cache

After changing site settings or configuration files:

```bash
vendor/bin/typo3 cache:flush
```

The CKEditor dropdown shows updated values only after a cache flush and a backend reload.
