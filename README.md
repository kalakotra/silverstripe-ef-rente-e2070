# E 207 – Angaben über den Beschäftigungsverlauf des Versicherten (E2070)

Form module for the e-formulare.de PDF filler (`kalakotra/pdf-editor`).
Fills the DRV form **E2070-00** (*E 207 – Angaben über den Beschäftigungsverlauf des Versicherten*, Version 01002,
4 pages, 99 text fields) incl. Ziffer 7 with up to **16 periods** and stamps the applicant's signature on every
copy of page 2 (Anmerkung 23).

Requires the pdf-editor app base classes (`app\forms\basemodel\*`, `app\models\FormSubmission`).

## Installation

pdf-editor `composer.json`:

```json
"repositories": [
    { "type": "git", "url": "https://github.com/kalakotra/silverstripe-ef-rente-e2070" }
],
"require": {
    "kalakotra/silverstripe-ef-rente-e2070": "^1.0"
}
```

```bash
composer update kalakotra/silverstripe-ef-rente-e2070
vendor/bin/sake db:build --flush
```

`db:build` is mandatory: `DBClassName` is an enum, without it the new steps load as the base `MultiFormStep`
("Please define at least one $next_steps").

## CMS setup (PDFMultiFormPage)

| Tab | Setting |
|---|---|
| PDF | `PDF File` (slot `PDFFile`) = original **E2070.pdf** (fillable E2070-00, Version 01002, from deutsche-rentenversicherung.de) |
| Form | `Form Class Name` = **Angaben über den Beschäftigungsverlauf (E 207 / E2070)** |
| Customising | `SignaturePositionX/Y` – leave empty; module default X 234 mm / Y 182 mm on the landscape page 2 |

| CMS slot | PDF | Used for |
|---|---|---|
| `PDFFile` | E2070.pdf | page 1, page 2 (periods 1–8), page 2 again (periods 9–16, only when needed), pages 3–4 |

No further slots: the continuation page is a second fill of the same template. `Custom Field Mapping` is not needed.

## Flow

| Step | E 207 | Fields / branching |
|---|---|---|
| 1 | Ziffer 1 | Name, Geburtsname (or „IDEM“), Vornamen, frühere Namen, Geschlecht M/F, Eltern |
| 2 | Ziffer 2–4 | Staatsangehörigkeit (+ Einbürgerung), Kenn-Nr., Geburt, Anschrift + Telefon |
| 3 | Ziffer 5 | DRV-Versicherungsnummer (→ 1.8 and 5.1), Kennzeichen, Nummer beim ausländischen Träger |
| Period 1–16 | Ziffer 7 | Von/Bis (empty = „laufend“), Art der Zeit, Tätigkeit, Arbeitgeber, Ort/Land, a) Träger b) Nummer c) Art der Versicherung, Wohnort; „weiterer Zeitraum?“ → next period or preview |
| Preview | 7.2, 7.3 | E-Mail, Unterschrift, AGB |

Not filled on purpose: Ziffer 6 (bearbeitender Träger: `VERS_B_TRAEGER_1`, `AIF_VT_KLTX4`, `VWST`, `VWOT`,
`PAF_ADate`), 7.1 Stempel, `VERS_VSNR_2_1`, `Version`.

## How it works

- **`Submission::$field_mapping`** covers page 1 and rows 1–8 of page 2. `periodPageData($copy)` reuses the row
  mapping with the period numbers shifted by 8 for the second copy of page 2.
- **`PdfPackage`** (from kinderzuschlag): fills each part separately, merges `1-2`, `2` (copy 2), `3-4` with pdftk
  and overlays via FPDI: signature on every page 2 copy, white masks over the printed row numbers 1–8 of the copy
  and the continued numbers 9–16 (Hinweise: „fortlaufende Nummerierung“). Page 2 is `/Rotate 90`; positions are mm
  on the landscape page.
- **`CellText`**: the table cells use a fixed 9 pt font (4 lines) and pdftk cuts words wider than a cell, so the
  values are word-wrapped (long words hyphenated) with Helvetica metrics (= Arial / Liberation Sans). The period
  steps reject texts that would need more than 4 lines (CAPITALS) – `Submission::cellFits()`.
- Column 3 „Art der Zeiten“ = short German type label + activity (Anmerkung 18), c) = German insurance type
  (Anmerkung 21), `PdfValue` via `$pdf_values`.
- **`Submission::$field_conditions`** – declarative dependency rules mirrored by display-logic: step cleanup,
  conditional required validation, `onBeforeWrite()` cleanup incl. the period chain (period N only if
  `Period{N-1}More = yes`).
- **`Form::getActiveSteps()`** – submission and preview only use steps on the current path; preview labels of the
  period steps come from the field titles (`PeriodStepTrait` lang keys).
- **Fonts**: pdftk drops ž, ć, č, đ, š in its own appearance generation, so `fonts/LiberationSans-Regular.ttf`
  (SIL OFL 1.1) is passed as `replacement_font`; without pdftk-java ≥ 3 the values are transliterated to ASCII.
- More than 16 periods: add `Form_Period17..24` (copy of `Form_Period16`, adjust `PERIOD`, sort order, next step),
  the `$db` / `$field_conditions` blocks and raise `Submission::MAX_PERIODS`; page copies follow automatically.

## Languages

`lang/` – ar, bg, bs, cs, de, en, es, fr, it, pl, ro, ru, tr, uk. The PDF itself is always filled in German.

## License

BSD-3-Clause. `fonts/LiberationSans-Regular.ttf`: SIL Open Font License 1.1 (see `fonts/`).
