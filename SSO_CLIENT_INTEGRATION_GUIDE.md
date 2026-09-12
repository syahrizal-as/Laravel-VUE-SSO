# Panduan Lengkap Integrasi Single Sign-On (SSO) Portal

Dokumen ini menjelaskan cara menggunakan Portal SSO serta panduan integrasi ke berbagai aplikasi klien (seperti **Vendor Management System (VMS)**, **Backend ERP**, dan aplikasi lainnya).

---

## Daftar Isi
1. [Arsitektur & Alur Kerja SSO](#1-arsitektur--alur-kerja-sso)
2. [Konfigurasi Google OAuth di Portal SSO](#2-konfigurasi-google-oauth-di-portal-sso)
3. [Pengaturan di Portal SSO (Admin Portal)](#3-pengaturan-di-portal-sso-admin-portal)
   - [3.1 Mendaftarkan Aplikasi Klien](#31-mendaftarkan-aplikasi-klien)
   - [3.2 Mengatur Hak Akses Pengguna](#32-mengatur-hak-akses-pengguna)
4. [Spesifikasi API Server SSO](#4-spesifikasi-api-server-sso)
5. [Implementasi di Aplikasi Klien](#5-implementasi-di-aplikasi-klien)
   - [Contoh Kasus A: Aplikasi SPA + Sanctum Token (Implementasi VMS)](#kasus-a-aplikasi-spa--sanctum-token-kasus-nyata-vms)
   - [Contoh Kasus B: Aplikasi Web Session Tradisional (Contoh: Backend ERP)](#kasus-b-aplikasi-web-session-tradisional-contoh-backend-erp)
6. [Catatan Keamanan & Troubleshooting](#6-catatan-keamanan--troubleshooting)

---

## 1. Arsitektur & Alur Kerja SSO

```
+-------------------+        1. Klik "Buka Aplikasi"       +----------------------+
|                   | -----------------------------------> |                      |
| User di Portal    |                                      | Portal SSO           |
| SSO (:8000)       | <----------------------------------- | (:8000)              |
+-------------------+        2. Redirect ke Callback       +----------------------+
          │                     + ?ticket=XYZ64                    │
          │                                                        │
          │ 3. Browser diarahkan ke                                │
          │    callback URL klien                                  │
          ▼                                                        │
+-------------------+        4. POST /api/sso/verify               │
|                   | ---------------------------------------------+
| Client: VMS / ERP |            (client_id, secret, ticket)       │
| (:8002 / :8001)   |                                              │
|                   | <--------------------------------------------+
+-------------------+        5. Response data user
          │
          │ 6. Pencarian User (first())
          │    - Jika ADA: Buat sesi / token -> Masuk Dashboard
          │    - Jika TIDAK ADA: Tolak -> Redirect login?error=...
          ▼
[Dashboard Klien Terbuka]
```

### Mekanisme Keamanan Tiket:
- Tiket berupa string acak kriptografis 64 karakter (`Str::random(64)`).
- Tiket hanya berlaku **satu kali pakai** (*single-use*).
- Tiket memiliki masa kadaluwarsa singkat (**2 menit**).
- Verifikasi dilakukan secara **Server-to-Server** sehingga `client_secret` tidak pernah bocor ke browser pengguna.

---

## 2. Konfigurasi Google OAuth di Portal SSO

1. Buka [Google Cloud Console](https://console.cloud.google.com/).
2. Masuk ke **APIs & Services > Credentials**.
3. Buat **OAuth 2.0 Client ID** (Web Application):
   - **Authorized JavaScript origins**:
     ```
     http://localhost:8000
     https://sso.perusahaan-anda.com
     ```
   - **Authorized redirect URIs**:
     ```
     http://localhost:8000/auth/google/callback
     https://sso.perusahaan-anda.com/auth/google/callback
     ```
4. Salin **Client ID** dan **Client Secret** ke file `.env` di direktori `sso`:
   ```env
   GOOGLE_CLIENT_ID=your-google-client-id.apps.googleusercontent.com
   GOOGLE_CLIENT_SECRET=GOCSPX-your-secret-key
   GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
   ```
5. Pengguna pertama yang login ke portal otomatis mendapatkan status **Super Admin**.

---

## 3. Pengaturan di Portal SSO (Admin Portal)

### 3.1 Mendaftarkan Aplikasi Klien
1. Buka menu **Kelola Aplikasi** di `/admin/applications`.
2. Klik tombol **Tambah Aplikasi Baru**.
3. Masukkan informasi aplikasi:
   - **Nama Aplikasi**: misal `Vendor Management System` atau `Backend ERP`
   - **Base URL**: URL utama aplikasi (misal `http://localhost:8002`)
   - **Callback URL**: Endpoint callback di aplikasi klien (misal `http://localhost:8002/sso/callback`)
4. Simpan. Portal SSO akan mengenerate:
   - `Client ID` (contoh: `app_Mid2CLy76S2RPo0l029wry1w`)
   - `Client Secret` (contoh: `YVaXnKZ8KCK0E5EO8EdL2NtV5tbmBV7gzFwebwuwHjhlhbWO`)

### 3.2 Mengatur Hak Akses Pengguna
1. Buka menu **Hak Akses Pengguna** di `/admin/users`.
2. Cari pengguna yang ingin diberikan akses, klik tombol **Atur Akses**.
3. Centang aplikasi-aplikasi yang boleh diakses pengguna tersebut (satu pengguna dapat mengakses banyak aplikasi sekaligus).
4. Klik **Simpan Izin Akses**.
> **Catatan:** Pengguna dengan status **Super Admin** otomatis memiliki akses ke seluruh aplikasi tanpa perlu dicentang satu per satu.

---

## 4. Spesifikasi API Server SSO

### Verifikasi Tiket (Server-to-Server)
- **Endpoint**: `POST /api/sso/verify`
- **Headers**:
  ```http
  Content-Type: application/json
  Accept: application/json
  ```
- **Request Body**:
  ```json
  {
    "client_id": "app_Mid2CLy76S2RPo0l029wry1w",
    "client_secret": "YVaXnKZ8KCK0E5EO8EdL2NtV5tbmBV7gzFwebwuwHjhlhbWO",
    "ticket": "64_character_random_ticket_string"
  }
  ```

- **Response Berhasil (`200 OK`)**:
  ```json
  {
    "success": true,
    "message": "SSO ticket verified successfully.",
    "data": {
      "sso_user_id": 2,
      "name": "Syahrizal Alisadikin",
      "email": "user@perusahaan.com",
      "avatar": "https://lh3.googleusercontent.com/...",
      "is_admin": true,
      "role_in_app": "admin"
    }
  }
  ```

- **Response Gagal (`400 Bad Request` / `401 Unauthorized`)**:
  ```json
  {
    "success": false,
    "message": "Tiket SSO tidak valid atau sudah kadaluwarsa."
  }
  ```

---

## 5. Implementasi di Aplikasi Klien

Berikut adalah dua contoh cara penerapan di sisi aplikasi penerima SSO:

---

### Kasus A: Aplikasi SPA + Sanctum Token (Kasus Nyata: VMS)

Metode ini digunakan untuk aplikasi berbasis **Single Page Application (Vue/React)** yang mengandalkan token Bearer/Sanctum dan **menolak login jika email tidak terdaftar sebelumnya di database lokal**.

#### 1. Tambahkan ke file `.env` aplikasi klien:
```env
SSO_BASE_URL=http://localhost:8000
SSO_CLIENT_ID=app_Mid2CLy76S2RPo0l029wry1w
SSO_CLIENT_SECRET=YVaXnKZ8KCK0E5EO8EdL2NtV5tbmBV7gzFwebwuwHjhlhbWO
```

#### 2. Tambahkan ke `config/services.php`:
```php
'sso' => [
    'base_url'      => env('SSO_BASE_URL', 'http://localhost:8000'),
    'client_id'     => env('SSO_CLIENT_ID'),
    'client_secret' => env('SSO_CLIENT_SECRET'),
],
```

#### 3. Daftarkan Route Callback di `routes/web.php`:
*(Pastikan diletakkan di atas rute wildcard `{any?}`)*
```php
use App\Http\Controllers\SsoClientController;

Route::get('/sso/callback', [SsoClientController::class, 'handleCallback'])->name('sso.callback');
```

#### 4. Buat Controller Callback (`app/Http/Controllers/SsoClientController.php`):
```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SsoClientController extends Controller
{
    public function handleCallback(Request $request)
    {
        $ticket = $request->query('ticket');

        if (! $ticket) {
            return redirect('/login?error=' . urlencode('Tiket SSO tidak ditemukan.'));
        }

        $baseUrl = rtrim(config('services.sso.base_url'), '/');
        $clientId = config('services.sso.client_id');
        $clientSecret = config('services.sso.client_secret');

        try {
            $response = Http::asJson()->timeout(10)->post("{$baseUrl}/api/sso/verify", [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'ticket' => $ticket,
            ]);
        } catch (\Throwable $e) {
            Log::error('Koneksi ke server SSO gagal: ' . $e->getMessage());
            return redirect('/login?error=' . urlencode('Gagal terhubung ke server SSO.'));
        }

        if (! $response->successful() || ! $response->json('success')) {
            $msg = $response->json('message') ?? 'Verifikasi tiket SSO gagal.';
            return redirect('/login?error=' . urlencode($msg));
        }

        $ssoUser = $response->json('data');
        $email = $ssoUser['email'] ?? null;

        // Cari user di database lokal menggunakan first()
        $user = User::where('email', $email)->first();

        // JIKA TIDAK ADA, TOLAK AKSES
        if (! $user) {
            return redirect('/login?error=' . urlencode("Akses ditolak: Akun ({$email}) tidak terdaftar di sistem ini."));
        }

        if ($user->disabled) {
            return redirect('/login?error=' . urlencode('Akses ditolak: Akun Anda sedang dinonaktifkan.'));
        }

        // Generate Sanctum token baru
        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        // Siapkan struktur profil user untuk state frontend
        $user->load('roles');
        $userData = [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'roles' => $user->roles->map(fn($role) => ['id' => $role->id, 'name' => $role->name]),
            'permissions' => $user->getAllPermissions()->map(fn($p) => ['id' => $p->id, 'name' => $p->name]),
        ];

        // Tampilkan view redirect untuk menyimpan token di browser
        return response()->view('sso-redirect', [
            'token' => $token,
            'userData' => $userData,
        ]);
    }
}
```

#### 5. Buat View Penyimpan Token (`resources/views/sso-redirect.blade.php`):
```html
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Mengalihkan ke Aplikasi...</title>
</head>
<body>
    <script>
        var token = @json($token);
        var userData = @json($userData);

        // Simpan token & user di localStorage & cookie untuk SPA
        localStorage.setItem('access_token', token);
        localStorage.setItem('user', JSON.stringify(userData));
        document.cookie = "accessToken=" + encodeURIComponent(token) + "; path=/; max-age=86400; SameSite=Lax";

        // Masuk ke dashboard
        window.location.replace('/');
    </script>
</body>
</html>
```

#### 6. Tampilkan Error di Halaman Login Vue (`pages/login.vue`):
Di bagian hook `onMounted`, periksa parameter `route.query.error`:
```ts
onMounted(() => {
  if (route.query.error) {
    errorMessage.value = decodeURIComponent(String(route.query.error))
  }
})
```

---

### Kasus B: Aplikasi Web Session Tradisional (Contoh: Backend ERP)

Untuk aplikasi Laravel berbasis Web Session (Blade / Livewire / Monolith):

```php
// Di SsoClientController aplikasi Backend ERP
$ssoUser = $response->json('data');

$user = User::where('email', $ssoUser['email'])->first();

if (! $user) {
    return redirect('/login')->withErrors([
        'email' => "Akses ditolak: Akun {$ssoUser['email']} belum terdaftar di sistem ERP."
    ]);
}

// Login via session Laravel
Auth::login($user, remember: true);
$request->session()->regenerate();

return redirect()->intended('/dashboard');
```

---

## 6. Catatan Keamanan & Troubleshooting

1. **Tiket Kadaluwarsa**: Tiket hanya berlaku selama 2 menit. Jika proses request callback melebihi 2 menit, tiket otomatis dianggap hangus (*expired*).
2. **Replay Attack Protection**: Setiap tiket yang sudah berhasil diverifikasi langsung di-flag `used_at = now()`. Percobaan verifikasi kedua kali dengan tiket yang sama pasti ditolak.
3. **Penyimpanan Secret**: Jangan pernah membagikan atau mengekspos `client_secret` di kode frontend (Vue/React). Panggilan ke `/api/sso/verify` wajib dilakukan dari backend (PHP/Laravel).
4. **Domain & Port**: Pastikan URL callback yang didaftarkan di menu *Kelola Aplikasi* SSO sesuai dengan port tempat aplikasi klien berjalan (misal `http://localhost:8001` untuk ERP, `http://localhost:8002` untuk VMS).
