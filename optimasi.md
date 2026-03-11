# Trader Book — Rencana Optimasi & Peningkatan Tampilan

> Tanggal analisa: 11 Maret 2026  
> Scope: Code quality, bug, arsitektur backend, UI/UX improvement  

---

## Bagian 1 — Bug Kritis (Wajib Diperbaiki Lebih Dulu)

### 1.1 Duplicate Migration Files

**Masalah:** Terdapat dua set migration untuk tabel yang sama dengan timestamp berbeda.

| File Duplikat | File Duplikat Kedua |
|---|---|
| `2026_03_11_013546_create_instruments_table.php` | `2026_03_11_013547_create_instruments_table.php` |
| `2026_03_11_013546_create_setups_table.php` | `2026_03_11_013548_create_setups_table.php` |
| `2026_03_11_013546_create_trades_table.php` | `2026_03_11_013549_create_trades_table.php` |
| `2026_03_11_013546_create_daily_journals_table.php` | `2026_03_11_013550_create_daily_journals_table.php` |
| `2026_03_11_013546_create_monthly_targets_table.php` | `2026_03_11_013551_create_monthly_targets_table.php` |

**Dampak:** `migrate:fresh` hanya berjalan karena SQLite bersifat toleran, tapi di MySQL/PostgreSQL akan **gagal** karena tabel sudah ada.  
**Solusi:** Hapus file di `_013546_*` untuk ke-5 tabel tersebut (pertahankan versi `_013547` dst yang punya FK urutan benar).

---

### 1.2 Prop Name Mismatch — Statistics Page

**Masalah:** `StatisticController` mengirim key props berbeda dengan yang diexpect `Statistics/Index.vue`:

| Controller kirim | Vue expect |
|---|---|
| `pair_performance` | `performance_by_pair` |
| `setup_performance` | `performance_by_setup` |
| `session_performance` | `performance_by_session` |
| `timeframe_performance` | `performance_by_timeframe` |
| `day_performance` | `performance_by_day_of_week` |

**Dampak:** Semua tabel breakdown di halaman Statistics **kosong** meskipun data ada.  
**Solusi:** Seragamkan key di controller atau di Vue (pilih salah satu).

---

### 1.3 Field Name Mismatch — Trade List & Detail

**Masalah:** `Dashboard/Index.vue` dan `Statistics/Index.vue` akses `trade.trade_date`, tapi model `Trade` menyimpan field sebagai `date` (bukan `trade_date`).  
**Dampak:** Kolom tanggal kosong di recent trades dashboard.  
**Solusi:** Ganti akses `trade.trade_date` → `trade.date` di kedua halaman, atau tambahkan accessor `getTradeDate()` di model.

---

### 1.4 Warna `brand-600` Tidak Terdefinisi di Tailwind

**Masalah:** `AppLayout.vue` dan beberapa halaman menggunakan class `bg-brand-600` / `hover:bg-brand-700`, namun `tailwind.config.js` tidak mendefinisikan color `brand`.  
**Dampak:** Tombol tampil transparan / tanpa warna.  
**Solusi:** Tambahkan color palette `brand` di `tailwind.config.js` atau ganti semua `brand-` ke `indigo-`.

---

### 1.5 Missing `warning` Variant di `AppBadge`

**Masalah:** `Statistics/Index.vue` menggunakan `variant="warning"` pada mistake distribution badge, tapi variant ini tidak didefinisikan di `AppBadge.vue`.  
**Dampak:** Badge tampil tanpa styling.  
**Solusi:** Tambahkan variant `warning` (orange/amber) ke `AppBadge.vue`.

---

## Bagian 2 — Optimasi Kode & Arsitektur

### 2.1 Duplikasi Logika Kalkulasi di DashboardController

**Masalah:** `DashboardController` menghitung win rate, profit factor, equity curve, dan daily P/L secara manual menggunakan Collection methods langsung, padahal `StatisticService` sudah dibuat untuk tujuan ini.  
**Dampak:** Code tidak DRY; kalau formula berubah, harus diubah di 2 tempat.  
**Solusi:**
```php
// Sebelum:
$totalWinMonth = $monthTrades->where('result', 'win')->count();
$monthWinRate = $monthTrades->count() > 0 ? round(...) : 0;

// Sesudah:
$service = new StatisticService($monthTrades);
$monthWinRate = $service->winRate();
$profitFactor = $service->profitFactor();
```

