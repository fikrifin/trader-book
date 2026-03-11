# UI/UX Upgrade Plan — Trader Book

Tujuan: menjadikan tampilan lebih **modern, interaktif, dan elegan** tanpa mengganti stack atau struktur backend. Semua perubahan murna di layer frontend (Vue 3 + Tailwind CSS).

---

## Analisis Kondisi Saat Ini

| Area | Status | Masalah |
|------|--------|---------|
| Sidebar | `bg-gray-900` polos | Tidak ada gradien, branding minim, active state terlalu flat |
| Stat Cards | `bg-white shadow-sm` polos | Tidak ada ikon, warna, trend indicator |
| Tombol "Add Trade" | Inline class `bg-indigo-600` | Inkonsisten, tidak pakai `AppButton` |
| AppButton | Solid color saja | Tidak ada gradient, tidak ada focus ring |
| AppBadge | Rounded-full, solid bg | Tidak ada dot indicator |
| AppProgressBar | Solid `bg-indigo-600` | Tidak ada animasi, tidak ada gradient |
| AppToast | Plain border box | Tidak ada ikon, tidak ada auto-dismiss, tidak ada progress bar |
| Header | Plain white + border-b | Tidak ada frosted glass |
| Table rows | Tidak ada hover state | Terasa tidak interaktif |
| Charts | Default chart.js style | Tidak ada grid styling, tooltip min |
| Typography | Font-bold biasa | Tidak ada gradient text, hierarchy kurang |
| Transitions | Minimal | Tidak ada page transition, entry animation |
| Color palette | Hanya 3 shade brand | Tidak ada full scale, tidak ada dark mode tokens |
| Forms | Basic input | Tidak ada focus glow, border terlalu plain |
| Skeleton | Tidak ada | Loading state kosong |

---

## Fase 1 — Design System Foundation

### 1.1 Extended Color Palette (`tailwind.config.js`)
Tambah full brand scale + semantic color tokens:
```js
brand: {
    50: '#eef2ff',  100: '#e0e7ff',  200: '#c7d2fe',
    300: '#a5b4fc',  400: '#818cf8',  500: '#6366f1',
    600: '#4f46e5',  700: '#4338ca',  800: '#3730a3',  900: '#312e81',  950: '#1e1b4b',
},
surface: {
    DEFAULT: '#ffffff',
    muted: '#f8fafc',
    subtle: '#f1f5f9',
},
```

### 1.2 CSS Custom Properties (`resources/css/app.css`)
Tambah variable untuk efek glow dan shadow token:
```css
:root {
    --shadow-card: 0 1px 3px 0 rgb(0 0 0 / 0.07), 0 1px 2px -1px rgb(0 0 0 / 0.07);
    --shadow-card-hover: 0 4px 12px 0 rgb(99 102 241 / 0.15), 0 2px 4px -1px rgb(0 0 0 / 0.1);
    --glow-brand: 0 0 0 3px rgb(99 102 241 / 0.2);
}
```

### 1.3 Component: `AppCard.vue` (baru)
Komponen card base yang bisa di-reuse:
- Props: `hoverable` (bool), `gradient` (bool), `padding` (sm/md/lg)
- `hoverable`: tambah `hover:-translate-y-0.5 hover:shadow-[var(--shadow-card-hover)] transition-all duration-200`
- `gradient`: border gradient dengan `before:` pseudo via inline style atau ring trick
- Ganti semua `<div class="rounded-lg bg-white p-4 shadow-sm">` di semua page ke `<AppCard>`

---

## Fase 2 — Sidebar & Layout Redesign

### 2.1 Sidebar Upgrade (`AppLayout.vue`)
- Background: ganti `bg-gray-900` → gradient `bg-gradient-to-b from-gray-950 via-gray-900 to-gray-950`
- Logo area: tambah ikon chart/book SVG kecil di sebelah "Trader Book" dengan warna `text-brand-400`
- User info block di bawah logo: avatar circle (inisial nama) + nama user + badge akun aktif
- Active nav item: ganti `bg-brand-700` → `bg-brand-600/90 shadow-[0_0_12px_rgb(79_70_229/0.4)]` (glow effect)
- Nav hover: `hover:bg-white/5` lebih subtle
- Sidebar footer: tambah separator + link "Help" dan versi app kecil
- Settings submenu: tambah ikon kecil per item (UserIcon, WrenchIcon, dll.)

