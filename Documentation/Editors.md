# Editor Guide: Master Data Placeholders

## What is this?

The **sitemetadata** extension makes central master data of the website — such as the owner,
phone number, or data protection officer — available as placeholders in the text editor
(CKEditor).

When you insert a placeholder into a text, it is automatically replaced by the real value when
the page is rendered in the frontend. If a value changes (e.g. a new phone number), it only
needs to be updated in one place — all texts across the entire website are immediately up to date.

---

## Inserting a Placeholder

1. Open a content element with a text field (RTE) in the backend.
2. Place the cursor where the value should appear.
3. Click the **"Master Data"** button in the toolbar.
4. Select the desired entry from the dropdown.

The placeholder is inserted as text, e.g.:

```
[[sitemetadata.owner]]
```

In the frontend the stored value is displayed, e.g. `Example GmbH`.

### Preview in the Dropdown

If master data has already been entered in the site configuration, the dropdown shows the
current value in parentheses:

```
Company / Owner (Example GmbH)
```

If no value has been stored yet, only the label is shown:

```
Owner / Operator
```

---

## Available Placeholders

| Label                      | Placeholder                                      |
|----------------------------|--------------------------------------------------|
| Owner / Operator           | `[[sitemetadata.owner]]`                         |
| Phone                      | `[[sitemetadata.phone]]`                         |
| E-Mail                     | `[[sitemetadata.email]]`                         |
| Street and House Number    | `[[sitemetadata.street]]`                        |
| Postal Code                | `[[sitemetadata.zip]]`                           |
| City                       | `[[sitemetadata.city]]`                          |

> The complete list depends on the fields configured for your project and may include
> additional entries.

---

## Important Notes

- **Never type placeholders manually.** Always use the dropdown to ensure correct spelling.
- **Visible in the editor, replaced in the frontend.** In the backend you see the placeholder
  text `[[sitemetadata.owner]]`. On the website the real value is displayed.
- **No value stored?** If no value has been entered in the site configuration for a placeholder,
  that spot will be empty in the frontend. Contact your integrator or administrator in that case.