---

### 2.2 Tidak Ada Form Request untuk DailyJournal Store/Update

**Masalah:** `DailyJournalController::store()` dan `update()` menerima raw `Request` tanpa dedicated `FormRequest`.  
**Dampak:** Validasi berserakan di controller; tidak konsisten dengan `StoreTradeRequest` / `UpdateTradeRequest`.  
**Solusi:** Buat `StoreJournalRequest` dan `UpdateJournalRequest` di `app/Http/Requests/`.

---

### 2.3 Tidak Ada Authorization Policy

**Masalah:** Semua controller hanya menggunakan `scopeForUser()` untuk filtering, tapi tidak ada `Policy` class untuk memastikan user tidak bisa mengakses resource milik user lain via URL manipulation.  
**Dampak:** Celah keamanan — user bisa akses `/trades/999` milik user lain jika tahu ID-nya.  
**Solusi:** Buat `TradePolicy`, `JournalPolicy`, dll., dan daftarkan di `AuthServiceProvider`. Gunakan `$this->authorize('view', $trade)` di controller.

---

### 2.4 Tidak Ada Query Scope di `TradingAccount::switchActive`

**Masalah:** `switchActive` di controller tidak memverifikasi bahwa `trading_account_id` yang dikirim memang milik user tersebut sebelum diset ke `active_account_id`.  
**Solusi:**
```php
$account = TradingAccount::query()->forUser()
    ->findOrFail($request->integer('trading_account_id'));
auth()->user()->update(['active_account_id' => $account->id]);
```

---

### 2.5 `StatisticService` Receives Entire Collection (Potensi Memory)

**Masalah:** `StatisticService` menerima seluruh collection trade tanpa paginasi. Untuk trader dengan ribuan trade, ini akan memuat semua data ke memori.  
**Solusi Jangka Pendek:** Batasi default range ke 3 bulan terakhir di `StatisticController`.  
**Solusi Jangka Panjang:** Refactor ke query-based aggregation (DB level) untuk metrik utama seperti win rate, profit factor, dan expectancy.

---

### 2.6 Tidak Ada Error Pages (404, 403, 500)

**Masalah:** Belum ada custom error views.  
**Solusi:** Buat `resources/views/errors/404.blade.php` dan `403.blade.php` dengan tampilan yang on-brand.

---

### 2.7 Flash Message di TradeController Berpotensi Kirim Null

**Masalah:**
```php
$warningMessage = collect(data_get($riskStatus, 'warnings', []))->implode(' ');
return redirect()->route('trades.index')
    ->with('warning', $warningMessage ?: null); // null di-flash
```
Flash dengan value `null` bisa menyebabkan AppToast tetap muncul dengan pesan kosong.  
**Solusi:** Hanya flash `warning` jika `$warningMessage` tidak kosong.

---

### 2.8 Redundant `trade_date` Field di Seeder / API Response

**Catatan:** Model `Trade` memiliki `date` yang di-cast ke `date`. Saat dirender ke JSON, field ini tetap bernama `date`. Halaman Vue mengakses `trade.trade_date` di beberapa tempat — konsistensikan.

---

### 2.9 Migration File Set Pertama (`_013546`) Masih Berisi Tabel Tanpa FK yang Benar

File `_013546` yang duplikat untuk `trades`, `daily_journals`, dll. kemungkinan punya definisi FK yang salah urutan sehingga tidak aman digunakan. Pastikan hanya satu set yang aktif.

---

## Bagian 3 — Fitur Yang Belum Diimplementasi (Ada di Blueprint, Belum Ada)

