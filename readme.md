# SCRIPT PHP untuk Mengecek Validasi Jumlah Data di web KPU

hasil tersimpan di file `result.csv`, hasil lama akan di rename jadi `result_backup_X.csv`
Script ini hanya mengecek:
- Jumlah suara 01 + suara 02 harus sama dengan total suara sah
- Jumlah suara 01 + suara 02 + suara tidak sah harus sama dengan total suara

Jika ditemukan kesalahan, silahkan cek form C1 di web KPU untuk memastikan data mana yang kurang sesuai, lalu laporkan ke KPU.