<?php
// ===== QURBA: base reference data =====
// Surah Arabic names, English meanings and revelation place are left empty on purpose:
// they are filled from the verified source during review, not typed by hand.
namespace Database\Seeders;

use App\Support\Quran\QuranStructure;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QurbaSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('languages')->upsert([
            ['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'direction' => 'ltr', 'ui_enabled' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'ar', 'name' => 'Arabic', 'native_name' => 'العربية', 'direction' => 'rtl', 'ui_enabled' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'ur', 'name' => 'Urdu', 'native_name' => 'اردو', 'direction' => 'rtl', 'ui_enabled' => true, 'created_at' => $now, 'updated_at' => $now],
        ], ['code'], ['name', 'native_name', 'direction', 'ui_enabled', 'updated_at']);

        DB::table('countries')->upsert([
            ['code' => 'PK', 'name' => 'Pakistan', 'default_language' => 'ur', 'default_prayer_method' => 'Karachi', 'default_asr_method' => 'hanafi', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'SA', 'name' => 'Saudi Arabia', 'default_language' => 'ar', 'default_prayer_method' => 'UmmAlQura', 'default_asr_method' => 'standard', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'AE', 'name' => 'United Arab Emirates', 'default_language' => 'ar', 'default_prayer_method' => 'Dubai', 'default_asr_method' => 'standard', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'GB', 'name' => 'United Kingdom', 'default_language' => 'en', 'default_prayer_method' => 'MoonsightingCommittee', 'default_asr_method' => 'standard', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'US', 'name' => 'United States', 'default_language' => 'en', 'default_prayer_method' => 'NorthAmerica', 'default_asr_method' => 'standard', 'created_at' => $now, 'updated_at' => $now],
        ], ['code'], ['name', 'default_language', 'default_prayer_method', 'default_asr_method', 'updated_at']);

        $names = [
            'Al-Fatihah',
            'Al-Baqarah',
            'Aal-Imran',
            'An-Nisa',
            'Al-Ma\'idah',
            'Al-An\'am',
            'Al-A\'raf',
            'Al-Anfal',
            'At-Tawbah',
            'Yunus',
            'Hud',
            'Yusuf',
            'Ar-Ra\'d',
            'Ibrahim',
            'Al-Hijr',
            'An-Nahl',
            'Al-Isra',
            'Al-Kahf',
            'Maryam',
            'Ta-Ha',
            'Al-Anbiya',
            'Al-Hajj',
            'Al-Mu\'minun',
            'An-Nur',
            'Al-Furqan',
            'Ash-Shu\'ara',
            'An-Naml',
            'Al-Qasas',
            'Al-Ankabut',
            'Ar-Rum',
            'Luqman',
            'As-Sajdah',
            'Al-Ahzab',
            'Saba',
            'Fatir',
            'Ya-Sin',
            'As-Saffat',
            'Sad',
            'Az-Zumar',
            'Ghafir',
            'Fussilat',
            'Ash-Shura',
            'Az-Zukhruf',
            'Ad-Dukhan',
            'Al-Jathiyah',
            'Al-Ahqaf',
            'Muhammad',
            'Al-Fath',
            'Al-Hujurat',
            'Qaf',
            'Adh-Dhariyat',
            'At-Tur',
            'An-Najm',
            'Al-Qamar',
            'Ar-Rahman',
            'Al-Waqi\'ah',
            'Al-Hadid',
            'Al-Mujadilah',
            'Al-Hashr',
            'Al-Mumtahanah',
            'As-Saff',
            'Al-Jumu\'ah',
            'Al-Munafiqun',
            'At-Taghabun',
            'At-Talaq',
            'At-Tahrim',
            'Al-Mulk',
            'Al-Qalam',
            'Al-Haqqah',
            'Al-Ma\'arij',
            'Nuh',
            'Al-Jinn',
            'Al-Muzzammil',
            'Al-Muddaththir',
            'Al-Qiyamah',
            'Al-Insan',
            'Al-Mursalat',
            'An-Naba',
            'An-Nazi\'at',
            'Abasa',
            'At-Takwir',
            'Al-Infitar',
            'Al-Mutaffifin',
            'Al-Inshiqaq',
            'Al-Buruj',
            'At-Tariq',
            'Al-A\'la',
            'Al-Ghashiyah',
            'Al-Fajr',
            'Al-Balad',
            'Ash-Shams',
            'Al-Layl',
            'Ad-Duha',
            'Ash-Sharh',
            'At-Tin',
            'Al-Alaq',
            'Al-Qadr',
            'Al-Bayyinah',
            'Az-Zalzalah',
            'Al-Adiyat',
            'Al-Qari\'ah',
            'At-Takathur',
            'Al-Asr',
            'Al-Humazah',
            'Al-Fil',
            'Quraysh',
            'Al-Ma\'un',
            'Al-Kawthar',
            'Al-Kafirun',
            'An-Nasr',
            'Al-Masad',
            'Al-Ikhlas',
            'Al-Falaq',
            'An-Nas'
        ];
        $rows = [];
        foreach ($names as $i => $name) {
            $id = $i + 1;
            $rows[] = ['id' => $id, 'name_simple' => $name, 'ayah_count' => QuranStructure::AYAHS[$id], 'created_at' => $now, 'updated_at' => $now];
        }
        DB::table('quran_surahs')->upsert($rows, ['id'], ['name_simple', 'ayah_count', 'updated_at']);

        // Reciter slots: inactive until audio source + licence are approved
        DB::table('quran_reciters')->upsert([
            ['slug' => 'alafasy', 'name' => 'Mishary Rashid Alafasy', 'style' => 'murattal', 'active' => false, 'sort' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'abdul-basit', 'name' => 'Abdul Basit Abdus Samad', 'style' => 'murattal', 'active' => false, 'sort' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'maher-al-muaiqly', 'name' => 'Maher Al-Muaiqly', 'style' => 'murattal', 'active' => false, 'sort' => 3, 'created_at' => $now, 'updated_at' => $now],
        ], ['slug'], ['name', 'style', 'sort', 'updated_at']);

        // Adhkar categories (content itself comes later, after review)
        $cats = [
            ['morning', 'Morning', 'أذكار الصباح', 'صبح کے اذکار'],
            ['evening', 'Evening', 'أذكار المساء', 'شام کے اذکار'],
            ['after-salah', 'After Salah', 'أذكار بعد الصلاة', 'نماز کے بعد'],
            ['sleep-wake', 'Sleep & Wake', 'النوم والاستيقاظ', 'سونا اور جاگنا'],
            ['travel', 'Travel', 'السفر', 'سفر'],
            ['daily-duas', 'Daily Duas', 'أدعية يومية', 'روزمرہ دعائیں'],
            ['quranic-duas', 'Quranic Duas', 'أدعية قرآنية', 'قرآنی دعائیں'],
        ];
        DB::table('adhkar_categories')->upsert(array_map(fn ($c, $i) => [
            'slug' => $c[0],
            'name' => json_encode(['en' => $c[1], 'ar' => $c[2], 'ur' => $c[3]], JSON_UNESCAPED_UNICODE),
            'sort' => $i + 1,
            'created_at' => $now,
            'updated_at' => $now,
        ], $cats, array_keys($cats)), ['slug'], ['name', 'sort', 'updated_at']);
    }
}
