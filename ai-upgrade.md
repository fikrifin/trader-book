# AI Upgrade Plan (Agent Blueprint)

Dokumen ini adalah planning agent untuk membangun fitur menu AI pada Trader Book.
Fitur utama: user memilih instrument yang disimpan, lalu AI memberikan rekomendasi aksi saat ini (setup, entry idea, risk, invalidation, strategi).

## 1. Tujuan Fitur

1. Menyediakan AI assistant trading berbasis data user + data market.
2. Menjaga rekomendasi tetap aman dengan risk guardrails (bukan sinyal buta).
3. Menyimpan hasil rekomendasi untuk audit trail dan evaluasi kualitas.

## 2. Prinsip Desain

1. AI bersifat decision support, bukan financial advice.
2. Output AI wajib terstruktur JSON agar mudah divalidasi.
3. Rule-based risk filter dieksekusi sebelum hasil ditampilkan.
4. Seluruh secret (API key) hanya diproses di backend.

## 3. Scope MVP (Phase 1)

1. Tambah menu AI di sidebar.
2. Halaman AI berisi:
- pilihan instrument milik user
- pilihan timeframe
- tombol generate recommendation
- hasil rekomendasi terstruktur
3. Integrasi provider AI (Ollama Cloud API).
4. Simpan histori rekomendasi ke DB.
5. Rate limit endpoint AI agar aman dari spam.

## 4. Arsitektur Komponen

### A. Data Source Layer

Data yang dipakai untuk prompt:
1. Instrument terpilih (symbol, category).
2. Last price, price_change_pct, price_updated_at.
3. Ringkasan statistik user (win rate, avg RR, max drawdown sederhana).
4. Risk status user (safe/blocked) dari risk rule service.

### B. AI Provider Layer

Buat service backend: `OllamaCloudService`

Tugas service:
1. Menyusun payload request ke `https://ollama.com`.
2. Menangani auth Bearer key.
3. Retry/backoff untuk status transient error.
4. Mengembalikan output text/JSON yang sudah dibersihkan.

### C. Recommendation Engine Layer

Buat service backend: `AiRecommendationService`

Tugas service:
1. Menyusun prompt system + user context.
2. Meminta output JSON schema ketat.
3. Memvalidasi format dan nilai output.
4. Menjalankan safety filter (contoh: no-trade saat risk blocked).
5. Menyimpan result ke tabel histori.

### D. API Layer

Endpoint minimal:
1. `GET /ai/recommendations` (halaman AI + histori singkat)
2. `POST /ai/recommendations` (generate recommendation)

Request contoh:
- instrument_id
- timeframe
- strategy_preference (optional)

Response contoh:
- bias
- action_plan
- entry_zone
- stop_loss
- take_profit
- risk_pct
- confidence
- invalidation
- rationale
- warnings

## 5. Database Design

Buat tabel `ai_recommendations` dengan kolom:
1. id
2. user_id
3. instrument_id (nullable)
4. timeframe
5. provider (default: ollama_cloud)
6. model
7. prompt_context (json)
8. recommendation (json)
9. confidence (decimal nullable)
10. risk_flags (json nullable)
11. latency_ms (integer nullable)
12. token_usage (json nullable)
13. status (success|blocked|failed)
14. error_message (nullable)
15. created_at, updated_at

Index penting:
1. (user_id, created_at desc)
2. (instrument_id, created_at desc)
3. status

## 6. Konfigurasi Environment

Tambahkan env:
1. OLLAMA_API_KEY=
2. OLLAMA_BASE_URL=https://ollama.com
3. OLLAMA_MODEL=gpt-oss:120b
4. AI_RECOMMENDATION_TIMEOUT=30
5. AI_RECOMMENDATION_RETRY_TIMES=2
6. AI_RECOMMENDATION_RETRY_SLEEP_MS=400
7. AI_RECOMMENDATION_RATE_LIMIT=20