### 2.2 Header Upgrade
- Background: `bg-white/80 backdrop-blur-md` (frosted glass) + `border-b border-gray-100`
- User section: tampilkan avatar circle (inisial) di sebelah nama
- Account switcher: lebih polished — tampilkan warna dot status (active = hijau) di depan nama akun
- Logout button: ganti `bg-gray-900` → ghost button dengan `hover:bg-red-50 hover:text-red-600` + `ArrowRightOnRectangleIcon`

### 2.3 Bottom Nav Mobile Upgrade
- Active item: tambah indicator bar di atas icon (`absolute top-0 inset-x-2 h-0.5 bg-brand-500 rounded-full`)
- Icon active: `text-brand-600`
- Background: `bg-white/90 backdrop-blur-md`

---

## Fase 3 — Component Upgrades

### 3.1 AppButton Enhancement
- Primary: ganti `bg-indigo-600` → `bg-gradient-to-r from-brand-600 to-brand-500 hover:from-brand-700 hover:to-brand-600`
- Tambah `shadow-sm hover:shadow-md` dan `active:scale-[0.98]` untuk feel interaktif
- Focus ring: `focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2`
- Danger variant: gradient merah serupa
- Loading: ganti spinner polos → spinner lebih polished dengan border gradient

### 3.2 AppBadge Enhancement
- Tambah dot indicator sebelum text: `<span class="mr-1 inline-block size-1.5 rounded-full bg-current" />`
- Variant `win`: `bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200`
- Variant `loss`: `bg-red-50 text-red-700 ring-1 ring-red-200`
- Variant `breakeven`: `bg-amber-50 text-amber-700 ring-1 ring-amber-200`
- Variant `buy`: `bg-sky-50 text-sky-700 ring-1 ring-sky-200`
- Variant `sell`: `bg-orange-50 text-orange-700 ring-1 ring-orange-200`

### 3.3 AppProgressBar Enhancement
- Fill: `bg-gradient-to-r from-brand-500 to-brand-400`
- Tambah animasi shimmer saat `value < 100`: pseudo `animate-pulse` atau CSS `@keyframes shimmer`
- Tambah prop `showLabel` untuk tampilkan persentase di ujung kanan bar
- Tambah prop `color` (success/warning/danger) — auto-ubah warna jika mendekati 100% (warning > 80%, danger > 100%)
- Track background: `bg-gray-100`
- Height: ubah ke `h-2.5`

### 3.4 AppToast Enhancement
- Layout: ikon di kiri (CheckCircleIcon / ExclamationTriangleIcon / XCircleIcon) + text di tengah + close button di kanan
- Auto-dismiss: tambah `setTimeout` 4 detik dengan progress bar bawah yang animate
- Enter/leave transition: slide-in dari kanan (`translate-x-full` → `translate-x-0`) + fade
- Success: border kiri `border-l-4 border-emerald-500 bg-white shadow-lg`
- Error: `border-l-4 border-red-500 bg-white shadow-lg`
- Warning: `border-l-4 border-amber-500 bg-white shadow-lg`

### 3.5 AppInput & AppSelect Enhancement
- Border: `border border-gray-200` → `border border-gray-300 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20`
- Background: `bg-white` dengan subtle `hover:border-gray-400` saat hover
- Label: tambah font-weight `font-medium text-gray-700` (sudah ada, pastikan konsisten)
- Disabled state: `bg-gray-50 text-gray-400 cursor-not-allowed`

### 3.6 AppModal Enhancement
- Backdrop: `bg-black/40 backdrop-blur-sm`
- Modal panel: `bg-white rounded-2xl shadow-2xl ring-1 ring-black/5`
- Enter animation: `scale-95 opacity-0` → `scale-100 opacity-100` + `ease-out duration-200`
- Header: border-b subtle, close button di kanan (`XMarkIcon`)

### 3.7 AppTable Enhancement
- Row hover: `hover:bg-gray-50/80 transition-colors duration-100`
- Header: `bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wider`
- Border: `divide-y divide-gray-100` (lebih terang dari default)
- Sticky header: `sticky top-0 z-10` di thead

### 3.8 AppEmptyState Enhancement
- Tampilkan SVG illustration yang sesuai (chart kosong / no data)
- Text: judul bold + deskripsi muted + CTA button jika relevan
- Background: rounded-2xl bg-gray-50 border-2 border-dashed border-gray-200

---

## Fase 4 — Dashboard Redesign

