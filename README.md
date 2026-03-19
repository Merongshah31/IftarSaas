# 🌙 Mini SaaS: Sistem Pengurusan Iftar Ramadan

## 🚀 Pengenalan

Mini SaaS ini dibangunkan untuk membantu masjid menguruskan **pendaftaran Iftar Ramadan**, **kapasiti peserta**, dan **tajaan makanan** secara sistematik.

Masalah utama yang diselesaikan:

* ❌ Sukar mengurus jumlah peserta berbuka puasa
* ❌ Risiko pembaziran atau kekurangan makanan
* ❌ Tiada sistem jelas untuk mengurus tajaan

✅ Penyelesaian:
Platform ini menyediakan sistem **multi-tenant (per masjid)** untuk:

* Pendaftaran peserta (guest-friendly)
* Kawalan kapasiti automatik
* Pengurusan tajaan (sponsorship)
* Check-in kehadiran menggunakan nombor telefon

---

## 🧠 Konsep Sistem

### 🏢 Multi-Tenant SaaS

Setiap masjid mempunyai data tersendiri menggunakan `tenant_id`.

### 👥 Guest Registration

Pengguna tidak perlu login untuk daftar iftar:

* Nama
* No Telefon
* Bilangan pax

### 📊 Capacity Control

* Sistem mengawal kapasiti setiap hari
* Auto reject jika penuh
* Auto reopen jika ada pembatalan

### ✅ Check-in System

Admin boleh:

* Check-in peserta menggunakan no telefon
* Cancel pendaftaran

System akan:

* Auto tandakan **NO_SHOW** jika tidak hadir

---

## 🏗️ Architecture Overview

### Core Models

* **Tenant (Masjid)**
* **IftarDay**
* **Participant**
* **Sponsorship**

### Relationship

```
Tenant
 └── IftarDay
      ├── Participant
      └── Sponsorship
```

---

## ⚙️ Business Logic

### Participant Status

```
REGISTERED → CHECKED_IN → NO_SHOW
           ↘ CANCELLED
```

### Capacity Rules

* `jumlah_daftar` dikemaskini secara atomic
* REGISTER → tambah pax
* CANCEL → tolak pax
* CHECK-IN → tiada perubahan

---

## 🎯 Ciri-ciri Utama

### 👤 Untuk Pengguna

* Daftar iftar tanpa login
* Pilih tarikh dan jumlah pax
* Sistem auto semak kapasiti

### 🕌 Untuk Admin Masjid

* Urus jadual iftar
* Lihat senarai peserta
* Check-in menggunakan no telefon
* Pantau status kehadiran
* Urus tajaan

### 💰 Sponsorship

* Multiple sponsor per hari
* Status: `PENDING`, `PAID`, `FAILED`

---

## 🧩 Struktur Projek

```
app/
├── Models/
├── Services/
│   ├── ParticipantService.php
│   ├── SponsorshipService.php
│   ├── IftarDayService.php
│   └── CapacityService.php
├── Http/
│   ├── Controllers/
│   └── Middleware/

database/
├── migrations/
├── seeders/

resources/
├── js/ (React Frontend)
└── views/

routes/
├── api.php
└── web.php
```

---

## 🔌 API Structure

* Authentication Routes
* Masjid (Tenant) Management
* IftarDay Management
* Participant Registration
* Sponsorship Management

---

## 🛠️ Teknologi Digunakan

* **Backend:** Laravel
* **Frontend:** React (Vite)
* **Database:** PostgreSQL (Supabase)
* **Deployment:** Render (Backend), Cloudflare Pages (Frontend)

---

## ⚡ Cara Pemasangan (Local Development)

```bash
git clone <URL_REPOSITORI>
cd mini-saas-iftar-ramadan

composer install
npm install

cp .env.example .env

php artisan key:generate
php artisan migrate --seed

npm run dev
php artisan serve
```

---

## 🌐 Deployment

* Backend: Render
* Database: Supabase
* Frontend: Cloudflare Pages

---

## 🧪 Ujian

```bash
php artisan test
```

---

## 🎥 Demo Flow

1. User daftar iftar (guest)
2. Sistem semak kapasiti
3. Admin lihat dashboard
4. Admin check-in peserta
5. Sistem update status secara real-time

---

## 💡 Future Improvements

* QR Code Check-in
* Notification (WhatsApp / Email)
* Multi-masjid organization support
* Payment gateway untuk sponsorship

---

## 🤝 Sumbangan

Sebarang sumbangan amat dialu-alukan!
Sila buka issue atau pull request.

---

## 📜 License

MIT License

---

## 🏆 Hackathon Note

Projek ini dibangunkan sebagai solusi praktikal untuk pengurusan Iftar di masjid, dengan fokus kepada:

* Kesederhanaan penggunaan
* Skalabiliti SaaS
* Real-world applicability