## 7. Prompt Contract (Wajib)

### System Prompt

Instruksi inti:
1. Bertindak sebagai trading assistant konservatif.
2. Dilarang memberi saran yang melanggar risk limits.
3. Jika data tidak cukup, kembalikan status `no_trade`.
4. Wajib output JSON valid tanpa teks tambahan.

### JSON Schema Output

Field minimal:
1. market_bias: bullish|bearish|neutral
2. recommended_action: buy|sell|wait|no_trade
3. entry_zone: string
4. stop_loss: string
5. take_profit: string
6. risk_pct: number
7. confidence: number (0-100)
8. invalidation: string
9. rationale: string[]
10. checklist: string[]
11. warning: string|null

## 8. Safety Guardrails

1. Jika account `is_blocked=true`, paksa output `no_trade`.
2. Tolak recommendation dengan risk_pct di atas batas akun.
3. Jika confidence < threshold (misal 45), ubah jadi `wait`.
4. Jika missing field wajib, set status failed dan log error.

## 9. UI/UX Plan

Halaman: `resources/js/Pages/AI/Index.vue`

Komponen utama:
1. Select instrument.
2. Select timeframe.
3. Optional textarea: context tambahan user.
4. Tombol generate + loading state.
5. Card hasil recommendation.
6. Card disclaimer + risk warning.
7. Table histori rekomendasi terakhir.

Menu sidebar:
1. Tambah item `AI` di `AppLayout`.
2. Route name: `ai.index`.

## 10. Observability

Tambahkan logging event:
1. `ai.recommendation.requested`
2. `ai.recommendation.succeeded`
3. `ai.recommendation.failed`
4. `ai.recommendation.blocked`

Log context:
- user_id
- instrument_id
- model
- latency_ms
- status

## 11. Testing Plan

### Feature Tests

1. User dapat membuka halaman AI (auth required).
2. Generate recommendation berhasil dengan mocked provider response.
3. Risk blocked menghasilkan `no_trade`.
4. Invalid JSON provider menghasilkan status failed.
5. Rate limit berjalan sesuai konfigurasi.

### Unit Tests

1. Schema validator menolak output tidak valid.
2. Safety filter memaksa no_trade pada kondisi tertentu.
3. Prompt builder memuat context penting.

## 12. Rollout Plan

### Phase 1 (MVP)

1. Menu + page + generate recommendation.
2. Simpan histori.
3. Basic guardrails.

### Phase 2

1. Tambah multi-scenario output (Plan A/B/C).
2. Tambah user feedback (helpful/not helpful).
3. Tambah analytics kualitas rekomendasi.

### Phase 3

1. Auto context enrichment (news/sentiment optional).
2. Fine-tuned risk profile per user.
3. AB test prompt template.

## 13. Acceptance Criteria

Fitur dinyatakan siap jika:
1. User bisa generate rekomendasi berdasarkan instrument tersimpan.
2. Output selalu terstruktur dan tervalidasi.
3. Guardrails berjalan (no trade saat blocked).
4. Histori rekomendasi tersimpan dan dapat dilihat.
5. Log dan test minimum lulus.

## 14. Agent Checklist (Execution)

- [x] Tambah route dan controller AI
- [x] Tambah menu AI di layout
- [x] Buat migration tabel ai_recommendations
- [x] Buat model AiRecommendation
- [x] Buat service OllamaCloudService
- [x] Buat service AiRecommendationService
- [x] Implement prompt template + schema validator
- [x] Implement safety filter
- [x] Buat halaman UI AI
- [x] Tambah rate limiting endpoint
- [x] Tambah logging observability
- [x] Tambah feature + unit tests
- [x] Update README dengan env dan runbook

## 15. Catatan Kepatuhan

1. Sertakan disclaimer jelas: bukan nasihat keuangan.
2. Hindari klaim profit pasti.
3. Simpan audit trail rekomendasi untuk troubleshooting.
