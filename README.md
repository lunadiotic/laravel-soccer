# Ayo Soccer API

REST API untuk manajemen tim sepak bola amatir. Dibangun dengan Laravel 11 dan Laravel Sanctum.

## Persyaratan

- PHP 8.2+
- Composer
- SQLite (default) atau MySQL

## Instalasi

```bash
# Clone repository
git clone <repo-url>
cd ayo-soccer

# Install dependencies
composer install

# Salin file konfigurasi
cp .env.example .env

# Generate app key
php artisan key:generate

# Buat file database SQLite
touch database/database.sqlite

# Jalankan migrasi
php artisan migrate

# Buat symlink storage untuk akses file logo
php artisan storage:link

# Jalankan server
php artisan serve
```

Server berjalan di `http://localhost:8000`.

## Konfigurasi Database

Secara default menggunakan SQLite. Untuk MySQL, ubah `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ayo_soccer
DB_USERNAME=root
DB_PASSWORD=
```

## Autentikasi

API ini menggunakan **Laravel Sanctum** dengan token-based authentication.

1. Daftar atau login untuk mendapatkan token
2. Sertakan token di setiap request sebagai header:
    ```
    Authorization: Bearer <token>
    ```

---

## Endpoint API

### Auth

#### Register

```
POST /api/auth/register
```

Body (JSON):

```json
{
    "name": "Admin XYZ",
    "email": "admin@xyz.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

#### Login

```
POST /api/auth/login
```

Body (JSON):

```json
{
    "email": "admin@xyz.com",
    "password": "password123"
}
```

Response:

```json
{
  "success": true,
  "message": "Login berhasil",
  "data": {
    "user": { ... },
    "token": "1|abc123..."
  }
}
```

#### Logout

```
POST /api/auth/logout
```

Header: `Authorization: Bearer <token>`

---

### Tim (Teams)

#### Daftar semua tim

```
GET /api/teams
```

#### Detail tim

```
GET /api/teams/{id}
```

#### Tambah tim

```
POST /api/teams
Content-Type: multipart/form-data
```

| Field        | Tipe    | Keterangan                         |
| ------------ | ------- | ---------------------------------- |
| name         | string  | Nama tim (wajib)                   |
| logo         | file    | Logo tim, format jpg/png, maks 2MB |
| founded_year | integer | Tahun berdiri (wajib)              |
| address      | string  | Alamat markas (wajib)              |
| city         | string  | Kota markas (wajib)                |

#### Update tim

```
PUT /api/teams/{id}
Content-Type: multipart/form-data
```

Semua field opsional.

#### Hapus tim

```
DELETE /api/teams/{id}
```

Data dihapus secara **soft delete** (tidak benar-benar hilang dari database).

---

### Pemain (Players)

#### Daftar semua pemain

```
GET /api/players
GET /api/players?team_id=1   # filter per tim
```

#### Detail pemain

```
GET /api/players/{id}
```

#### Tambah pemain

```
POST /api/players
Content-Type: application/json
```

```json
{
    "team_id": 1,
    "name": "Budi Santoso",
    "height": 175.5,
    "weight": 68.0,
    "position": "penyerang",
    "jersey_number": 9
}
```

Nilai `position` yang valid: `penyerang`, `gelandang`, `bertahan`, `penjaga_gawang`

> Catatan: Nomor punggung tidak boleh sama dalam satu tim.

#### Update pemain

```
PUT /api/players/{id}
```

#### Hapus pemain

```
DELETE /api/players/{id}
```

---

### Pertandingan (Matches)

#### Daftar semua jadwal

```
GET /api/matches
```

#### Detail jadwal

```
GET /api/matches/{id}
```

#### Tambah jadwal

```
POST /api/matches
Content-Type: application/json
```

```json
{
    "match_date": "2024-08-10",
    "match_time": "15:30",
    "home_team_id": 1,
    "away_team_id": 2
}
```

#### Update jadwal

```
PUT /api/matches/{id}
```

Hanya bisa diubah jika status masih `scheduled`.

#### Hapus jadwal

```
DELETE /api/matches/{id}
```

---

### Hasil Pertandingan

#### Catat hasil pertandingan

```
POST /api/matches/{id}/result
Content-Type: application/json
```

```json
{
    "home_score": 2,
    "away_score": 1,
    "goals": [
        { "player_id": 5, "minute": 23 },
        { "player_id": 5, "minute": 67 },
        { "player_id": 11, "minute": 89 }
    ]
}
```

- `goals` bersifat opsional
- Setelah hasil dicatat, status pertandingan berubah menjadi `finished`

#### Update hasil pertandingan

```
PUT /api/matches/{id}/result
```

Format body sama dengan POST. Data gol lama akan diganti seluruhnya.

#### Lihat hasil pertandingan

```
GET /api/matches/{id}/result
```

---

### Laporan Pertandingan

#### Laporan semua pertandingan

```
GET /api/reports/matches
```

#### Laporan detail satu pertandingan

```
GET /api/reports/matches/{id}
```

Contoh response:

```json
{
    "success": true,
    "data": {
        "match_id": 1,
        "match_date": "2024-08-10",
        "match_time": "15:30:00",
        "home_team": { "id": 1, "name": "Tim Alpha" },
        "away_team": { "id": 2, "name": "Tim Beta" },
        "home_score": 2,
        "away_score": 1,
        "final_status": "Tim Home Menang",
        "top_scorer": {
            "player": { "id": 5, "name": "Budi Santoso" },
            "goal_count": 2
        },
        "home_team_wins": 3,
        "away_team_wins": 1,
        "goals": [
            { "player_id": 5, "minute": 23 },
            { "player_id": 5, "minute": 67 },
            { "player_id": 11, "minute": 89 }
        ]
    }
}
```

**Keterangan field laporan:**

| Field            | Keterangan                                                                   |
| ---------------- | ---------------------------------------------------------------------------- |
| `final_status`   | `Tim Home Menang` / `Tim Away Menang` / `Draw`                               |
| `top_scorer`     | Pemain pencetak gol terbanyak di pertandingan ini                            |
| `home_team_wins` | Akumulasi kemenangan tim home dari pertandingan pertama s/d pertandingan ini |
| `away_team_wins` | Akumulasi kemenangan tim away dari pertandingan pertama s/d pertandingan ini |

---

## Format Response

Semua response menggunakan format JSON yang konsisten:

**Sukses:**

```json
{
  "success": true,
  "message": "Pesan sukses",
  "data": { ... }
}
```

**Gagal validasi (422):**

```json
{
    "success": false,
    "message": "Validasi gagal",
    "errors": {
        "field": ["Pesan error"]
    }
}
```

**Tidak ditemukan (404):**

```json
{
    "success": false,
    "message": "Data tidak ditemukan"
}
```

**Unauthorized (401):**

```json
{
    "message": "Unauthenticated."
}
```

---

## Testing

### Menjalankan Test

Test menggunakan database SQLite **in-memory** sehingga tidak mempengaruhi data development. Tidak perlu konfigurasi tambahan.

```bash
# Jalankan semua test
php artisan test

