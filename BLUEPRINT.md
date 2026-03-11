# BLUEPRINT: Trader Book — Trading Journal Dashboard

> **Framework:** Laravel 11 + Inertia.js + Vue 3 (Composition API) + Tailwind CSS  
> **Database:** MySQL 8  
> **Auth:** Laravel Breeze (Inertia + Vue stack)  
> **Versi Blueprint:** 2.0

---

## DAFTAR ISI

1. [Gambaran Umum](#1-gambaran-umum)
2. [Tech Stack](#2-tech-stack)
3. [Fitur Lengkap](#3-fitur-lengkap)
4. [Struktur Database](#4-struktur-database)
5. [Struktur Direktori Proyek](#5-struktur-direktori-proyek)
6. [Routing](#6-routing)
7. [Urutan Pengerjaan (Step-by-Step)](#7-urutan-pengerjaan-step-by-step)
8. [Detail Setiap Fitur](#8-detail-setiap-fitur)

> **Perubahan dari v1.0:** Tambah Risk Management Rules, Multi-TP/Partial Close, Commission & Swap, Export CSV/Excel, Target & Goal Tracking, Dark Mode, Timezone; perbaikan skema database (index, active_account_id, monthly_targets); langkah pengerjaan diperbarui dari 12 menjadi 13 step; dihapus flag `--ssr` dan VeeValidate.

---

## 1. Gambaran Umum

**Trader Book** adalah aplikasi web pribadi untuk mencatat dan menganalisis aktivitas trading harian seorang trader. Setiap trade dicatat lengkap beserta detail entry, SL, TP, lot, hasil (profit/loss), serta evaluasi kesalahan jika terkena stop loss. Dashboard menampilkan ringkasan performa trading berupa statistik, grafik ekuitas, dan riwayat trade yang dapat difilter.

---

## 2. Tech Stack

| Layer | Teknologi |
|---|---|
| Backend | Laravel 11 |
| Frontend | Vue 3 (Composition API + `<script setup>`) |
| Bridge | Inertia.js v2 |
| Styling | Tailwind CSS v3 |
| Database | MySQL 8 |
| Auth | Laravel Breeze (Inertia + Vue) |
| Chart | Chart.js via vue-chartjs |
| Icons | Heroicons (via @heroicons/vue) |
| Date | Day.js |
| Tabel | TanStack Table (opsional) |
| Export | Laravel Excel (maatwebsite/excel) |

---

## 3. Fitur Lengkap

### 3.1 Autentikasi
- [x] Register akun
- [x] Login / Logout
- [x] Lupa password (reset via email)
- [x] Profil pengguna (nama, email, ganti password, timezone, preferensi mata uang)

### 3.2 Dashboard (Halaman Utama)
- [x] Ringkasan hari ini: total trade, total P/L hari ini, sisa risk slot hari ini
- [x] Ringkasan bulan ini: Win Rate, Profit Factor, Total P/L, progress target bulanan
- [x] Equity Curve chart (grafik akumulasi balance dari waktu ke waktu)
- [x] P/L harian chart (bar chart per hari, 30 hari terakhir)
- [x] Statistik cepat: Total Trade, Win, Loss, BE, Win Rate (%), Average RR
- [x] 5 trade terbaru
- [x] **Risk Rule alert** — peringatan jika sudah mencapai max daily loss atau max trades per hari
- [x] **Progress target bulanan** — progress bar target profit bulan ini vs aktual

### 3.3 Trade Journal (Catatan Trade)
- [x] List semua trade (tabel + filter + pagination)
- [x] Tambah trade baru
- [x] Edit trade
- [x] Hapus trade
- [x] Detail trade
- [x] **Export trade list ke CSV/Excel** (dengan filter yang aktif)

**Field setiap trade:**
| Field | Tipe | Keterangan |
|---|---|---|
| date | date | Tanggal trade |
| open_time | time (nullable) | Waktu entry dibuka |
| close_time | time (nullable) | Waktu trade ditutup |
| session | enum | asia / london / new_york / overlap |
| pair | string | Contoh: XAUUSD, EURUSD, BTC |
| instrument_id | FK (nullable) | Relasi ke tabel instruments |
| direction | enum | buy / sell |
| entry_price | decimal(10,5) | Harga entry |
| stop_loss | decimal(10,5) | Harga SL |
| take_profit_1 | decimal(10,5) | Harga TP1 |
| take_profit_2 | decimal(10,5) (nullable) | Harga TP2 (partial close) |
| take_profit_3 | decimal(10,5) (nullable) | Harga TP3 (partial close) |
| close_price | decimal(10,5) | Harga aktual close |
| lot_size | decimal(10,2) | Ukuran lot total |
| risk_amount | decimal(15,2) | Nominal rupiah/dollar yang di-risk |
| commission | decimal(10,2) | Biaya komisi broker (default 0) |
| swap | decimal(10,2) | Biaya swap/overnight (default 0) |
| result | enum | win / loss / breakeven / **partial** |
| profit_loss | decimal(15,2) | Nominal P/L **bersih** (setelah commission & swap) |
| profit_loss_gross | decimal(15,2) | Nominal P/L **kotor** (sebelum biaya) |
| pips | decimal(10,1) | Pips yang didapat / hilang |
| rr_ratio | decimal(5,2) | Risk:Reward **aktual** |
| rr_planned | decimal(5,2) | Risk:Reward yang **direncanakan** saat entry |
| setup_id | FK (nullable) | Relasi ke tabel setups |
| setup | string | Nama setup (bisa diisi bebas) |
| timeframe | string | TF entry (M15, H1, H4, dll) |
| followed_plan | boolean | Apakah trade sesuai dengan rencana? |
| mistake | text (nullable) | Kesalahan / evaluasi jika loss |
| notes | text (nullable) | Catatan tambahan |
| screenshot_before | string (nullable) | Path screenshot sebelum entry |
| screenshot_after | string (nullable) | Path screenshot setelah close |
| tags | json (nullable) | Tag bebas (FOMO, Revenge, Discipline, dll) |

**Keterangan result `partial`**: trade yang di-close sebagian (partial TP hit), sisa posisi masih jalan.

### 3.4 Daily Journal (Jurnal Harian)
- [x] Satu entri per hari per akun
- [x] Catatan mood/kondisi psikologi sebelum trading (scale 1-5 dengan emoji)
- [x] Catatan rencana trading hari ini (pair yang akan diwatch, bias market)
- [x] Review akhir hari (refleksi, apa yang berjalan baik/buruk)
- [x] **Apakah hari ini mengikuti Risk Rules?** (checkbox)
- [x] Link ke trade yang dilakukan hari tersebut

### 3.5 Statistik & Analitik
- [x] Filter by: rentang tanggal, pair, session, setup, result, direction
- [x] Metrics: Win Rate, Loss Rate, Profit Factor, Expectancy, Average Win, Average Loss, Max Drawdown, Consecutive Win/Loss
- [x] **Net P/L vs Gross P/L** (perbandingan sebelum dan sesudah biaya)
- [x] Performa per pair/instrument (tabel ranking)
- [x] Performa per setup (tabel ranking)
- [x] Performa per session (Asia/London/NY/Overlap)
- [x] Performa per day of week (Senin-Jumat)
- [x] **Performa per timeframe** (M15, H1, H4, dll)
- [x] **Rata-rata durasi trade** (dari open_time ke close_time)
- [x] Distribusi kesalahan (bar chart dari field `mistake`)
- [x] **`followed_plan` rate** — berapa % trade yang sesuai dengan plan

### 3.6 Manajemen Akun Trading
- [x] User bisa punya lebih dari satu akun trading (live/demo/prop)
- [x] Setiap trade terhubung ke satu akun
- [x] Equity tracking per akun
- [x] Setting modal awal per akun
- [x] **Risk Rules per akun** — max loss harian (nominal & %), max trades per hari, max drawdown %

### 3.7 Target & Goal Tracking *(Fitur Baru)*
- [x] Set target profit per bulan (nominal atau %)
- [x] Set target win rate per bulan
- [x] Set target max drawdown per bulan
- [x] Progress bar aktual vs target di Dashboard
- [x] History target vs pencapaian per bulan (tabel)

### 3.8 Export Data *(Fitur Baru)*
- [x] Export trade list ke **CSV**
- [x] Export trade list ke **Excel (.xlsx)** dengan formatting
- [x] Export statistik bulanan ke Excel
- [x] Filter export mengikuti filter yang aktif di halaman Trades

### 3.9 Pengaturan (Settings)
- [x] Profil pengguna (nama, email, password, **timezone**, preferensi mata uang)
- [x] Manajemen akun trading (termasuk risk rules per akun)
- [x] Daftar pair/instrument yang tersimpan
- [x] Daftar setup yang tersimpan
- [x] **Dark mode toggle**
- [x] **Tag default** — daftar tag yang sering dipakai (FOMO, Revenge, Early Entry, dll)

---

## 4. Struktur Database

> **Penting:** Tambahkan database index pada kolom `user_id`, `date`, `pair`, `result` di tabel `trades` untuk performa query yang optimal.

### Tabel: `users`
```sql
id
name
email
password
currency_preference    -- default: 'USD'
timezone               -- default: 'UTC', contoh: 'Asia/Jakarta'
active_account_id      -- FK nullable ke trading_accounts (akun yang sedang aktif dipilih)
remember_token
timestamps
```

### Tabel: `trading_accounts`
```sql
id
user_id               -- FK users
name                  -- "Prop Firm A", "Live MT5", dll
broker                -- nama broker
account_type          -- enum: live | demo | prop
account_number        -- nullable
initial_balance       -- modal awal
current_balance       -- balance terkini (di-update otomatis setiap ada trade)
currency              -- USD | IDR | EUR | dll
-- Risk Rules
max_daily_loss        -- nullable decimal: max loss per hari (nominal)
max_daily_loss_pct    -- nullable decimal: max loss per hari (%)
max_trades_per_day    -- nullable integer: max jumlah trade per hari
max_drawdown_pct      -- nullable decimal: max total drawdown (%)
--
is_active             -- boolean
notes
timestamps
```

### Tabel: `trades`
```sql
id
user_id               -- FK users
trading_account_id    -- FK trading_accounts
instrument_id         -- FK instruments (nullable)
setup_id              -- FK setups (nullable)
date
open_time             -- time nullable
close_time            -- time nullable
session               -- enum: asia | london | new_york | overlap
pair                  -- string (tetap disimpan meski ada instrument_id, untuk fleksibilitas)
direction             -- enum: buy | sell
entry_price
stop_loss
take_profit_1
take_profit_2         -- nullable
take_profit_3         -- nullable
close_price
lot_size
risk_amount
commission            -- default 0
swap                  -- default 0
result                -- enum: win | loss | breakeven | partial
profit_loss           -- bersih (setelah commission & swap)
profit_loss_gross     -- kotor (sebelum biaya)
pips
rr_ratio              -- aktual
rr_planned            -- yang direncanakan saat entry
setup                 -- string
timeframe
followed_plan         -- boolean default true
mistake               -- text nullable
notes                 -- text nullable
screenshot_before     -- string nullable
screenshot_after      -- string nullable
tags                  -- JSON nullable
timestamps

-- INDEX:
INDEX idx_user_date (user_id, date)
INDEX idx_user_pair (user_id, pair)
INDEX idx_user_result (user_id, result)
INDEX idx_account (trading_account_id)
```

### Tabel: `daily_journals`
```sql
id
user_id               -- FK users
trading_account_id    -- FK trading_accounts
date
UNIQUE (user_id, trading_account_id, date)
mood_before           -- integer 1-5
plan                  -- text (rencana trading)
review                -- text (refleksi akhir hari)
followed_risk_rules   -- boolean nullable
timestamps
```

### Tabel: `instruments`
```sql
id
user_id               -- FK users
symbol                -- "XAUUSD"
name                  -- "Gold"
category              -- enum: forex | commodity | crypto | index | stock
pip_value             -- decimal nullable (nilai 1 pip dalam currency akun)
is_active
timestamps
```

### Tabel: `setups`
```sql
id
user_id               -- FK users
name                  -- "Order Block + FVG"
description           -- text nullable
is_active
timestamps
```

### Tabel: `monthly_targets` *(Baru)*
```sql
id
user_id               -- FK users
trading_account_id    -- FK trading_accounts
year                  -- integer
month                 -- integer (1-12)
target_profit         -- decimal nullable
target_win_rate       -- decimal nullable (%)
target_max_drawdown   -- decimal nullable (%)
timestamps

UNIQUE (user_id, trading_account_id, year, month)
```

---

## 5. Struktur Direktori Proyek

```
trader-book/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── DashboardController.php
│   │   │   ├── TradeController.php
│   │   │   ├── DailyJournalController.php
│   │   │   ├── StatisticController.php
│   │   │   ├── TradingAccountController.php
│   │   │   ├── InstrumentController.php
│   │   │   ├── SetupController.php
│   │   │   ├── MonthlyTargetController.php
│   │   │   ├── ExportController.php
│   │   │   └── ProfileController.php
│   │   ├── Requests/
│   │   │   ├── StoreTradeRequest.php
│   │   │   ├── UpdateTradeRequest.php
│   │   │   ├── StoreTradingAccountRequest.php
│   │   │   ├── StoreDailyJournalRequest.php
│   │   │   ├── StoreMonthlyTargetRequest.php
│   │   │   └── UpdateProfileRequest.php
│   │   └── Resources/
│   │       ├── TradeResource.php
│   │       └── TradingAccountResource.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Trade.php
│   │   ├── TradingAccount.php
│   │   ├── DailyJournal.php
│   │   ├── Instrument.php
│   │   ├── Setup.php
│   │   └── MonthlyTarget.php
│   ├── Services/
│   │   ├── StatisticService.php
│   │   └── RiskRuleService.php
│   └── Exports/
│       └── TradesExport.php
├── database/
│   ├── migrations/
│   ├── factories/
│   │   └── TradeFactory.php
│   └── seeders/
│       └── DatabaseSeeder.php
├── resources/
│   └── js/
│       ├── Pages/
│       │   ├── Auth/
│       │   │   ├── Login.vue
│       │   │   ├── Register.vue
│       │   │   └── ForgotPassword.vue
│       │   ├── Dashboard/
│       │   │   └── Index.vue
│       │   ├── Trades/
│       │   │   ├── Index.vue
│       │   │   ├── Create.vue
│       │   │   ├── Edit.vue
│       │   │   └── Show.vue
│       │   ├── Journal/
│       │   │   ├── Index.vue
│       │   │   └── Show.vue
│       │   ├── Statistics/
│       │   │   └── Index.vue
│       │   └── Settings/
│       │       ├── Profile.vue
│       │       ├── Accounts.vue
│       │       ├── Instruments.vue
│       │       ├── Setups.vue
│       │       └── Targets.vue
│       ├── Components/
│       │   ├── Trade/
│       │   │   ├── TradeForm.vue
│       │   │   ├── TradeTable.vue
│       │   │   ├── TradeCard.vue
│       │   │   └── TradeFilters.vue
│       │   ├── Dashboard/
│       │   │   ├── StatCard.vue
│       │   │   ├── EquityCurveChart.vue
│       │   │   ├── DailyPLChart.vue
│       │   │   ├── RecentTrades.vue
│       │   │   ├── MonthlyTargetProgress.vue
│       │   │   └── RiskRuleAlert.vue
│       │   ├── Statistics/
│       │   │   ├── MetricCard.vue
│       │   │   ├── PairPerformanceTable.vue
│       │   │   ├── SetupPerformanceTable.vue
│       │   │   ├── SessionChart.vue
│       │   │   └── MistakeDistributionChart.vue
│       │   └── UI/
│       │       ├── AppButton.vue
│       │       ├── AppInput.vue
│       │       ├── AppSelect.vue
│       │       ├── AppModal.vue
│       │       ├── AppBadge.vue
│       │       ├── AppTable.vue
│       │       ├── AppPagination.vue
│       │       ├── AppAlert.vue
│       │       ├── AppToast.vue
│       │       ├── AppProgressBar.vue
│       │       └── AppEmptyState.vue
│       └── Layouts/
│           └── AppLayout.vue
└── routes/
    └── web.php
```

---

## 6. Routing

```php
// routes/web.php

// Auth (dari Breeze — tidak perlu diubah)
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create']);
    Route::post('/register', [RegisteredUserController::class, 'store']);
    Route::get('/login', [AuthenticatedSessionController::class, 'create']);
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create']);
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Export (HARUS sebelum Route::resource trades agar /trades/export tidak tertangkap sebagai trades.show)
    Route::get('/trades/export', [ExportController::class, 'trades'])->name('trades.export');

    // Trade Journal
    Route::resource('trades', TradeController::class);

    // Daily Journal
    Route::resource('journals', DailyJournalController::class)->only(['index', 'show', 'store', 'update']);

    // Statistics
    Route::get('/statistics', [StatisticController::class, 'index'])->name('statistics.index');

    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
        Route::patch('/profile', [ProfileController::class, 'update']);
        Route::delete('/profile', [ProfileController::class, 'destroy']);

        Route::resource('accounts', TradingAccountController::class)->except(['show']);
        Route::resource('instruments', InstrumentController::class)->except(['show']);
        Route::resource('setups', SetupController::class)->except(['show']);

        // Monthly Targets
        Route::get('/targets', [MonthlyTargetController::class, 'index'])->name('targets.index');
        Route::post('/targets', [MonthlyTargetController::class, 'store'])->name('targets.store');
        Route::put('/targets/{monthlyTarget}', [MonthlyTargetController::class, 'update'])->name('targets.update');
    });

    // Switch active account
    Route::post('/switch-account', [TradingAccountController::class, 'switchActive'])->name('accounts.switch');

    // Logout
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
```

---

## 7. Urutan Pengerjaan (Step-by-Step)

Ikuti urutan ini secara berurutan. Setiap step harus selesai sebelum ke step berikutnya.

---

### STEP 1 — Setup Project Laravel
```
1. Install Laravel baru:
   composer create-project laravel/laravel .

2. Install Laravel Breeze:
   composer require laravel/breeze --dev

3. Jalankan (TANPA --ssr untuk mengurangi kompleksitas, app ini behind auth wall):
   php artisan breeze:install vue

4. Install dependencies:
   npm install

5. Konfigurasi .env:
   DB_DATABASE=trader_book
   DB_USERNAME=root
   DB_PASSWORD=

6. Buat database MySQL:
   CREATE DATABASE trader_book;

7. Jalankan migrasi awal:
   php artisan migrate

8. Install package tambahan (npm):
   npm install @heroicons/vue vue-chartjs chart.js dayjs @vueuse/core

9. Install package tambahan (composer):
   composer require maatwebsite/excel

10. Pastikan dev server berjalan:
    npm run dev       (terminal 1)
    php artisan serve (terminal 2)

11. Akses http://localhost:8000 — halaman welcome Breeze harus tampil.
```

---

### STEP 2 — Migrasi Database
```
Buat migration untuk semua tabel berikut (URUTAN WAJIB karena foreign key):

1. Modifikasi migration users yang sudah ada:
   Tambahkan kolom: currency_preference, timezone, active_account_id (nullable FK)

2. create_trading_accounts_table
   Kolom risk rules wajib ada: max_daily_loss, max_daily_loss_pct, max_trades_per_day,
   max_drawdown_pct, current_balance

3. Tambahkan FK active_account_id ke tabel users setelah trading_accounts dibuat:
   $table->foreignId('active_account_id')->nullable()->constrained('trading_accounts')->nullOnDelete();

4. create_instruments_table (dengan kolom pip_value)

5. create_setups_table

6. create_trades_table
   Kolom baru wajib ada: open_time, close_time, close_price, take_profit_1, take_profit_2,
   take_profit_3, commission (default 0), swap (default 0), profit_loss_gross, rr_planned,
   followed_plan (default true), instrument_id (FK nullable), setup_id (FK nullable)
   Tambahkan INDEX:
     $table->index(['user_id', 'date']);
     $table->index(['user_id', 'pair']);
     $table->index(['user_id', 'result']);
     $table->index('trading_account_id');

7. create_daily_journals_table
   Kolom baru: followed_risk_rules (boolean nullable)
   UNIQUE constraint: unique(['user_id', 'trading_account_id', 'date'])

8. create_monthly_targets_table
   Kolom: user_id, trading_account_id, year, month, target_profit, target_win_rate,
   target_max_drawdown
   UNIQUE constraint: unique(['user_id', 'trading_account_id', 'year', 'month'])

Jalankan: php artisan migrate
```

---

### STEP 3 — Model & Relationships
```
Buat atau update Model beserta $fillable, $casts, dan relasi:

1. User.php
   - $fillable: name, email, password, currency_preference, timezone, active_account_id
   - $casts: active_account_id => integer
   - hasMany: TradingAccount, Trade, DailyJournal, Instrument, Setup, MonthlyTarget
   - belongsTo: TradingAccount (activeAccount - foreign key: active_account_id)

2. TradingAccount.php
   - $fillable: semua kolom termasuk risk rule columns
   - $casts: is_active => boolean, max_trades_per_day => integer
   - belongsTo: User
   - hasMany: Trade, DailyJournal, MonthlyTarget
   - Method: recalculateBalance() — update current_balance dari initial_balance + sum(profit_loss)

3. Trade.php
   - $fillable: semua kolom
   - $casts: tags => array, followed_plan => boolean
   - belongsTo: User, TradingAccount
   - belongsTo: Instrument (nullable)
   - belongsTo: Setup (nullable)

4. DailyJournal.php
   - $fillable: semua kolom
   - $casts: followed_risk_rules => boolean
   - belongsTo: User, TradingAccount

5. Instrument.php
   - $fillable: user_id, symbol, name, category, pip_value, is_active
   - belongsTo: User; hasMany: Trade

6. Setup.php
   - $fillable: user_id, name, description, is_active
   - belongsTo: User; hasMany: Trade

7. MonthlyTarget.php
   - $fillable: semua kolom
   - belongsTo: User, TradingAccount

Tambahkan local scope `forUser()` di setiap model:
  public function scopeForUser($query) {
      return $query->where('user_id', auth()->id());
  }
```

---

### STEP 4 — Layout Utama & UI Components
```
Buat komponen layout utama Vue yang akan dipakai semua halaman:

1. resources/js/Layouts/AppLayout.vue
   - Sidebar navigasi kiri: Dashboard, Trades, Journal, Statistics, Settings
   - Topbar: nama user, dropdown akun trading aktif (switch account), dark mode toggle, logout
   - Slot konten utama dengan padding dan max-width yang konsisten
   - Sidebar collapse menjadi off-canvas drawer di layar mobile (hamburger button)

2. Mekanisme switch account:
   - Simpan active_account_id di database (kolom users.active_account_id)
   - Dropdown di topbar memanggil POST /switch-account via useForm dari Inertia
   - Setelah switch, halaman akan reload via Inertia.visit('/') agar data refresh

3. Konfigurasi dark mode di tailwind.config.js:
   darkMode: 'class'
   (Tailwind akan mengaktifkan dark: variants saat class 'dark' ada di element <html>)

4. Buat UI Components di resources/js/Components/UI/:
   AppButton.vue     — variant: primary,secondary,danger,ghost; size: sm,md,lg; prop loading
   AppInput.vue      — label, error, prefix/suffix icon slot
   AppSelect.vue     — label, error, options array [{value,label}]
   AppModal.vue      — size: sm,md,lg; slots: header, default, footer
   AppBadge.vue      — variant: win,loss,breakeven,partial,buy,sell,info
   AppTable.vue      — responsive wrapper, slots: thead, tbody
   AppPagination.vue — terima object paginator dari Laravel (links, current_page, dll)
   AppAlert.vue      — variant: success,error,warning,info; dismissible
   AppToast.vue      — global toast (teleport ke body), subscribe ke flash props
   AppProgressBar.vue— props: value, max, color
   AppEmptyState.vue — props: icon, title, description; slot: action button

5. Tambahkan AppToast ke AppLayout.vue, subscribe ke $page.props.flash
```

---

### STEP 5 — Fitur Settings: Akun Trading + Risk Rules
```
Tujuan: User HARUS punya akun trading sebelum bisa mencatat trade.

Backend:
1. TradingAccountController: index, store, update, destroy, switchActive
   - switchActive(): update users.active_account_id, return redirect back
   - Saat store() pertama kali: otomatis set active_account_id ke akun baru
2. StoreTradingAccountRequest: validasi semua field termasuk risk rule columns
3. Return via Inertia::render('Settings/Accounts', [...])

Frontend:
1. Pages/Settings/Accounts.vue
   - Kartu per akun (bukan tabel): nama, broker, type badge, balance, status aktif
   - Badge "ACTIVE" pada akun yang sedang aktif
   - Modal tambah/edit akun:
     * Section umum: nama, broker, account_type, account_number, initial_balance, currency
     * Section Risk Rules (collapsible): max_daily_loss, max_daily_loss_pct,
       max_trades_per_day, max_drawdown_pct
   - Tombol "Set as Active" per akun
   - Tombol hapus dengan AppModal konfirmasi
```

---

### STEP 6 — Fitur Settings: Instruments, Setups & Targets
```
Backend:
1. InstrumentController: index, store, update, destroy
2. SetupController: index, store, update, destroy
3. MonthlyTargetController:
   - index(): list target semua bulan untuk akun aktif
   - store(): upsert via updateOrCreate(year+month+account_id, target_data)
   - update(): update target yang sudah ada

Frontend:
1. Pages/Settings/Instruments.vue
   - Tabel CRUD: symbol, name, category badge, pip_value, status toggle
   - Modal tambah/edit instrument

2. Pages/Settings/Setups.vue
   - Tabel CRUD: name, description, status toggle
   - Modal tambah/edit setup

3. Pages/Settings/Targets.vue
   - Tabel target per bulan (tampilkan 6 bulan terakhir + bulan depan)
   - Kolom: Bulan, Target Profit, Target Win Rate, Max DD, Status (Achieved/Missed/Ongoing)
   - Inline edit atau modal untuk set/update target per baris

Tambahkan default data di DatabaseSeeder (instruments & setups umum).
```

---

### STEP 7 — Fitur Trade Journal (CRUD)
```
Ini adalah fitur INTI proyek.

Backend:
1. TradeController:
   - index()  : list trade + filter (date_from,date_to,pair,result,session,direction,account_id)
                + paginate(20)
   - create() : pass ke view: accounts, instruments (aktif milik user), setups (aktif milik user)
   - store()  : simpan trade, lalu panggil $account->recalculateBalance()
   - show()   : detail 1 trade (load relasi instrument, setup)
   - edit()   : form edit, pass data yang sama dengan create()
   - update() : update trade, lalu panggil $account->recalculateBalance()
   - destroy(): hapus trade, lalu panggil $account->recalculateBalance()

2. StoreTradeRequest & UpdateTradeRequest: validasi lengkap semua field
   - take_profit_2, take_profit_3 bersifat nullable
   - commission dan swap default ke 0 jika tidak diisi
   - instrument_id dan setup_id nullable

3. Upload screenshot:
   - Simpan di storage/app/public/screenshots/{user_id}/
   - Validasi: max 5MB, mime: jpg/jpeg/png/webp
   - Jalankan php artisan storage:link sekali saat setup

4. RiskRuleService::checkDailyStatus(account, date):
   - Cek apakah trade baru akan melebihi max_daily_loss atau max_trades_per_day
   - Dipanggil di store() — berikan WARNING di response (bukan block/reject)

Frontend:
1. Pages/Trades/Index.vue
   - Tabel: date, pair, direction (badge), session, setup, entry, SL, TP1, lot, P/L, result (badge)
   - Filter bar: date range picker, pair dropdown, result, session, direction
   - Tombol export (dropdown: CSV / Excel) di sebelah filter
   - Klik baris → navigate ke Show
   - Tombol "+ Add Trade"
   - Pagination menggunakan AppPagination

2. Components/Trade/TradeForm.vue (dipakai di Create & Edit)
   Section 1 — Info Dasar: date, open_time, close_time, account, pair (autocomplete),
               direction, session, timeframe
   Section 2 — Harga: entry, SL, TP1, TP2 (toggle show), TP3 (toggle show), close_price
   Section 3 — Risk & Result: lot, risk_amount, commission, swap, result, profit_loss
               (profit_loss_gross auto hitung, profit_loss = gross - commission - |swap|)
   Section 4 — Analisis: setup (autocomplete), rr_planned (auto), rr_ratio (auto dari close_price),
               followed_plan checkbox, tags input (multi-tag)
   Section 5 — Evaluasi: mistake (muncul hanya jika result = loss atau partial), notes
   Section 6 — Screenshot: upload drag & drop dengan preview thumbnail

   Vue computed/watch untuk kalkulasi otomatis:
   - rr_planned saat entry/SL/TP1 berubah
   - rr_ratio saat close_price berubah
   - profit_loss saat commission/swap berubah

3. Pages/Trades/Create.vue — wrapper + pass props ke TradeForm
4. Pages/Trades/Edit.vue — wrapper + pass trade data ke TradeForm
5. Pages/Trades/Show.vue
   - Layout 2 kolom: kiri (semua field detail), kanan (screenshot dalam lightbox)
   - Badge result + direction di header
   - Tombol Edit & Delete di atas halaman
```

---

### STEP 8 — Export Data
```
Backend:
1. app/Exports/TradesExport.php (maatwebsite/excel)
   - Implementasi: FromQuery, WithHeadings, WithMapping, WithStyles
   - Terima filter query yang sama dengan TradeController@index
   - Heading: Date, Open Time, Close Time, Pair, Direction, Session, Setup, Timeframe,
     Entry, SL, TP1, Close, Lot, Risk, Commission, Swap, Result, P/L Gross, P/L Net, RR Planned,
     RR Actual, Followed Plan, Mistake, Tags

2. ExportController::trades():
   - Validasi filter input (date_from, date_to, pair, result, dll)
   - ?format=csv  → return download CSV
   - ?format=xlsx → return download Excel (.xlsx)
   - Default format: xlsx

Frontend:
1. Di Pages/Trades/Index.vue tambahkan tombol Export di sebelah filter:
   - Dropdown menu: "Export CSV" | "Export Excel"
   - Gunakan window.location.href = '/trades/export?format=xlsx&{filter_params}'
   - (Bukan Inertia visit — harus direct link agar browser trigger download)
   - Build query string dari filter yang sedang aktif
```

---

### STEP 9 — Fitur Daily Journal
```
Backend:
1. DailyJournalController:
   - index()  : data kalender bulan tertentu (default: bulan ini)
                Return: array tanggal dengan flag has_journal, has_trades, total_pl, trade_count
                Terima query param: ?month=2026-03
   - show()   : detail jurnal 1 hari + semua trade pada hari tersebut
                Jika belum ada jurnal, return null agar frontend tampilkan form kosong
   - store()  : upsert (updateOrCreate berdasarkan user_id + account_id + date)
   - update() : update jurnal

Frontend:
1. Pages/Journal/Index.vue
   - Grid kalender bulanan (7 kolom, Senin-Minggu)
   - Setiap sel hari menampilkan:
     * Dot hijau jika ada jurnal + trade profit
     * Dot merah jika ada jurnal + trade loss
     * Mini badge P/L total jika ada trade
   - Navigasi bulan prev/next via Inertia.visit('?month=YYYY-MM')
   - Klik hari → navigate ke Show

2. Pages/Journal/Show.vue
   - Header: tanggal lengkap, ringkasan trade (jumlah trade, total P/L hari ini)
   - Form mood sebelum trading: 5 tombol emoji (😔😐🙂😊🤩) mewakili skala 1-5
   - Textarea Plan (rencana trading, pair yang diwatch, bias market)
   - Textarea Review (refleksi akhir hari)
   - Checkbox: Apakah hari ini mengikuti Risk Rules?
   - Daftar trade hari tersebut: TradeCard kompak (pair, direction, P/L, result badge)
   - Auto-save via debounce (watch form.data() + delay 1000ms) atau tombol Save manual
```

---

### STEP 10 — Fitur Dashboard
```
Backend:
1. DashboardController::index():
   - Gunakan active_account_id dari user yang login
   - today_summary: jumlah trade hari ini, P/L hari ini, sisa trade slot (dari risk rules)
   - month_summary: win rate, profit factor, total P/L bulan ini
   - monthly_target: ambil dari monthly_targets + kalkulasi aktual bulan ini
   - equity_curve: array {date, cumulative_pl} untuk 90 hari terakhir
   - daily_pl_chart: array {date, pl} untuk 30 hari terakhir
   - recent_trades: 5 trade terbaru
   - risk_rule_status: cek via RiskRuleService::checkDailyStatus()

Frontend:
1. Pages/Dashboard/Index.vue — layout grid 12 kolom

   Row 1 — Stat Cards (4 kartu grid):
     Total Trade Hari Ini | P/L Hari Ini | Win Rate Bulan Ini | Total P/L Bulan Ini

   Row 2 — Risk Rule Alert (conditional):
     RiskRuleAlert.vue — tampil jika mendekati/melampaui max daily loss atau max trades/day

   Row 3 — Charts (2 kolom):
     EquityCurveChart.vue (Line chart, 90 hari) | DailyPLChart.vue (Bar chart, 30 hari)

   Row 4 — Bottom (2 kolom):
     RecentTrades.vue (5 trade terbaru) | MonthlyTargetProgress.vue (progress bars)
```

---

### STEP 11 — Fitur Statistik & Analitik
```
Backend:
1. app/Services/StatisticService.php
   Constructor: menerima Collection of trades yang sudah difilter

   Methods wajib:
   - summary()                 → array semua metric utama
   - winRate()                 → float (%)
   - profitFactor()            → float (INF jika tidak ada loss)
   - expectancy()              → float (per trade)
   - averageWin()              → float
   - averageLoss()             → float (positif)
   - maxDrawdown()             → float (%)
   - consecutiveWinLoss()      → [max_win_streak, max_loss_streak]
   - netVsGross()              → [total_net, total_gross, total_fees]
   - performanceByPair()       → Collection
   - performanceBySetup()      → Collection
   - performanceBySession()    → Collection
   - performanceByTimeframe()  → Collection
   - performanceByDayOfWeek()  → Collection (1=Mon ... 5=Fri)
   - averageDuration()         → float (menit)
   - followedPlanRate()        → float (%)
   - mistakeDistribution()     → Collection (group by mistake)

2. StatisticController::index():
   - Terima filter: date_from, date_to, pair, result, direction, account_id
   - Query trades berdasarkan filter (scope forUser())
   - Instantiasi StatisticService dengan collection
   - Return ke Inertia

Frontend:
1. Pages/Statistics/Index.vue

   Filter Bar (sticky di atas): date range, pair, result, direction, session, tombol Apply

   Section 1 — Metric Cards (grid 4 kolom):
     Win Rate | Profit Factor | Expectancy | Avg Win | Avg Loss | Max Drawdown
     + bonus: Avg RR | Max Win Streak | Max Loss Streak | Followed Plan Rate

   Section 2 — Net vs Gross P/L:
     Tabel: Gross P/L | Total Fees | Net P/L

   Section 3 — Performa per Pair (tabel):
     Pair | Total Trade | Win | Loss | Win Rate | Avg RR | Net P/L

   Section 4 — Performa per Setup (tabel):
     Setup | Total Trade | Win Rate | Avg RR | Net P/L

   Section 5 — Charts (2 kolom):
     Bar: P/L per Session | Bar: P/L per Day of Week

   Section 6 — Charts bawah (2 kolom):
     Bar: P/L per Timeframe | Bar/Pie: Distribusi Kesalahan
```

---

### STEP 12 — Profil & Polish
```
1. Pages/Settings/Profile.vue
   - Form update: nama, email, timezone (dropdown timezone list), currency_preference
   - Form ganti password (section terpisah di bawah)

2. Validasi error:
   - Setiap AppInput menampilkan pesan error dari $page.props.errors
   - Highlight border merah pada field yang error

3. Toast notification (AppToast.vue):
   - Subscribe ke $page.props.flash di AppLayout.vue menggunakan watch
   - Auto-dismiss setelah 4 detik
   - Variant: success (hijau), error (merah), warning (kuning)

4. Responsive Mobile:
   - Sidebar → off-canvas drawer (toggle via hamburger di topbar)
   - Tabel di mobile: scroll horizontal atau card view
   - Gunakan @vueuse/core useBreakpoints untuk kondisional

5. Dark Mode:
   - Toggle button di topbar AppLayout
   - Simpan preferensi ke localStorage via @vueuse/core useStorage('theme')
   - Apply/remove class 'dark' pada document.documentElement
   - Semua komponen UI dan halaman wajib punya variant dark: dari Tailwind

6. Loading state:
   - Tombol submit gunakan :disabled="form.processing" dan spinner icon saat processing

7. Konfirmasi delete:
   - Semua aksi hapus wajib tampilkan AppModal konfirmasi terlebih dahulu

8. Empty State:
   - Gunakan AppEmptyState.vue di halaman Trades, Journal, Statistics
   - Berikan CTA: "Belum ada trade. Tambah trade pertama Anda" dengan tombol
```

---

### STEP 13 — Seeding, Factory & Verifikasi
```
1. TradeFactory.php — generate trade dummy yang realistis:
   - Tanggal acak dalam 90 hari terakhir
   - Pair: random dari XAUUSD, EURUSD, GBPUSD, USDJPY, NAS100
   - Direction: random buy/sell
   - Entry/SL/TP: angka yang masuk akal per pair
   - Result: 55% win, 35% loss, 10% breakeven
   - commission: random antara 2-7
   - followed_plan: 80% true
   - profit_loss_gross: hitung berdasarkan lot dan pips
   - profit_loss: gross - commission - (random swap 0-3)

2. DatabaseSeeder.php:
   a. Demo User:
      email: demo@traderbook.com | password: demo1234
      timezone: Asia/Jakarta | currency: USD

   b. 2 Trading Account untuk demo user:
      - "Demo Account" (type: demo, initial_balance: 10000, currency: USD)
      - "Prop Firm" (type: prop, initial_balance: 50000, currency: USD,
        max_daily_loss: 500, max_trades_per_day: 3, max_drawdown_pct: 8)

   c. Instruments default per user:
      XAUUSD (Gold), EURUSD, GBPUSD, USDJPY, BTCUSDT, NAS100, GBPJPY, AUDUSD

   d. Setups default per user:
      Order Block, Fair Value Gap (FVG), BOS/CHOCH, Supply & Demand,
      Trend Follow, ICT Killzone, Breaker Block

   e. 90 trade dummy via TradeFactory untuk demo user

   f. 30 daily journals untuk 30 hari terakhir

   g. Monthly targets untuk 3 bulan terakhir + bulan ini

3. Jalankan: php artisan db:seed

4. Verifikasi:
   - Login demo@traderbook.com — dashboard tampil dengan data
   - Semua navigasi tidak ada error
   - Filter trade bekerja dengan benar
   - Export CSV dan Excel berhasil download
   - Dark mode toggle berfungsi dan persisten setelah refresh
   - Switch account bekerja dan data berubah
   - Risk Rule alert muncul saat limit tercapai
```

---

## 8. Detail Setiap Fitur

### 8.1 Kalkulasi Otomatis di Form Trade

Saat user mengisi field harga, Vue `watch` menjalankan kalkulasi berikut secara reaktif:

```javascript
// RR yang direncanakan (saat entry/SL/TP1 berubah)
rr_planned = Math.abs(take_profit_1 - entry_price) / Math.abs(entry_price - stop_loss)

// Pips risk
// XAUUSD: pip_size = 0.01 | Forex non-JPY: pip_size = 0.0001
// Forex JPY: pip_size = 0.01 | Index/Crypto: gunakan 1
risk_pips = Math.abs(entry_price - stop_loss) / pip_size

// Estimasi P/L dari close_price (saat close_price berubah)
pips_actual = direction === 'buy'
  ? (close_price - entry_price) / pip_size
  : (entry_price - close_price) / pip_size

profit_loss_gross = pips_actual * pip_value_per_lot * lot_size

// P/L bersih
profit_loss = profit_loss_gross - commission - Math.abs(swap)

// RR aktual
rr_ratio = Math.abs(close_price - entry_price) / Math.abs(entry_price - stop_loss)
```

> `pip_value` diambil dari tabel `instruments` (field `pip_value`). Jika instrument tidak terdaftar, default ke 10 (standar umum forex non-JPY per 0.01 lot).

### 8.2 RiskRuleService

```php
// app/Services/RiskRuleService.php

class RiskRuleService
{
    public function checkDailyStatus(TradingAccount $account, string $date): array
    {
        $todayTrades = Trade::where('trading_account_id', $account->id)
            ->whereDate('date', $date)
            ->get();

        $todayLoss = abs($todayTrades->where('profit_loss', '<', 0)->sum('profit_loss'));
        $todayCount = $todayTrades->count();

        $warnings = [];
        $isBlocked = false;

        if ($account->max_trades_per_day && $todayCount >= $account->max_trades_per_day) {
            $warnings[] = "Sudah mencapai batas {$account->max_trades_per_day} trade hari ini.";
            $isBlocked = true;
        }

        if ($account->max_daily_loss && $todayLoss >= $account->max_daily_loss) {
            $warnings[] = "Sudah mencapai batas max daily loss {$account->currency}{$account->max_daily_loss}.";
            $isBlocked = true;
        }

        return [
            'today_trade_count' => $todayCount,
            'today_loss'        => $todayLoss,
            'warnings'          => $warnings,
            'is_blocked'        => $isBlocked,
        ];
    }
}
```

### 8.3 StatisticService — Formula

```php
// Win Rate
winRate = (jumlah WIN / total trade) * 100

// Profit Factor
profitFactor = sum(profit_loss WHERE result='win') / abs(sum(profit_loss WHERE result='loss'))
// Jika tidak ada loss: return INF, tampilkan sebagai "∞" di frontend

// Expectancy (per trade)
expectancy = (winRate/100 * averageWin) - (lossRate/100 * averageLoss)

// Max Drawdown
// 1. Buat array cumulative P/L berurutan berdasarkan tanggal
// 2. Track running peak
// 3. Drawdown di tiap titik = (current - peak) / peak * 100
// 4. maxDrawdown = nilai minimum dari semua drawdown

// Consecutive Win/Loss
// Loop trades berurutan, hitung streak saat ini
// Simpan max streak yang pernah tercapai

// Net vs Gross
// total_fees = sum(commission) + sum(abs(swap))
// total_gross = sum(profit_loss_gross)
// total_net = sum(profit_loss)
```

### 8.4 Shared Props (Inertia HandleInertiaRequests)

Di `HandleInertiaRequests.php`, share data berikut ke semua halaman:

```php
public function share(Request $request): array
{
    $user = $request->user();

    return [
        ...parent::share($request),
        'auth' => [
            'user' => $user ? [
                'id'                  => $user->id,
                'name'                => $user->name,
                'email'               => $user->email,
                'timezone'            => $user->timezone,
                'currency_preference' => $user->currency_preference,
                'active_account_id'   => $user->active_account_id,
            ] : null,
        ],
        'trading_accounts' => fn () => $user
            ? TradingAccount::where('user_id', $user->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'account_type', 'currency'])
            : [],
        'active_account' => fn () => $user?->active_account_id
            ? TradingAccount::find($user->active_account_id,
                ['id', 'name', 'account_type', 'currency', 'current_balance',
                 'max_daily_loss', 'max_trades_per_day', 'max_drawdown_pct'])
            : null,
        'flash' => [
            'success' => fn () => $request->session()->get('success'),
            'error'   => fn () => $request->session()->get('error'),
            'warning' => fn () => $request->session()->get('warning'),
        ],
    ];
}
```

### 8.5 Design System (Tailwind)

```
Mode Terang:
  Primary: indigo-600 (button, link, active nav)
  Background halaman: gray-50
  Card/Panel: white, shadow-sm, rounded-xl, border border-gray-100
  Sidebar: gray-900, text white
  Text utama: gray-900
  Text sekunder: gray-500

Mode Gelap (dark: prefix):
  Background halaman: gray-950
  Card/Panel: gray-900, border-gray-800
  Sidebar: gray-950
  Text utama: gray-50
  Text sekunder: gray-400

Badge result:
  win:       bg-green-100 text-green-700  dark:bg-green-900/30  dark:text-green-400
  loss:      bg-red-100   text-red-700    dark:bg-red-900/30    dark:text-red-400
  breakeven: bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400
  partial:   bg-blue-100  text-blue-700   dark:bg-blue-900/30   dark:text-blue-400

Badge direction:
  buy:  bg-emerald-100 text-emerald-700
  sell: bg-orange-100  text-orange-700

P/L positif: text-green-600 dark:text-green-400
P/L negatif: text-red-600   dark:text-red-400
```

### 8.6 Update Balance Otomatis di TradingAccount

Setiap kali trade di-`store()`, `update()`, atau `destroy()`, recalculate balance:

```php
// Di TradingAccount model
public function recalculateBalance(): void
{
    $totalPL = $this->trades()->sum('profit_loss');
    $this->current_balance = $this->initial_balance + $totalPL;
    $this->saveQuietly(); // tanpa trigger model events
}
```

Panggil di `TradeController` setelah setiap operasi:
```php
// store()
$trade = Trade::create([...]);
$trade->tradingAccount->recalculateBalance();

// update()
$trade->update([...]);
$trade->tradingAccount->recalculateBalance();

// destroy()
$account = $trade->tradingAccount;
$trade->delete();
$account->recalculateBalance();
```

### 8.7 Screenshot Upload

- Simpan di `storage/app/public/screenshots/{user_id}/`
- Buat symlink sekali: `php artisan storage:link`
- Validasi: max 5MB, mime: `image/jpeg,image/png,image/webp`
- Di form Vue: preview thumbnail via `URL.createObjectURL(file)` setelah file dipilih
- Hapus file lama saat trade dihapus: `Storage::disk('public')->delete($trade->screenshot_before)`

---

## Catatan Penting untuk AI Agent

1. **SELALU scope query ke `user_id` yang sedang login** — jangan pernah return data milik user lain. Gunakan `Trade::forUser()->...` atau `auth()->user()->trades()->...`
2. **Gunakan `authorize()` di FormRequest** untuk resource milik user: `return $this->user()->id === $this->route('trade')->user_id;`
3. **Semua controller method return `Inertia::render()`** — tidak ada Blade template kecuali file awal dari Breeze.
4. **Vue component wajib menggunakan `<script setup>`** dan Composition API — tidak ada Options API.
5. **Gunakan `useForm()` dari Inertia** untuk semua form submission — jangan axios manual kecuali switch-account.
6. **Pagination menggunakan `->paginate(20)`** di controller — pass seluruh object paginator ke Inertia (bukan hanya items-nya).
7. **Flash message**: set di controller `session()->flash('success', '...')`, tampilkan via AppToast yang subscribe ke shared flash props.
8. **File upload**: gunakan `$request->file()->store('screenshots/' . auth()->id(), 'public')` — jangan base64.
9. **Semua tanggal** format `Y-m-d` di database, format tampilan menggunakan Day.js di frontend sesuai timezone user.
10. **Inisialisasi active_account**: di `TradingAccountController::store()`, jika user belum punya `active_account_id`, otomatis set ke akun yang baru dibuat.
11. **TradeController** wajib memanggil `$account->recalculateBalance()` setelah setiap operasi store/update/destroy.
12. **Jangan gunakan flag `--ssr`** saat install Breeze — app ini behind auth wall, tidak memerlukan SSR.
13. **Dark mode**: semua komponen UI harus punya variant `dark:` yang sesuai dari Tailwind.
14. **`instrument_id` dan `setup_id`** di trades bersifat nullable — user boleh mengisi pair dan setup sebagai text bebas.
15. **Export route** harus diletakkan SEBELUM `Route::resource('trades', ...)` agar `/trades/export` tidak dianggap sebagai `trades.show` dengan id `export`.
16. **`commission` dan `swap`** default ke 0 di migration — jangan biarkan nullable agar kalkulasi tidak error.
