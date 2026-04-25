# sitemasterdata — Site Master Data for TYPO3

## What this extension does

Websites regularly display the same company information in many places: the postal
address in the footer, the phone number on the contact page, the data protection
officer in the privacy policy, and so on. If this information changes, every single
occurrence has to be updated manually — a tedious and error-prone task.

**sitemasterdata** solves this by storing master data such as the company name,
address, phone number, e-mail address, and legally required officer roles centrally
in the TYPO3 Site Settings. Editors insert a short placeholder (e.g.
`[[sitemasterdata.phone]]`) anywhere in a text. TYPO3 replaces every placeholder
with the current value automatically when the page is rendered. A phone number that
changes needs to be updated in exactly one place — all pages reflect the new value
immediately.

---

## Features

- Central storage of master data per site in TYPO3 Site Settings
- Placeholder dropdown in the CKEditor toolbar — no manual typing required
- Current values shown as a preview in the dropdown label
- Automatic frontend replacement in all RTE content
- Direct access to individual values in Fluid templates and TypoScript
- Ready-made TypoScript cObject for a complete formatted address block
- Multisite-capable — each site maintains its own independent values
- Extendable with additional fields without modifying this extension

---

## Available fields

| Field | Placeholder |
|---|---|
| Owner / Operator | `[[sitemasterdata.owner]]` |
| Contact Person | `[[sitemasterdata.contactPerson]]` |
| Phone | `[[sitemasterdata.phone]]` |
| Fax | `[[sitemasterdata.fax]]` |
| E-Mail | `[[sitemasterdata.email]]` |
| Street and House Number | `[[sitemasterdata.street]]` |
| Postal Code | `[[sitemasterdata.zip]]` |
| City | `[[sitemasterdata.city]]` |
| Youth Protection Officer | `[[sitemasterdata.youthProtectionOfficer]]` |
| Data Protection Officer | `[[sitemasterdata.dataProtectionOfficer]]` |
| AI Officer | `[[sitemasterdata.aiOfficer]]` |
| Accessibility Officer | `[[sitemasterdata.accessibilityOfficer]]` |

---

## Requirements

| Requirement | Version |
|---|---|
| TYPO3 | ^14.3 |
| PHP | ^8.2 |
| Required Site Set | `typo3/theme-camino` |

---

## Installation

```bash
composer require porthd/sitemasterdata
vendor/bin/typo3 cache:flush
```

Then activate the Site Set `porthd/sitemasterdata` in your site configuration
(`config/sites/<your-site>/config.yaml`):

```yaml
dependencies:
  - typo3/theme-camino
  - porthd/sitemasterdata
```

---

## Extending

Additional fields (e.g. social media profiles, VAT ID, registration number) can be
added from a separate extension without modifying this one. The recommended approach
is Symfony Service Decoration — see
[EXT:sitemasterdata_extend](../sitemasterdata_extend/README.md) for a fully worked
example.

---

## Documentation

- [Editor Guide](Documentation/Editors.md)
- [Integrator Guide](Documentation/Integrators.md) — includes Fluid template and TypoScript examples
- [Developer Guide](Documentation/Developers.md)

---

## License

GNU General Public License v3.0 or later — see [LICENSE.txt](LICENSE.txt).