### 4.1 Stat Cards dengan Ikon & Trend
Setiap kartu stat di Dashboard harus punya:
- Ikon background (rounded-xl, warna sesuai konteks) di pojok kanan atas
- Trend indicator: tampilkan `↑ 12%` dari periode sebelumnya (jika memungkinkan, atau hardcode dari data)
- Color accent: kartu P/L positif punya subtle `border-l-4 border-emerald-400`, negatif `border-l-4 border-red-400`
- Example structure:
```html
<AppCard hoverable>
    <div class="flex items-start justify-between">
        <div>
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">P/L Hari Ini</p>
            <p class="mt-2 text-3xl font-bold text-emerald-600">+$120.50</p>
            <p class="mt-1 text-xs text-gray-400">↑ vs kemarin</p>
        </div>
        <div class="rounded-xl bg-emerald-50 p-3">
            <ArrowTrendingUpIcon class="size-6 text-emerald-500" />
        </div>
    </div>
</AppCard>
```

### 4.2 Chart Enhancements
- Equity chart: tambah gradient fill lebih dramatis, tooltip custom dengan format currency
- Daily P/L chart: tambah zero baseline line, tooltip dengan tanggal lengkap
- Chart container: tambah subtle `ring-1 ring-gray-100` dan `rounded-xl`
- Chart title: pindah ke dalam dengan font lebih kecil dan ikon di sebelahnya

### 4.3 Recent Trades Table
- Ganti table biasa → card list dengan hover lift di mobile
- Tambah direction badge (BUY/SELL) dengan panah icon
- Kolom P/L: font-tabular-nums, warna lebih tegas

### 4.4 Target Progress
- Progress bar dengan gradient + label persentase
- Tambah milestone dots di progress bar (25%, 50%, 75%)

### 4.5 Risk Status Card
- Jika `is_blocked = true`: tampilkan card dengan background `bg-red-50 border border-red-200` + `ShieldExclamationIcon` merah
- Jika aman: `bg-emerald-50 border border-emerald-200` + `ShieldCheckIcon` hijau
- Lebih visual, bukan hanya teks

---

## Fase 5 — Trades Page Upgrade

### 5.1 Filter Panel
- Filter badge count: tampilkan jumlah filter aktif sebagai badge merah di tombol "Show Filters"
  - `<span v-if="activeFilterCount > 0" class="ml-1 rounded-full bg-brand-600 px-1.5 py-0.5 text-xs text-white">{{ activeFilterCount }}</span>`
- Reset individual filter: tambah `×` di setiap filter yang terisi
- Input date: tampilkan range summary di atas tabel: "Showing trades from Jan 1 to Mar 11"

### 5.2 Trade Table/Cards
- Row status: warna latar belakang subtle per result (win = bg-emerald-50/30, loss = bg-red-50/20)
- Quick expand: klik row untuk tampilkan detail kecil di bawahnya (tanpa navigasi)
- Export button: redesign ke icon button + dropdown (CSV / Excel) dengan `ChevronDownIcon`
- Tambah kolom "R:R" di table Desktop

### 5.3 Summary Bar Upgrade
- Summary bar di atas tabel: redesign sebagai row 3 kartu kecil (Total | Win Rate | Net P/L), bukan plain text
- Animasi counter: saat data berubah (filter apply), angka fade in

---

## Fase 6 — Statistics Page Upgrade

### 6.1 Chart Container
- Wrap setiap chart dalam `AppCard` dengan judul section + ikon
- Doughnut chart: tampilkan persentase di tengah donut (center label plugin) — `Win Rate: 62%`
- Bar chart pairs: label sumbu X rotasi 45° jika banyak pair

