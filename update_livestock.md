# Update Plan: Twelve Data Integration Blueprint

Dokumen ini adalah planning agent untuk membuat fitur serupa:
- sync instrument otomatis dari API eksternal
- update harga realtime berkala
- simpan cache harga ke database
- tampilkan insight market di dashboard

## 1. Objective

Bangun integrasi provider market data (contoh: Twelve Data) yang mendukung:
1. Pencarian dan sinkronisasi instrument ke database per user.
2. Pengambilan harga terbaru per symbol.
3. Penyimpanan harga terbaru + perubahan persentase.
4. Scheduler otomatis untuk sync symbol dan refresh harga.
5. Visualisasi Top Movers di dashboard.

## 2. Arsitektur Minimum

1. Service layer untuk provider API.
2. Controller endpoint untuk sync dan prices.
3. Command artisan untuk otomatisasi background.
4. Scheduler task untuk periodik run.
5. Frontend polling data harga.
6. Dashboard widget berbasis data cache DB.
7. Feature test untuk endpoint penting.

## 3. Komponen yang Perlu Dibuat

### A. Config + Environment

Tambahkan config service provider API:
- key
- base_url
- default sync keywords
- default sync limit

Tambahkan env vars:
- TWELVEDATA_API_KEY
- TWELVEDATA_BASE_URL
- TWELVEDATA_SYNC_KEYWORDS
- TWELVEDATA_SYNC_LIMIT

## B. Database

Tambahkan kolom pada tabel instruments:
- last_price (decimal)
- price_change_pct (decimal)
- price_source (string)
- price_updated_at (timestamp)

## C. Service (Provider Client)

Buat service dengan fungsi utama:
1. isConfigured()
2. searchSymbols(query, limit)
3. getPrice(symbol)
4. refreshInstrumentPrice(instrument)

Aturan penting:
- gunakan timeout HTTP
- handle error response provider
- pakai cache pendek (contoh 10 detik) untuk endpoint harga
- mapping category provider -> category internal (forex/commodity/crypto/index/stock)

## D. Controller

Tambahkan endpoint:
1. syncFromProvider
- input: query, limit, category(optional)
- behavior: upsert instrument per user
- output: flash success/error

2. prices
- input: symbols(optional)
- behavior: fetch price, update cache DB (last_price + change_pct + timestamp)
- output: JSON harga

## E. Console Commands

Buat command:
1. instruments:sync-twelvedata
- sync keyword default ke semua user
- opsi: --keywords, --limit, --category

2. instruments:refresh-prices
- refresh harga semua instrument aktif
- opsi: --user, --limit

## F. Scheduler

Daftarkan scheduler:
1. sync symbols harian (contoh 06:00)
2. refresh prices tiap 10 menit (with withoutOverlapping)

## G. Frontend Instruments

Tambahan UI:
1. Form sync dari API
- keyword
- limit
- filter category

2. Tabel harga realtime
- polling tiap 10 detik
- status Live/Cached/Error
- fallback ke last_price dari DB

## H. Frontend Dashboard

Tambahkan widget Top Movers:
- urutkan berdasarkan ABS(price_change_pct) terbesar
- tampilkan symbol, name, last price, change %, updated at
- fallback state bila data belum tersedia

## 4. Routing yang Diperlukan

Dalam group auth + settings:
1. POST settings/instruments/sync
2. GET settings/instruments/prices

## 5. Validation dan Security

1. Semua endpoint harus di middleware auth.
2. Semua query instrument harus terfilter user_id.
3. Validasi request ketat untuk query/limit/category.
4. Hindari expose API key ke frontend.

## 6. Testing Minimum

Buat feature test untuk:
1. sync endpoint berhasil menyimpan instrument.
2. prices endpoint mengupdate last_price, price_change_pct, price_source, price_updated_at.

Gunakan:
- RefreshDatabase
- Http::fake untuk mock provider API
- assert database state

## 7. Acceptance Criteria

Fitur dianggap selesai bila:
1. User bisa sync instrument dari UI.
2. Harga realtime tampil di halaman Instruments.
3. Dashboard menampilkan Top Movers dari data cache.
4. Scheduler menampilkan dua task aktif.
5. Test feature untuk sync/prices lulus.

## 8. Operasional (Opsional tapi Disarankan)

1. Tambahkan logging untuk setiap run command.
2. Tambahkan monitoring jumlah gagal fetch harga.
3. Tambahkan retry/backoff jika provider rate limit.
4. Siapkan fallback provider jika API utama down.

## 9. Runbook Singkat

1. Isi env key provider.
2. Jalankan migration.
3. Jalankan sync awal symbols.
4. Jalankan refresh prices manual sekali.
5. Verifikasi Top Movers di dashboard.
6. Nyalakan scheduler worker untuk development.

## 10. Checklist Agent Execution

- [ ] Update config/services.php
- [ ] Update .env.example dan env docker
- [ ] Buat migration kolom harga
- [ ] Update model Instrument casts/fillable
- [ ] Buat service provider client
- [ ] Tambah endpoint sync + prices di controller
- [ ] Tambah routes settings instruments
- [ ] Buat command sync symbols
- [ ] Buat command refresh prices
- [ ] Daftarkan scheduler
- [ ] Update UI Instruments (sync + realtime)
- [ ] Update Dashboard (Top Movers)
- [ ] Tambah feature test
- [ ] Update README runbook
- [ ] Jalankan test dan verifikasi manual
