# EDOM App — Integration & Usage Guide for `dimszkyz/newedom`

This file adapts the uploaded EDOM integration guide to the current `newedom` Laravel/Filament project.

## Core schema now used by this project

The project no longer stores manually input Mata Kuliah in its own database. Mata kuliah/section data always comes from `/edom/krs` or `/edom/penawaran` in `unw-api-siakad`.

Core tables:

| Table | Purpose |
|---|---|
| `edom_settings` | EDOM question set, with `name` and `status` (`draft`, `active`, `closed`) |
| `edom_settings_program_studi` | Pivot from EDOM question set to `program_studi` |
| `program_studi` | Local program-study table: `id_unw_program_studi`, `nama` |
| `edom_periods` | EDOM period: `year`, `siakad_idsemester` |
| `edom_question_categories` | Category group for questions |
| `edom_questions` | Questions, with `question_type` = `option` or `text` |
| `edom_question_options` | Answer options with `name` and `score` |
| `edom_response` | One response row per student × period × EDOM × KRS section |
| `edom_response_detail` | One detail row per question answer |

Removed from the core schema:

- `courses`
- `edom_courses`
- old plural `edom_responses`
- old plural `edom_answers`
- old `edoms`, `edom_categories`, `edom_options`

## Environment

```env
UNW_API_SIAKAD_BASE_URL=https://api.siakad.unw.ac.id
UNW_API_SIAKAD_EMAIL=edomuser@mail.com
UNW_API_SIAKAD_PASSWORD=

# Temporary random value until the real SIAKAD secret is provided.
HMAC_SIAKAD_SECRET=temporary-random-edom-hmac-7f4d0c9b1a8e6f203b55c1149dd83a2c
```

`HMAC_SIAKAD_SECRET` must be replaced with the real `edom_hmac_secret` from SIAKAD before production.

## Testing `/enter`

The testing student id is currently treated as a string, so `testing18273` is valid.

```bash
php artisan edom:make-token testing18273 2026 2 --ttl=3600
```

Paste the generated `/enter?token=...` URL into a browser/Postman.

## Student flow

1. SIAKAD opens `GET /enter?token=<base64url>.<signature>`.
2. The EDOM app verifies the HMAC token and `exp`.
3. The app stores `siakad_idmahasiswa`, `siakad_idtahunajaran`, `siakad_idsemester`, and optional `return_url` in session.
4. The app calls `/edom/krs` using the exact year and semester from the token.
5. Active `edom_settings` are resolved through `edom_settings_program_studi` by matching `program_studi.id_unw_program_studi` to the KRS section's `id_unw_program_studi`.
6. The form renders the same question block for every returned KRS section.
7. On submit, the app upserts:
   - `edom_response`
   - `edom_response_detail`
8. If all current KRS sections have responses, the app calls `/edom/complete`.
9. The app redirects back to token `return_url` or `EDOM_SIAKAD_FALLBACK_URL`.

## Admin flow

Filament provides:

- EDOM question-set management.
- Prodi sync/selection.
- Category and question management.
- Option management.
- Period management with `openPeriod()` and `closePeriod()` actions to mirror the active period to SIAKAD.
- Hasil EDOM report based on `edom_response` and `edom_response_detail`.

## Important implementation rule

Do not reintroduce manual Mata Kuliah storage in this app. The SIAKAD KRS/penawaran response is the source of truth for mata kuliah, lecturer, and section identifiers.
