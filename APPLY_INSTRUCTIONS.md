# Cara menerapkan update EDOM ke repo `dimszkyz/newedom`

Isi paket ini adalah file replacement untuk menyesuaikan project dengan schema inti:

- `edom_settings`
- `edom_settings_program_studi`
- `program_studi`
- `edom_periods`
- `edom_question_options`
- `edom_question_categories`
- `edom_questions`
- `edom_response`
- `edom_response_detail`

Perubahan utama:

1. Menghapus penyimpanan Mata Kuliah manual dari database dan admin.
2. Menghapus tabel `courses` dan `edom_courses`.
3. Mengganti hasil pengisian dari `edom_responses`/`edom_answers` menjadi `edom_response`/`edom_response_detail`.
4. Menambahkan `edom_periods`.
5. Menjadikan `siakad_idmahasiswa` string supaya bisa dipakai untuk testing `testing18273`.
6. Memasang HMAC temporary random di `.env.example`.

## Langkah apply

Jalankan dari root repository `newedom`:

```bash
unzip newedom-edom-integration-update.zip -d /tmp/newedom-edom-update
cd /path/ke/newedom

# copy semua file pengganti
cp -R /tmp/newedom-edom-update/repo/* .

# hapus file lama yang sudah tidak dipakai
while IFS= read -r file; do
  [ -n "$file" ] && rm -f "$file"
done < /tmp/newedom-edom-update/DELETE_FILES.txt

composer dump-autoload
php artisan migrate:fresh --seed
php artisan optimize:clear
```

Untuk test token:

```bash
php artisan edom:make-token testing18273 2026 2 --ttl=3600
```

Catatan: `migrate:fresh` disarankan karena schema inti berubah dan tabel Mata Kuliah manual memang dihapus.
