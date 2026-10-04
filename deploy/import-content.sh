#!/usr/bin/env bash
# ===== QURBA: download + import verified Quran content on the server (run once) =====
set -euo pipefail
cd /var/www/qurba
mkdir -p storage/app/quran && cd storage/app/quran
curl -fsSL -o quran-uthmani.txt "https://tanzil.net/pub/download/index.php?quranType=uthmani&outType=txt-2&agree=true" || true
curl -fsSL -o quran-data.xml   "https://tanzil.net/res/text/metadata/quran-data.xml"
curl -fsSL -o en.sahih.txt      "https://tanzil.net/trans/?transID=en.sahih&type=txt-2"
curl -fsSL -o ur.jalandhry.txt  "https://tanzil.net/trans/?transID=ur.jalandhry&type=txt-2"
cd /var/www/qurba
echo "If quran-uthmani.txt is empty, upload the same file you used locally (scp) and rerun."
php artisan db:seed --class=QurbaSeeder --force
php artisan qurba:import-quran storage/app/quran/quran-uthmani.txt --edition=uthmani-1.1 --url=https://tanzil.net --license=tanzil-terms
php artisan qurba:import-metadata storage/app/quran/quran-data.xml
php artisan qurba:import-translation storage/app/quran/en.sahih.txt --lang=en --name=Sahih-International --license=tanzil-terms
php artisan qurba:import-translation storage/app/quran/ur.jalandhry.txt --lang=ur --name=Jalandhry --license=tanzil-terms
php artisan db:seed --class=QurbaAudioSeeder --force
php artisan qurba:verify-quran
php artisan qurba:import-tafsir ur-tafsir-bayan-ul-quran --name="Bayan ul Quran" --author="Dr. Israr Ahmad" --lang=ur || echo "!! Tafsir import failed (network?) — rerun this line later"
php artisan qurba:import-dua-dhikr || echo "!! Duas import failed (network?) — rerun this line later"
echo "Approve the tafsir and duas in /admin before they show in production."
echo "Compare the Dataset SHA-256 above with your local one: 9017fffbd70a592438733b748f9704b30b26f091deb6e4828c2c71d82c4c5b3a"
