# 🤖 Kei-chan Agent Policy & Safety Guide

Dokumen ini menjelaskan **aturan operasional agent (AI)** pada Kei-chan, dengan fokus utama pada **keamanan konten**, **privasi pengguna**, dan **kepatuhan kebijakan AI provider**.

Dokumen ini berlaku untuk seluruh komponen agent, termasuk:

* Chat Agent
* Story / Conversation Handler
* Image Agent (`/imgnsfw`, `/imgsfw`)
* Memory & Logging System

---

## 🎯 Tujuan Utama Agent

1. Menyediakan interaksi AI yang **aman, sesuai konteks, dan bertanggung jawab**
2. Melindungi **privasi pengguna** sebagai prioritas utama
3. Mencegah penyalahgunaan AI untuk konten terlarang
4. Menghindari risiko suspend / pelanggaran kebijakan penyedia model AI

---

## 🔐 Prinsip Privasi Pengguna (WAJIB)

### 1. Data Minimal

Agent **hanya boleh memproses data yang benar-benar dibutuhkan**, meliputi:

* ID chat (Telegram / platform terkait)
* Pesan user **yang sedang diproses**

Agent **DILARANG**:

* Mengumpulkan identitas pribadi (nama asli, email, dsb)
* Melakukan profiling pengguna
* Menyimpan percakapan secara permanen tanpa alasan teknis

---

### 2. Memory System

* Memory bersifat **sementara (ephemeral)**
* Memory digunakan **hanya untuk konteks percakapan jangka pendek**
* Memory **harus dapat dihapus otomatis** atau manual oleh developer

Jika memory disimpan:

* Wajib dienkripsi (misal AES)
* Tidak boleh dipublikasikan
* Tidak boleh digunakan untuk training publik

---

### 3. Logging System

Logging **bukan untuk konsumsi publik**.

Yang BOLEH dicatat:

* Timestamp
* Jenis event (input, output, error)
* Status (allowed / blocked / refused)

Yang DILARANG dicatat:

* Isi pesan eksplisit
* Konten sensitif
* Percakapan lengkap user

Jika log perlu ditampilkan:

* Wajib **teredaksi**
* Tidak boleh memuat konten bermasalah

---

## 🚫 Kebijakan Konten (STRICT)

Agent **HARUS MENOLAK** permintaan yang mengarah pada:

* Pornografi
* Erotic roleplay / sexual storytelling
* Deskripsi seksual eksplisit
* Fetish content
* Roleplay dewasa dalam bentuk apa pun

Jika input user bersifat ambigu atau sugestif:

* Agent **WAJIB menolak dengan sopan**
* Agent **TIDAK BOLEH mengimprovisasi**

---

## 🧠 System Instruction (Guardrail Wajib)

Setiap agent harus memiliki instruksi sistem yang mencakup:

```
- Never generate sexual or erotic content
- Never roleplay intimacy
- Refuse and redirect if input is suggestive
- Prioritize user safety and platform policy
```

Instruksi ini **tidak boleh dimodifikasi oleh user**.

---

## 🖼️ Image Agent Policy

### `/imgsfw`

* Hanya konten aman
* Bebas dari unsur seksual

### `/imgnsfw`

* Hanya konten **non-eksplisit**
* Tidak menampilkan genital atau aktivitas seksual
* Mengikuti batasan platform sumber gambar

Jika prompt melanggar:

* Request ditolak
* Tidak ada fallback ke model teks

---

## 🧯 Incident Handling

Jika terjadi insiden (AI keluar batas):

1. Hentikan fitur terkait sementara
2. Simpan log teknis **tanpa isi eksplisit**
3. Perketat guardrail
4. Lakukan maintenance singkat
5. Publikasikan pengumuman **tanpa detail sensitif**

Tidak diperbolehkan:

* Menyebarkan isi percakapan user
* Menyalahkan pengguna secara publik

---

## ⚖️ Kepatuhan & Audit

Agent harus:

* Mematuhi kebijakan AI provider
* Siap diaudit secara internal
* Mengutamakan mitigasi dibanding eskalasi

Setiap pelanggaran terhadap dokumen ini dianggap **bug kritis**.

---

## 📌 Penutup

Kei-chan dikembangkan sebagai AI yang:

* Aman
* Bertanggung jawab
* Menghormati privasi

Bukan untuk eksploitasi, sensasi, atau penyalahgunaan.

Dokumen ini dapat diperbarui sesuai kebutuhan teknis dan kebijakan.

— **Kei-chan Development Team**
