# SCRIPT PHP untuk Mengecek Validasi Jumlah Data di web KPU

hasil tersimpan di file `result.csv`, hasil lama akan di rename jadi `result_backup_X.csv`
Script ini hanya mengecek:
- Jumlah suara 01 + suara 02 harus sama dengan total suara sah
- Jumlah suara 01 + suara 02 + suara tidak sah harus sama dengan total suara

Jika ditemukan kesalahan, silahkan cek form C1 di web KPU untuk memastikan data mana yang kurang sesuai, lalu laporkan ke KPU.

# Penggunaan
```
php koreksi.php [ Provinsi ] [ Kabupaten ] [ Kecamatan ] [ Desa ] [ -v | --verbose ] [ -nc | --no-cache ]
```
Semua parameter bersifat opsional. Gunakan tanda petik untuk nama daerah yang menggunakan spasi.

    -v --verbose    Verbose mode
    -nc --no-cache  Tidak meload cache

## Contoh
```
php koreksi.php "Jawa Tengah" Banjarnegara
```
Hanya mengecek data pada kabupaten Banjarnegara provinsi Jawa Tengah