# Jalankan dengan output detail per assertion
php artisan test --verbose

# Jalankan test suite tertentu saja
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Jalankan satu file test
php artisan test tests/Feature/TeamTest.php

# Jalankan satu test case spesifik
php artisan test --filter=test_can_create_team
```

### Cakupan Test

Total: **58 tests, 184 assertions**

| File              | Jumlah Test | Skenario yang Diuji                                                            |
| ----------------- | :---------: | ------------------------------------------------------------------------------ |
| `AuthTest`        |      7      | Register, login, logout, penolakan request tanpa token                         |
| `TeamTest`        |     10      | CRUD tim, upload logo, validasi field wajib, soft delete                       |
| `PlayerTest`      |     11      | CRUD pemain, filter per tim, nomor punggung unik per tim, validasi posisi      |
| `MatchTest`       |      9      | CRUD jadwal, validasi tim home ≠ away, blokir update jadwal yang sudah selesai |
| `MatchResultTest` |     10      | Catat/update hasil, detail gol, replace gol lama, validasi player exist        |
| `ReportTest`      |      9      | Status akhir (menang/kalah/draw), top scorer, akumulasi kemenangan             |

### Konfigurasi Test

Test dikonfigurasi di `phpunit.xml`. Environment yang digunakan saat testing:

| Variabel        | Nilai      | Keterangan                               |
| --------------- | ---------- | ---------------------------------------- |
| `DB_CONNECTION` | `sqlite`   | Menggunakan SQLite                       |
| `DB_DATABASE`   | `:memory:` | Database di RAM, tidak tersimpan ke file |
| `CACHE_STORE`   | `array`    | Cache di memori                          |
| `BCRYPT_ROUNDS` | `4`        | Hash lebih cepat agar test tidak lambat  |

---

## Keamanan

- Semua endpoint (kecuali register dan login) memerlukan token autentikasi
- Token dibuat saat login dan dihapus saat logout
- Upload file dibatasi hanya gambar (jpg, png) maksimal 2MB
- Validasi input ketat di setiap endpoint
- Soft delete menjaga integritas data historis
- Rate limiting bawaan Laravel aktif
