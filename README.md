# 🧭 Aplikasi Absensi & Patrol Mode (Web & Mobile)

Aplikasi absensi berbasis **web dan mobile** yang dikembangkan menggunakan **CodeIgniter 4.5.3**, dengan fitur utama **QR Attendance** dan **Patrol Mode**.  
Sistem ini dirancang untuk mendukung operasional perusahaan dalam melakukan absensi, pencatatan aktivitas, serta patroli keamanan berbasis lokasi dan QR Code.

_Modifikasi berdasarkan Jagowebdev Absensi Online dengan penambahan beberapa role dan fitur baru._

---

## 🚀 Fitur Utama

### 🔹 1. Absensi via QR Code
- Pengguna melakukan absensi dengan **memindai QR Code statis** yang ditempelkan di titik lokasi tertentu.
- Sistem otomatis mencatat lokasi (latitude & longitude), waktu, dan foto bukti kehadiran.

### 🔹 2. Patrol Mode (Mode Keamanan)
- Saat fitur **Patrol Mode** diaktifkan, petugas keamanan wajib **melakukan scan QR di setiap titik patroli**.
- Setiap titik memiliki **QR Code statis** unik yang mewakili lokasi tertentu (misalnya: pos jaga, area parkir, gudang, dsb).
- Semua aktivitas scan tercatat dan dapat dipantau secara real-time oleh admin atau supervisor.

### 🔹 3. Mode Normal (Patrol Mode Disabled)
- Jika **Patrol Mode** dimatikan, sistem hanya berfungsi untuk **absensi karyawan biasa** dan **pencatatan aktivitas harian (activity log)**.
- Aktivitas dapat disertai foto, deskripsi, serta koordinat lokasi.

### 🔹 4. Role-Based Access Control (RBAC)
Aplikasi ini sudah mengimplementasikan **RBAC (Role-Based Access Control)** untuk mengatur hak akses pengguna.

#### Apa itu RBAC?
**RBAC** adalah sistem kontrol akses berbasis peran (role).  
Artinya, setiap pengguna memiliki **role tertentu** (misalnya: Admin, Supervisor, Security, User) yang menentukan modul, menu, dan fitur apa saja yang dapat diakses.

Contoh implementasi:
- **Admin:** dapat mengelola semua data dan user.
- **Supervisor:** dapat melihat laporan dan aktivitas bawahannya.
- **Security/User:** hanya dapat melakukan absensi dan patroli.

Keamanan dan modularitas sistem menjadi lebih terstruktur karena setiap izin (permission) diatur melalui tabel `role`, `module_permission`, dan `role_module_permission`.

---

## 🧱 Teknologi yang Digunakan
- **Framework:** CodeIgniter 4.5.3  
- **Database:** MariaDB 10.4  
- **Frontend:** HTML5, Bootstrap, jQuery  
- **Mobile:** WebView / PWA (Progressive Web App) kompatibel  
- **Autentikasi:** Session-based dan Token-based  
- **RBAC:** Role & Permission system berbasis module dan menu  

---

## 🗃️ Struktur Database Utama

Struktur database lengkap dapat dilihat pada file SQL dump. Berikut tabel-tabel inti:

| Kategori | Tabel | Deskripsi |
|-----------|--------|-----------|
| **User & Role Management** | `user`, `role`, `user_role`, `module`, `module_permission`, `role_module_permission` | Mengatur autentikasi dan hak akses berbasis role |
| **Absensi & Aktivitas** | `user_presensi`, `activity`, `setting_waktu_presensi` | Menyimpan data kehadiran dan aktivitas pengguna |
| **Patroli Keamanan** | `company_patrol` | Menyimpan titik-titik patroli dan QR statis |
| **Master Data** | `company`, `jabatan`, `wilayah_*` | Data perusahaan, jabatan, dan wilayah administratif |
| **System Support** | `menu`, `menu_role`, `setting`, `tbl_sessions` | Mendukung fungsi internal aplikasi dan pengaturan global |

---

## 🧩 Fitur Teknis Tambahan
- **Static QR Generator** untuk setiap titik patroli (`company_patrol.barcode`)  
- **Geolocation Capture** pada absensi dan aktivitas  
- **Approval Workflow** untuk aktivitas (`activity.status` → pending, approved, rejected)  
- **Logging Sistem**: tabel `user_login_activity` menyimpan histori login pengguna  

---