| Fitur | Status | Prioritas |
|---|---|---|
| Chart visual (equity curve, daily P/L bar chart) | Data siap, UI kosong | Tinggi |
| Mobile navigation (hamburger menu) | Sidebar hidden di mobile | Tinggi |
| Active menu highlight di sidebar | Tidak ada | Sedang |
| Dark mode | Tidak ada | Rendah |
| Currency formatting (IDR/USD) | Hanya angka mentah | Sedang |
| Trade screenshot preview di detail | Field ada, UI belum | Sedang |
| Seeder / data dummy untuk development | Tidak ada | Sedang |
| Password change di profile settings | UI ada tapi belum di-wire | Sedang |
| HeroIcons sudah install tapi tidak dipakai | — | Rendah |
| `vue-chartjs` / `chart.js` install tapi tidak dipakai | — | Tinggi |

---

## Bagian 4 — Rencana Peningkatan Tampilan (UI/UX Plan)

### 4.1 Design System & Token Warna

**Kondisi saat ini:** Warna tersebar (`indigo-600`, `gray-900`, `green-600`, `red-600`) tanpa sistem yang terpusat. Tidak ada color `brand`.

**Target:**
- Definisikan palet warna semantik di `tailwind.config.js`:
  ```js
  colors: {
    brand: {
      50: '#eef2ff',
      100: '#e0e7ff',
      500: '#6366f1',   // indigo-500
      600: '#4f46e5',
      700: '#4338ca',
    },
    success: { ... },  // green
    danger:  { ... },  // red
    warning: { ... },  // amber
    muted:   { ... },  // cool gray
  }
  ```
- Semua komponen UI memakai token ini, bukan warna literal.
- Tambahkan custom font pair: **Inter** (body) + **Space Grotesk** (heading/number) untuk tampilan lebih profesional bagi trader.

---

### 4.2 AppLayout — Sidebar & Navigation

**Kondisi saat ini:**
- Sidebar plain dark gray, teks saja, tidak ada ikon
- Tidak ada active state highlight
- Mobile: sidebar hilang total, tidak ada mobile nav

**Plan Perbaikan:**

```
[Desktop]
┌─────────────────────────────────────────────────────┐
│ ▶ TRADER BOOK    [Logo/icon kecil]                  │  ← Header sidebar
├─────────────────────────────────────────────────────┤
│ 📊 Dashboard                                        │  ← Dengan icon HeroIcons
│ 📋 Trades                        [badge count]      │  ← Badge jumlah trade hari ini
│ 📓 Journal                                          │
│ 📈 Statistics                                       │
│ ─────────────────                                   │  ← Divider
│ ⚙  Settings                                         │  ← Collapsible sub-menu
│   └ Accounts                                        │
│   └ Instruments                                     │
│   └ Setups                                          │
│   └ Targets                                         │
│   └ Profile                                         │
├─────────────────────────────────────────────────────┤
│ [Avatar] John Doe                  [Logout icon]    │  ← User info di bottom
└─────────────────────────────────────────────────────┘

[Mobile]
- Hamburger button di top-left
- Drawer dari kiri, overlay backdrop semi-transparent
- Bottom tab bar untuk navigasi utama (Dashboard/Trades/Journal/Stats)
```

**Implementasi:**
- Tambahkan `@heroicons/vue` icon di setiap menu item
- Active state: `bg-brand-700 text-white` untuk item aktif (cek dengan `route().current()`)
- Sidebar collapsible untuk Settings sub-menu
- Mobile: gunakan `<Transition>` Vue untuk slide-in drawer
- Account switcher dipindah ke dalam sidebar (lebih rapi)

---

### 4.3 Dashboard — Metric Cards

**Kondisi saat ini:**
- 4 card plain putih dengan angka besar
- Progress bar polos
- Tabel recent trades tanpa styling khusus

**Plan Perbaikan:**

```
┌──────────────────────────────────────────────────────────┐
│  📅 Hari Ini                    📅 Bulan Ini              │
│  ┌────────────────┐  ┌────────────────┐                  │
│  │  Trades: 3     │  │ Win Rate: 67%  │                  │
│  │  P/L: +$120 ▲  │  │ P/L: +$440 ▲  │                  │
│  │  [bar mini]    │  │ PF: 2.1        │                  │
│  └────────────────┘  └────────────────┘                  │
│                                                          │
│  ┌──────────────────────────────────────┐               │
│  │  Equity Curve (90 hari)              │               │
│  │  [Line chart dari vue-chartjs]       │               │
│  └──────────────────────────────────────┘               │
│                                                          │
│  Target Bulan Ini ━━━━━━━━━━━━━━━━━━       67% / $1000  │
│                                                          │
│  Risk Status:  [✅ Safe] DD Harian: 1.2%               │
└──────────────────────────────────────────────────────────┘
```

