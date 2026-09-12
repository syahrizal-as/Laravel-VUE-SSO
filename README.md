# Portal Single Sign-On (SSO)

Portal Single Sign-On terpusat untuk ekosistem aplikasi internal (termasuk **Vendor Management System (VMS)**, **Backend ERP**, dll). Pengguna cukup melakukan login satu kali (via akun Google atau kredensial SSO) dan dapat mengakses seluruh aplikasi yang diizinkan tanpa perlu login berulang.

---

## Fitur Utama

- **Google OAuth Login**: Autentikasi mudah dan aman menggunakan akun Google Workspace / Gmail.
- **Katalog Aplikasi Terpusat**: Pengguna langsung melihat daftar aplikasi yang diizinkan untuk akun mereka di dashboard.
- **Manajemen Hak Akses Fleksibel**:
  - Super Admin memiliki akses otomatis ke semua aplikasi.
  - Pengguna reguler dapat diatur aksesnya per aplikasi (1 pengguna bisa mengakses banyak aplikasi).
- **Protokol Tiket Sekali Pakai (One-Time Ticket SSO)**:
  - Tiket unik 64-karakter dengan masa berlaku 2 menit.
  - Proteksi anti-replay (*single-use*).
  - Verifikasi *Server-to-Server* yang aman melalui `POST /api/sso/verify`.
- **Dukungan Multi-Arsitektur Klien**:
  - Mendukung aplikasi **SPA (Vue/React) + Sanctum Token** (sudah diterapkan di VMS).
  - Mendukung aplikasi **Web Session Tradisional** (Laravel Monolith / ERP).

---

## Cara Menjalankan Portal SSO

1. Pastikan dependensi terpasang:
   ```bash
   composer install
   npm install
   ```

2. Konfigurasi file `.env`:
   ```env
   APP_NAME="Portal SSO"
   APP_URL=http://localhost:8000

   # Google OAuth Credentials
   GOOGLE_CLIENT_ID=your_client_id.apps.googleusercontent.com
   GOOGLE_CLIENT_SECRET=your_client_secret
   GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
   ```

3. Jalankan migrasi database:
   ```bash
   php artisan migrate
   ```

4. Jalankan server lokal:
   ```bash
   # Terminal 1: Backend Laravel
   php artisan serve --port=8000

   # Terminal 2: Frontend Vite
   npm run dev
   ```

---

## Menu Administrasi

- **Kelola Aplikasi (`/admin/applications`)**:
  Menambah, mengubah, atau menonaktifkan aplikasi klien serta mengelola `Client ID` dan `Client Secret`.
- **Hak Akses Pengguna (`/admin/users`)**:
  Mengatur status Super Admin dan mencentang aplikasi apa saja yang boleh diakses oleh masing-masing user.

---

## Panduan Integrasi Aplikasi Klien

Untuk panduan lengkap langkah-demi-langkah mengenai cara menghubungkan aplikasi klien (seperti **VMS** atau **Backend ERP**) ke portal SSO ini, silakan baca:

📄 **[SSO_CLIENT_INTEGRATION_GUIDE.md](./SSO_CLIENT_INTEGRATION_GUIDE.md)**

Panduan tersebut mencakup:
1. Konfigurasi `.env` dan `config/services.php` klien.
2. Route dan Controller penanganan callback tiket SSO.
3. Metode pencarian user (`first()` dengan penolakan jika tidak terdaftar).
4. Penanganan token Sanctum untuk SPA Vue dan session untuk backend tradisional.
