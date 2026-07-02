# EDOM Response Snapshot Note

Perbaikan ini memastikan jawaban mahasiswa tetap terekam meskipun admin menghapus pertanyaan atau opsi jawaban dari EDOM Settings setelah mahasiswa submit.

## Cara kerja

- `edom_response_detail` menyimpan snapshot kategori, pertanyaan, tipe pertanyaan, nama opsi, dan nilai opsi.
- Snapshot diisi otomatis saat detail jawaban disimpan.
- Relasi `edom_question_id` diubah dari `cascadeOnDelete` menjadi `nullOnDelete`, sehingga detail jawaban tidak ikut terhapus saat pertanyaan dihapus.
- Relasi `edom_option_id` tetap `nullOnDelete`, tetapi nama opsi dan nilai opsi tetap tersedia dari snapshot.
- Detail hasil EDOM dan rata-rata nilai membaca snapshot terlebih dahulu, lalu fallback ke data master jika snapshot belum tersedia.

## Setelah deploy

Jalankan:

```bash
php artisan migrate
php artisan optimize:clear
```