**Elemen baru:**
- Trend arrow (▲ hijau / ▼ merah) di sebelah nilai P/L
- Sparkline mini bar chart di bawah angka card
- **Equity curve line chart** menggunakan `vue-chartjs` + `chart.js` (data sudah tersedia)
- **Daily P/L bar chart** 30 hari terakhir (batang hijau/merah)
- Progress bar target dengan gradient warna (merah → kuning → hijau sesuai %)
- Risk status card dengan warna background dinamis

---

### 4.4 Trades — Index & Filter

**Kondisi saat ini:**
- Filter berderet dalam grid biasa
- Tabel dengan header minimal
- Badge ada tapi warna kurang kontras

**Plan Perbaikan:**
- Filter area: collapsible panel dengan `<Transition>` (default collapse jika tidak ada filter aktif)
- Summary bar di atas tabel: total trades, total P/L, win rate untuk hasil filter aktif
- Tabel:
  - Kolom pair + icon arah (▲ Buy / ▼ Sell) dengan warna
  - Kolom result dengan badge lebih besar
  - Kolom P/L dengan angka berwarna + format currency
  - Hover row highlight
  - Row click → navigasi ke detail
  - Sticky header saat scroll panjang
- Tombol export lebih visible (dropdown CSV/Excel)

---

### 4.5 Trades — Form (Create/Edit)

**Kondisi saat ini:**
- Form panjang tanpa grouping visual
- Input biasa tanpa state aktif yang jelas

**Plan Perbaikan:**
- Bagi form ke dalam **tab/section**, misalnya:
  1. Entry Details (pair, direction, date, session)
  2. Price & Risk (entry/SL/TP, lot size, RR)
  3. Exit & Result (close price, P/L, result)
  4. Notes & Tags (plan, review, screenshot)
- Kalkulasi RR dan P/L secara **real-time** dengan preview kartu sebelah kanan
- Screenshot: preview thumbnail langsung setelah upload
- Tombol simpan dengan `Ctrl+Enter` shortcut

---

### 4.6 Statistics — Visualisasi Data

**Kondisi saat ini:**
- Hanya tabel dan angka
- Tidak ada chart

**Plan Perbaikan:**
- **Win/Loss Donut Chart** untuk distribusi hasil
- **Bar Chart horizontal** untuk performance by pair (sort by P/L)
- **Heatmap kalender** untuk P/L harian (mirip GitHub contribution graph)
- **Radar Chart** untuk performance by session/timeframe
- **Table** tetap ada di bawah chart sebagai detail
- Filter yang lebih interaktif: date range picker visual (bukan input text)

---

### 4.7 Journal — Index & Detail

**Kondisi saat ini:**
- List jurnal sederhana dengan tabel summary per tanggal
- Form create/edit plain

**Plan Perbaikan:**
- **Kalender view** sebagai UI utama:
  - Grid 7 kolom (Senin–Minggu)
  - Setiap sel menampilkan P/L hari itu dengan warna (hijau/merah/abu)
  - Klik sel → drawer/modal untuk isi jurnal harian
  - Indikator mood (emoji atau warna dot)
- List view tetap tersedia sebagai toggle
- Form jurnal: textarea dengan auto-resize, mood selector (emoji 1–5)

---

### 4.8 Settings — Komponen Tabel CRUD

**Kondisi saat ini:**
- Form dan tabel basic tanpa animasi transisi

**Plan Perbaikan:**
- Inline edit langsung di tabel (click cell → editable)
- Konfirmasi hapus via modal bukan `confirm()` browser
- Drag-and-drop reorder untuk instruments & setups (opsional, fase 2)
- Status toggle (active/inactive) dengan switch UI bukan checkbox

---

### 4.9 Komponen UI — Peningkatan yang Diperlukan

