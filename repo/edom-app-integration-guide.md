# EDOM App — Integration & Usage Guide (newedom adjusted)

Project ini sudah disesuaikan ke schema inti:

- `edom_settings`
- `edom_settings_program_studi`
- `program_studi`
- `edom_periods`
- `edom_question_options`
- `edom_question_categories`
- `edom_questions`
- `edom_response`
- `edom_response_detail`

Perubahan penting pada project:

1. `Edom` lama diganti menjadi `SettingEdom`.
2. `EdomCategory` lama diganti menjadi `EdomQuestionCategory`.
3. `EdomOption` lama diganti menjadi `EdomQuestionOption`.
4. `EdomAnswer` lama diganti menjadi `EdomResponseDetail`.
5. Input Mata Kuliah manual dihapus dari database dan panel admin.
6. Mata kuliah selalu berasal dari `/edom/krs` SIAKAD.
7. `siakad_idmahasiswa` disimpan sebagai string agar testing `testing18273` bisa dipakai.
8. `HMAC_SIAKAD_SECRET` di `.env.example` masih temporary random dan wajib diganti saat secret asli sudah diberikan.

Test token:

```bash
php artisan edom:make-token testing18273 2026 2 --ttl=3600
```

Atau cukup:

```bash
php artisan edom:make-token
```

karena default testing sudah diset ke `testing18273 2026 2`.