### 6.2 Performance Tables
- Tambah mini bar visual di kolom Net P/L (progress relative terhadap max value)
- Tambah rank number (#1, #2, #3) di baris terbaik
- Header sort: klik kolom untuk sort ascending/descending (client-side)

### 6.3 Summary Cards
- Kartu `Avg Win` / `Avg Loss` dalam warna semantik (hijau/merah)
- Tambah kartu `Expectancy` (rumus: Win% × AvgWin − Loss% × AvgLoss)

---

## Fase 7 — Journal Calendar Upgrade

### 7.1 Calendar Cell Styling
- Cell BG gradasi berdasarkan P/L magnitude:
  - Profit besar: `bg-emerald-100` → `bg-emerald-200` berdasarkan nilai
  - Loss besar: `bg-red-100` → `bg-red-200`
  - Breakeven: `bg-gray-50`
  - Belum ada trade: `bg-white`
- Cell hover: `hover:ring-2 hover:ring-brand-400 hover:z-10 cursor-pointer`
- Cell tanggal hari ini: ring brand

### 7.2 Journal List Mode
- List item berpotensi card dengan avatar mood emoji lebih besar
- Expandable row untuk snippet notes

---

## Fase 8 — Micro-interactions & Polish

### 8.1 Page Transition (Inertia)
Tambah di `app.js` — Inertia `onStart` / `onFinish` event untuk NProgress-style indicator:
```js
import NProgress from 'nprogress'
router.on('start', () => NProgress.start())
router.on('finish', () => NProgress.done())
```
Install: `npm install nprogress`
CSS: import `nprogress/nprogress.css` + override warna ke brand color

### 8.2 Entry Animations
Tambah `v-motion` atau CSS `@keyframes fadeInUp` untuk kartu:
- Dashboard stat cards: masuk dengan stagger `animation-delay` 0ms, 75ms, 150ms, 225ms
- Gunakan Tailwind + class `animate-fade-in-up` custom di `app.css`

### 8.3 Number Counter Animation
Komponen `AppCounter.vue` baru:
- Animasi angka dari 0 ke nilai target saat komponen mount
- Gunakan `requestAnimationFrame` + easing function
- Dipakai di Dashboard stat cards

### 8.4 Hover Lift on Cards
Semua `AppCard` dengan prop `hoverable`:
```css
transition: transform 150ms ease, box-shadow 150ms ease;
&:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-card-hover);
}
```

### 8.5 Tooltip on Truncated Text
Pakai `title` attribute + custom Tailwind tooltip class untuk teks pair yang panjang

---

## Fase 9 — Skeleton Loaders

### 9.1 `AppSkeleton.vue` Komponen Baru
- Props: `variant` (text/card/chart/table-row), `lines` (jumlah baris)
- Animasi `animate-pulse` dari Tailwind
- `AppSkeleton` variant `card`: kotak abu-abu dengan rounded-xl
- `AppSkeleton` variant `table-row`: baris dengan kolom-kolom abu-abu

### 9.2 Implementasi
- Dashboard: tampilkan 4 skeleton card dan 2 skeleton chart container saat loading (Inertia `$page.props` belum ada)
- Trades table: skeleton 5 baris saat filter sedang diapply
- Dengan Inertia: gunakan `router.on('start'/'finish')` untuk toggle state `isLoading`

---

## Fase 10 — Settings Pages Upgrade

### 10.1 Settings Accounts Page
- Header section dengan ikon dan judul lebih besar
- Account card horizontal dengan avatar/initial, nama, tipe, balance
- Inline edit lebih polished: row berubah jadi form yang smooth dengan transition height
- Delete confirmation: tampilkan nama akun di modal konfirmasi dengan warna merah

### 10.2 Instrumen / Setup / Target Pages
- List item dengan drag handle icon (UX hint, though drag tidak diimplementasikan dulu)
- Empty state dengan CTA jelas

---

## Ringkasan Prioritas Eksekusi

| Fase | Item | Impact | Effort |
|------|------|--------|--------|
| 1 | Design system (warna, CSS vars) | Tinggi | Rendah |
| 2 | Sidebar + Header overhaul | Tinggi | Sedang |
| 3 | AppCard, AppButton, AppBadge, AppProgressBar, AppToast | Tinggi | Sedang |
| 4 | Dashboard stat cards + risk card | Tinggi | Sedang |
| 5 | AppTable hover + AppModal blur | Sedang | Rendah |
| 6 | Trade table styling | Sedang | Rendah |
| 7 | NProgress page transition | Sedang | Rendah |
| 8 | Entry animations (fadeInUp stagger) | Sedang | Rendah |
| 9 | Statistics chart enhancements | Sedang | Sedang |
| 10 | Journal calendar cell gradient | Sedang | Sedang |
| 11 | AppSkeleton + loading states | Rendah | Sedang |
| 12 | AppCounter animation | Rendah | Sedang |

> **Rekomendasi urutan eksekusi**: Fase 1 → Fase 2 → Fase 3 (komponen) → Fase 4 (Dashboard) → Fase 5–6 (Trades) → Fase 7–8 (UX polish) → Fase 9–10 (Statistics, Journal) → Fase 11–12 (optional)

---

## Catatan Teknis

- Semua perubahan **tidak memerlukan** perubahan backend/PHP
- `AppCard.vue` akan menjadi wrapper universal — ganti semua `<div class="rounded-lg bg-white ...">` di semua page
- NProgress memerlukan `npm install nprogress`
- Tidak perlu library animasi eksternal — gunakan Tailwind `transition` + custom `@keyframes` di `app.css`
- Tailwind safelist diperlukan jika ada class yang dibuat dinamis (cek setelah implementasi)
- Semua icon tersedia dari `@heroicons/vue/24/outline` yang sudah terinstall