| Komponen | Kondisi Sekarang | Perbaikan |
|---|---|---|
| `AppInput` | Plain input | Tambah left/right icon slot, focus ring, error state animasi |
| `AppButton` | Fungsional | Loading spinner lebih smooth, size variant (sm/md/lg) |
| `AppBadge` | Baik | Tambah `warning` variant, size prop |
| `AppTable` | Wrapper tipis | Tambah sortable column, sticky header, loading skeleton |
| `AppModal` | Ada tapi belum dipakai merata | Pastikan accessible (trap focus, ESC close) |
| `AppToast` | Ada | Posisi, animasi slide-in, auto dismiss |
| `AppProgressBar` | Ada | Gradient color + animasi fill |
| Baru: `AppDateRangePicker` | Tidak ada | Komponen date range untuk filter |
| Baru: `AppCurrencyDisplay` | Tidak ada | Format angka sesuai currency preference user |
| Baru: `AppChart` | Tidak ada | Wrapper tipis untuk `vue-chartjs` |
| Baru: `AppCalendar` | Tidak ada | Untuk Journal kalender view |

---

### 4.10 Responsivitas Mobile

**Kondisi saat ini:**  
Sidebar hilang total di mobile (`hidden lg:block`) tanpa pengganti. Semua grid `md:grid-cols-4` menjadi satu kolom panjang.

**Plan:**
1. **Bottom Navigation Bar** untuk mobile (4 item utama: Dashboard, Trades, Journal, Stats)
2. **Hamburger drawer** untuk menu lengkap termasuk Settings
3. **Grid cards** di dashboard: 2 kolom di tablet, 1 kolom di mobile
4. **Tabel trades** di mobile: mode card stacked bukan tabel horizontal
5. **Filter panel** di mobile: full-screen bottom sheet

---

## Bagian 5 — Urutan Pengerjaan yang Direkomendasikan

### Fase 1 — Bug Fix Kritis (1–2 jam)
1. Hapus duplicate migration files
2. Fix prop name mismatch di `StatisticController`
3. Fix `brand-600` di Tailwind config
4. Fix `trade.date` vs `trade.trade_date`
5. Tambah `warning` variant di `AppBadge`
6. Fix flash null warning di `TradeController`

### Fase 2 — Polish Backend (2–4 jam)
1. Buat `StoreJournalRequest` & `UpdateJournalRequest`
2. Gunakan `StatisticService` di `DashboardController`
3. Tambah authorization policy dasar
4. Fix `switchActive` validasi kepemilikan account
5. Tambah seeder dengan data dummy untuk development

### Fase 3 — Chart & Visualisasi (4–8 jam)
1. Buat `AppChart.vue` wrapper component
2. Equity curve line chart di Dashboard
3. Daily P/L bar chart di Dashboard
4. Win/loss donut chart di Statistics
5. Performance by pair horizontal bar chart
6. Format currency di seluruh halaman

### Fase 4 — Mobile & Navigation (4–6 jam)
1. Redesign AppLayout dengan ikon HeroIcons
2. Active menu state
3. Mobile hamburger drawer
4. Bottom tab bar mobile
5. Responsive trade table → card mode di mobile

### Fase 5 — UI Polish & Komponen Baru (8–12 jam)
1. Journal kalender view
2. Trade form dengan section/tab grouping
3. Filter collapsible di Trades
4. Inline edit di Settings tables
5. `AppCurrencyDisplay` component
6. Custom error pages (404, 403)
7. Loading skeleton states
8. Animasi page transition Inertia

---

## Ringkasan Prioritas

| # | Item | Dampak | Effort |
|---|---|---|---|
| 1 | Fix duplicate migrations | 🔴 Bug kritis | XS |
| 2 | Fix prop mismatch Statistics | 🔴 Data tidak tampil | XS |
| 3 | Fix brand-600 Tailwind | 🔴 UI rusak | XS |
| 4 | Fix trade.date field | 🟠 Data salah | XS |
| 5 | Chart visual di Dashboard/Stats | 🟠 Core feature | M |
| 6 | Mobile navigation | 🟠 UX penting | M |
| 7 | Active sidebar highlight | 🟡 UX minor | S |
| 8 | Authorization policies | 🟠 Security | M |
| 9 | Journal kalender view | 🟡 Nice to have | L |
| 10 | Form grouping & real-time calc | 🟡 UX polish | M |
