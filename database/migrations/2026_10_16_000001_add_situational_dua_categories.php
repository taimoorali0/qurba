<?php
// ===== QURBA: duas by situation. Categories only; duas are added and approved in the admin panel. =====
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CATEGORIES = [
        ['illness', 'Illness & Healing', 'المرض والشفاء', 'بیماری اور شفا'],
        ['distress', 'Anxiety & Distress', 'الهم والكرب', 'پریشانی اور غم'],
        ['knowledge', 'Study & Exams', 'طلب العلم', 'علم اور امتحان'],
        ['forgiveness', 'Forgiveness', 'الاستغفار', 'استغفار'],
        ['protection', 'Protection', 'التحصين', 'حفاظت'],
        ['home-family', 'Home & Family', 'البيت والأهل', 'گھر اور اہلِ خانہ'],
        ['food-drink', 'Food & Drink', 'الطعام والشراب', 'کھانا اور پینا'],
        ['weather', 'Rain & Weather', 'المطر والرياح', 'بارش اور موسم'],
        ['masjid', 'Masjid & Wudu', 'المسجد والوضوء', 'مسجد اور وضو'],
    ];

    public function up(): void
    {
        $now = now();
        $sort = (int) DB::table('adhkar_categories')->max('sort');
        foreach (self::CATEGORIES as $i => [$slug, $en, $ar, $ur]) {
            if (DB::table('adhkar_categories')->where('slug', $slug)->exists()) continue;
            DB::table('adhkar_categories')->insert([
                'slug' => $slug,
                'name' => json_encode(['en' => $en, 'ar' => $ar, 'ur' => $ur], JSON_UNESCAPED_UNICODE),
                'sort' => $sort + $i + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Keep categories that already hold duas.
        $slugs = array_column(self::CATEGORIES, 0);
        DB::table('adhkar_categories')->whereIn('slug', $slugs)
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('adhkar')->whereColumn('adhkar.adhkar_category_id', 'adhkar_categories.id'))
            ->delete();
    }
};
