# Integrator Guide: sitemasterdata

## Requirements

| Requirement    | Version   |
|----------------|-----------|
| TYPO3          | ^14.3     |
| PHP            | ^8.2      |
| Required Set   | `typo3/theme-camino` (must be loaded before `porthd/sitemasterdata`) |

---

## Installation

```bash
composer require porthd/sitemasterdata
vendor/bin/typo3 cache:flush
```

---

## Activating the Site Set

The Site Set `porthd/sitemasterdata` is activated per site in the site configuration.
Open `config/sites/<your-site>/config.yaml` and add the dependency:

```yaml
dependencies:
  - typo3/theme-camino
  - porthd/sitemasterdata
```

After that, the master data fields are available in the backend under
**Sites → \<Site\> → Site Sets → Site Master Data**.

---

## Maintaining Master Data

1. TYPO3 backend → **Sites** → select the desired site.
2. Tab **Site Sets** → expand **Site Master Data**.
3. Fill in the fields and save.

The values are stored in `config/sites/<site>/settings.yaml:`, e.g.:

```yaml
settings:
  sitemasterdataOwner: 'Example GmbH'
  sitemasterdataPhone: '+49 89 123456'
  sitemasterdataEmail: 'info@example.com'
```

---

## RTE Preset

The Site Set registers the preset `sitemasterdata_default` and activates it as the default RTE
preset via `page.tsconfig`:

```
RTE.default.preset = sitemasterdata_default
```

The preset extends `EXT:theme_camino/Configuration/RTE/Default.yaml` and adds the placeholder
dropdown button to the toolbar.

To restrict the preset to specific fields or tables, use PageTSconfig:

```
RTE.config.tt_content.bodytext.preset = sitemasterdata_default
```

---

## Frontend Rendering via RTE Placeholders

Placeholders in RTE content are replaced via a `postUserFunc` hook in the
`lib.parseFunc_RTE` pipeline. The TypoScript is included with the Site Set:

```typoscript
lib.parseFunc_RTE.nonTypoTagStdWrap {
    postUserFunc = porthd\sitemasterdata\DataProcessing\SiteMasterdataProcessor->process
}
```

This hook applies to all text nodes between HTML tags. It only runs when the content
contains a `[[sitemasterdata.` substring (early return for performance).

---

## Accessing Site Settings in Fluid Templates

Site settings can be passed to any Fluid template via the `SiteProcessor` DataProcessor.

### Step 1 — Register the DataProcessor in TypoScript

```typoscript
page.10 = FLUIDTEMPLATE
page.10 {
    dataProcessing {
        10 = TYPO3\CMS\Frontend\DataProcessing\SiteProcessor
        10.as = site
    }
}
```

### Step 2 — Use `{site.settings}` in the template

```html
<p>
    {site.settings.sitemasterdataOwner}<br>
    {site.settings.sitemasterdataStreet}<br>
    {site.settings.sitemasterdataZip} {site.settings.sitemasterdataCity}
</p>

<a href="mailto:{site.settings.sitemasterdataEmail}">
    {site.settings.sitemasterdataEmail}
</a>

<a href="tel:{site.settings.sitemasterdataPhone}">
    {site.settings.sitemasterdataPhone}
</a>
```

The `SiteProcessor` makes the complete site object — including all settings —
available as `{site}` in every template that uses this DataProcessor.

---

## Providing the Full Address as a TypoScript cObject

The `site:` getText key reads individual site settings directly in TypoScript
without a DataProcessor.

### Single value

```typoscript
lib.sitemasterdataOwner = TEXT
lib.sitemasterdataOwner.data = site:sitemasterdataOwner
```

### Complete address block

```typoscript
lib.sitemasterdataAddress = COA
lib.sitemasterdataAddress {
    # Owner / company name
    10 = TEXT
    10.data = site:sitemasterdataOwner
    10.wrap = <span class="address__owner">|</span><br>
    10.required = 1

    # Street
    20 = TEXT
    20.data = site:sitemasterdataStreet
    20.wrap = <span class="address__street">|</span><br>
    20.required = 1

    # ZIP + city on one line
    30 = COA
    30 {
        10 = TEXT
        10.data = site:sitemasterdataZip
        10.wrap = <span class="address__zip">|</span>&nbsp;

        20 = TEXT
        20.data = site:sitemasterdataCity
        20.wrap = <span class="address__city">|</span>
    }
    30.wrap = |<br>

    # Phone
    40 = TEXT
    40.data = site:sitemasterdataPhone
    40.typolink.parameter.data = site:sitemasterdataPhone
    40.typolink.parameter.wrap = tel:|
    40.wrap = <span class="address__phone">|</span><br>
    40.required = 1

    # E-Mail
    50 = TEXT
    50.data = site:sitemasterdataEmail
    50.typolink.parameter.data = site:sitemasterdataEmail
    50.typolink.parameter.wrap = mailto:|
    50.wrap = <span class="address__email">|</span>
    50.required = 1

    stdWrap.wrap = <address class="sitemasterdata-address">|</address>
}
```

Render the cObject in a Fluid template via the `f:cObject` ViewHelper:

```html
<f:cObject typoscriptObjectPath="lib.sitemasterdataAddress" />
```

Or pass it as a variable to a FLUIDTEMPLATE:

```typoscript
page.10.variables.address < lib.sitemasterdataAddress
```

---

## Multisite

Each site activates the Site Set independently. Master data is stored per site —
different sites can have different values.

---

## Extending with Additional Fields

Additional fields can be added to the system **without modifying this extension** by
installing a separate extension that uses Symfony Service Decoration.
See `EXT:sitemasterdata_extend` for a fully worked example.

---

## Cache

After changing site settings or configuration files:

```bash
vendor/bin/typo3 cache:flush
```

The CKEditor dropdown shows updated values only after a cache flush and a backend reload.
