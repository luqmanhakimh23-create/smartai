SMART AI - HOSTING + GOOGLE SHEETS

1. Upload index.html ke folder public_html hosting Anda.
2. Google Apps Script endpoint sudah dipasang di form.
3. Pastikan Apps Script Web App:
   - Execute as: Me
   - Who has access: Anyone
4. Apps Script harus memiliki doPost(e) yang menerima:
   waktu, nama, whatsapp, email, minat, pengalaman, pesan, status
5. Setelah upload, buka website dan lakukan 1 test submit.
6. Cek apakah baris baru masuk ke Google Sheet.

Catatan:
Website menggunakan POST ke Google Apps Script.
