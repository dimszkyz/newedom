# newedom SQL schema hotfix

This patch aligns the code to the uploaded SQL schema:

- `program_studi.nama`, not `program_studi.name`
- no `degree_short_name`, `faculty_name`, or `sort_order`
- `edom_questions.question_type` uses `option` / `text`
- `edom_response_detail.edom_option_id`, not `edom_question_option_id`
- controller uses `SettingEdom`, not `SettingsEdom`, `Edom`, or `SettingSettingEdom`

Apply:

```powershell
powershell -ExecutionPolicy Bypass -File "D:\path\hasil-extract\apply_sql_schema_hotfix.ps1" -ProjectRoot "D:\13. bptik\newedom"
composer dump-autoload
php artisan optimize:clear
php artisan migrate:fresh --seed
```
