<?php
// ===== QURBA: starter course catalogue (edit freely in /admin) =====
namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

class QurbaLearnSeeder extends Seeder
{
    public function run(): void
    {
        $c = [
            ['quran-beginners', 'quran', 'all', 'both', null,
                ['en' => 'Quran for Beginners', 'ar' => 'القرآن للمبتدئين', 'ur' => 'ابتدائی قرآن'],
                ['en' => 'Learn the letters, joining and reading with a qualified teacher.', 'ar' => 'تعلّم الحروف والوصل والقراءة مع معلم مؤهل.', 'ur' => 'مستند استاد کے ساتھ حروف، جوڑ اور ناظرہ سیکھیں۔']],
            ['tajweed', 'tajweed', 'all', 'both', null,
                ['en' => 'Tajweed', 'ar' => 'التجويد', 'ur' => 'تجوید'],
                ['en' => 'Improve your recitation with the rules of tajweed.', 'ar' => 'حسّن تلاوتك بأحكام التجويد.', 'ur' => 'تجوید کے قواعد کے ساتھ تلاوت بہتر بنائیں۔']],
            ['hifz', 'hifz', 'all', 'both', '7+',
                ['en' => 'Hifz Program', 'ar' => 'برنامج الحفظ', 'ur' => 'حفظ پروگرام'],
                ['en' => 'Structured memorisation with regular revision and progress reports.', 'ar' => 'حفظ منظم مع مراجعة منتظمة وتقارير تقدم.', 'ur' => 'باقاعدہ دہرائی اور پیش رفت رپورٹس کے ساتھ منظم حفظ۔']],
            ['quran-children', 'quran', 'kids', 'both', '5+',
                ['en' => 'Quran for Children', 'ar' => 'القرآن للأطفال', 'ur' => 'بچوں کے لیے قرآن'],
                ['en' => 'Gentle, parent-supervised classes with verified teachers.', 'ar' => 'دروس لطيفة بإشراف الوالدين ومع معلمين موثّقين.', 'ur' => 'والدین کی نگرانی میں، تصدیق شدہ اساتذہ کے ساتھ کلاسز۔']],
            ['adult-quran', 'quran', 'adults', 'both', '18+',
                ['en' => 'Adult Quran Classes', 'ar' => 'دروس القرآن للكبار', 'ur' => 'بالغان کے لیے قرآن کلاسز'],
                ['en' => 'It is never too late: flexible classes for adults at any level.', 'ar' => 'ليس متأخرًا أبدًا: دروس مرنة للكبار بكل المستويات.', 'ur' => 'کبھی دیر نہیں ہوتی: ہر سطح کے بالغان کے لیے لچکدار کلاسز۔']],
            ['arabic-reading', 'arabic', 'all', 'group', null,
                ['en' => 'Arabic Reading', 'ar' => 'القراءة العربية', 'ur' => 'عربی پڑھنا'],
                ['en' => 'Read Arabic script confidently, as a foundation for the Quran.', 'ar' => 'اقرأ الحروف العربية بثقة كأساس لقراءة القرآن.', 'ur' => 'قرآن کی بنیاد کے طور پر عربی رسم الخط اعتماد سے پڑھیں۔']],
        ];
        foreach ($c as $i => [$slug, $cat, $aud, $fmt, $age, $title, $summary]) {
            Course::updateOrCreate(['slug' => $slug], ['category' => $cat, 'audience' => $aud, 'format' => $fmt, 'min_age' => $age,
                'title' => $title, 'summary' => $summary, 'sort' => $i + 1]);
        }
    }
}
