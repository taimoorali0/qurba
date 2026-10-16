<?php
// ===== QURBA: the Six Kalimas (as taught in South Asian madrasas), for Kids corner and Adhkar.
// Added as "in review": a Religious Content Reviewer must check and approve them before users see them. =====
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const KALIMAS = [
        [
            'ref' => 'First Kalima — Tayyib (Purity)',
            'ar' => 'لَا إِلٰهَ إِلَّا اللّٰهُ مُحَمَّدٌ رَّسُوْلُ اللّٰهِ',
            'tl' => 'Lā ilāha illallāhu Muḥammadur rasūlullāh',
            'en' => 'There is no god but Allah; Muhammad is the Messenger of Allah.',
            'ur' => 'اللہ کے سوا کوئی معبود نہیں، محمد ﷺ اللہ کے رسول ہیں۔',
        ],
        [
            'ref' => 'Second Kalima — Shahadah (Testimony)',
            'ar' => 'أَشْهَدُ أَنْ لَّا إِلٰهَ إِلَّا اللّٰهُ وَحْدَهٗ لَا شَرِيْكَ لَهٗ وَأَشْهَدُ أَنَّ مُحَمَّدًا عَبْدُهٗ وَرَسُوْلُهٗ',
            'tl' => 'Ash-hadu al-lā ilāha illallāhu waḥdahū lā sharīka lahū, wa ash-hadu anna Muḥammadan ʿabduhū wa rasūluh',
            'en' => 'I bear witness that there is no god but Allah, He is One and has no partner, and I bear witness that Muhammad is His servant and Messenger.',
            'ur' => 'میں گواہی دیتا ہوں کہ اللہ کے سوا کوئی معبود نہیں، وہ اکیلا ہے، اس کا کوئی شریک نہیں، اور میں گواہی دیتا ہوں کہ محمد ﷺ اس کے بندے اور رسول ہیں۔',
        ],
        [
            'ref' => 'Third Kalima — Tamjeed (Glorification)',
            'ar' => 'سُبْحَانَ اللّٰهِ وَالْحَمْدُ لِلّٰهِ وَلَا إِلٰهَ إِلَّا اللّٰهُ وَاللّٰهُ أَكْبَرُ وَلَا حَوْلَ وَلَا قُوَّةَ إِلَّا بِاللّٰهِ الْعَلِيِّ الْعَظِيْمِ',
            'tl' => 'Subḥānallāhi wal-ḥamdu lillāhi wa lā ilāha illallāhu wallāhu akbar, wa lā ḥawla wa lā quwwata illā billāhil-ʿaliyyil-ʿaẓīm',
            'en' => 'Glory be to Allah, all praise is for Allah, there is no god but Allah, and Allah is the Greatest. There is no might nor power except with Allah, the Most High, the Most Great.',
            'ur' => 'اللہ پاک ہے، تمام تعریفیں اللہ کے لیے ہیں، اللہ کے سوا کوئی معبود نہیں، اور اللہ سب سے بڑا ہے۔ گناہوں سے بچنے کی طاقت اور نیکی کرنے کی قوت صرف اللہ ہی کی طرف سے ہے جو بلند و عظیم ہے۔',
        ],
        [
            'ref' => 'Fourth Kalima — Tawheed (Oneness of Allah)',
            'ar' => 'لَا إِلٰهَ إِلَّا اللّٰهُ وَحْدَهٗ لَا شَرِيْكَ لَهٗ لَهُ الْمُلْكُ وَلَهُ الْحَمْدُ يُحْيِيْ وَيُمِيْتُ وَهُوَ حَيٌّ لَّا يَمُوْتُ أَبَدًا أَبَدًا ذُو الْجَلَالِ وَالْإِكْرَامِ بِيَدِهِ الْخَيْرُ وَهُوَ عَلٰى كُلِّ شَيْءٍ قَدِيْرٌ',
            'tl' => 'Lā ilāha illallāhu waḥdahū lā sharīka lahū, lahul-mulku wa lahul-ḥamdu, yuḥyī wa yumītu, wa huwa ḥayyul lā yamūtu abadan abadā, dhul-jalāli wal-ikrām, biyadihil-khayr, wa huwa ʿalā kulli shay-in qadīr',
            'en' => 'There is no god but Allah, He is One and has no partner. His is the kingdom and His is the praise. He gives life and causes death, and He is Ever-Living and will never die. Possessor of majesty and honour; in His hand is all good, and He has power over all things.',
            'ur' => 'اللہ کے سوا کوئی معبود نہیں، وہ اکیلا ہے، اس کا کوئی شریک نہیں، اسی کی بادشاہی ہے اور اسی کے لیے تمام تعریفیں ہیں۔ وہی زندہ کرتا ہے اور وہی مارتا ہے، اور وہ ہمیشہ زندہ ہے، اسے کبھی موت نہیں آئے گی۔ بڑی شان اور عزت والا ہے، اسی کے ہاتھ میں ہر بھلائی ہے، اور وہ ہر چیز پر قادر ہے۔',
        ],
        [
            'ref' => 'Fifth Kalima — Astaghfar (Seeking forgiveness)',
            'ar' => 'أَسْتَغْفِرُ اللّٰهَ رَبِّيْ مِنْ كُلِّ ذَنْبٍ أَذْنَبْتُهٗ عَمَدًا أَوْ خَطَأً سِرًّا أَوْ عَلَانِيَةً وَّأَتُوْبُ إِلَيْهِ مِنَ الذَّنْبِ الَّذِيْ أَعْلَمُ وَمِنَ الذَّنْبِ الَّذِيْ لَا أَعْلَمُ إِنَّكَ أَنْتَ عَلَّامُ الْغُيُوْبِ وَسَتَّارُ الْعُيُوْبِ وَغَفَّارُ الذُّنُوْبِ وَلَا حَوْلَ وَلَا قُوَّةَ إِلَّا بِاللّٰهِ الْعَلِيِّ الْعَظِيْمِ',
            'tl' => 'Astaghfirullāha rabbī min kulli dhambin adhnabtuhū ʿamadan aw khaṭa-an sirran aw ʿalāniyatan wa atūbu ilayhi minadh-dhambil-ladhī aʿlamu wa minadh-dhambil-ladhī lā aʿlamu, innaka anta ʿallāmul-ghuyūbi wa sattārul-ʿuyūbi wa ghaffārudh-dhunūb, wa lā ḥawla wa lā quwwata illā billāhil-ʿaliyyil-ʿaẓīm',
            'en' => 'I seek forgiveness from Allah, my Lord, for every sin I committed knowingly or unknowingly, secretly or openly, and I turn to Him in repentance from the sin I know and the sin I do not know. Indeed You are the Knower of the unseen, the Concealer of faults and the Forgiver of sins. There is no might nor power except with Allah, the Most High, the Most Great.',
            'ur' => 'میں اللہ سے معافی مانگتا ہوں جو میرا رب ہے، ہر اس گناہ سے جو میں نے جان بوجھ کر یا بھول کر کیا، چھپ کر یا کھلم کھلا، اور میں اس کی طرف توبہ کرتا ہوں اس گناہ سے جو میں جانتا ہوں اور اس گناہ سے جو میں نہیں جانتا۔ بے شک تو غیبوں کا جاننے والا، عیبوں کو چھپانے والا اور گناہوں کو بخشنے والا ہے۔ اور گناہوں سے بچنے کی طاقت اور نیکی کرنے کی قوت صرف اللہ ہی کی طرف سے ہے جو بلند و عظیم ہے۔',
        ],
        [
            'ref' => 'Sixth Kalima — Radd-e-Kufr (Rejecting disbelief)',
            'ar' => 'اَللّٰهُمَّ إِنِّيْ أَعُوْذُ بِكَ مِنْ أَنْ أُشْرِكَ بِكَ شَيْئًا وَّأَنَا أَعْلَمُ بِهٖ وَأَسْتَغْفِرُكَ لِمَا لَا أَعْلَمُ بِهٖ تُبْتُ عَنْهُ وَتَبَرَّأْتُ مِنَ الْكُفْرِ وَالشِّرْكِ وَالْكِذْبِ وَالْغِيْبَةِ وَالْبِدْعَةِ وَالنَّمِيْمَةِ وَالْفَوَاحِشِ وَالْبُهْتَانِ وَالْمَعَاصِيْ كُلِّهَا وَأَسْلَمْتُ وَأَقُوْلُ لَا إِلٰهَ إِلَّا اللّٰهُ مُحَمَّدٌ رَّسُوْلُ اللّٰهِ',
            'tl' => 'Allāhumma innī aʿūdhu bika min an ushrika bika shay-an wa ana aʿlamu bihī wa astaghfiruka limā lā aʿlamu bihī, tubtu ʿanhu wa tabarra-tu minal-kufri wash-shirki wal-kidhbi wal-ghībati wal-bidʿati wan-namīmati wal-fawāḥishi wal-buhtāni wal-maʿāṣī kullihā, wa aslamtu wa aqūlu lā ilāha illallāhu Muḥammadur rasūlullāh',
            'en' => 'O Allah, I seek refuge in You from knowingly associating any partner with You, and I seek Your forgiveness for what I do not know. I repent from it and I free myself from disbelief, associating partners, lying, backbiting, innovation, tale-carrying, indecency, slander and all sins. I submit to You, and I say: there is no god but Allah, Muhammad is the Messenger of Allah.',
            'ur' => 'اے اللہ! میں تیری پناہ مانگتا ہوں اس بات سے کہ میں جان بوجھ کر کسی چیز کو تیرا شریک بناؤں، اور تجھ سے معافی مانگتا ہوں اس سے جو میں نہیں جانتا۔ میں نے اس سے توبہ کی، اور میں بیزار ہوا کفر سے، شرک سے، جھوٹ سے، غیبت سے، بدعت سے، چغلی سے، بے حیائی کے کاموں سے، بہتان سے اور تمام گناہوں سے۔ اور میں اسلام لایا اور کہتا ہوں: اللہ کے سوا کوئی معبود نہیں، محمد ﷺ اللہ کے رسول ہیں۔',
        ],
    ];

    public function up(): void
    {
        $now = now();
        if (! DB::table('adhkar_categories')->where('slug', 'kalimas')->exists()) {
            DB::table('adhkar_categories')->insert([
                'slug' => 'kalimas',
                'name' => json_encode(['en' => 'Six Kalimas', 'ar' => 'الكلمات الست', 'ur' => 'چھ کلمے'], JSON_UNESCAPED_UNICODE),
                'sort' => (int) DB::table('adhkar_categories')->max('sort') + 1,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
        $cat = DB::table('adhkar_categories')->where('slug', 'kalimas')->value('id');
        if (DB::table('adhkar')->where('adhkar_category_id', $cat)->exists()) return;

        foreach (self::KALIMAS as $i => $k) {
            $id = DB::table('adhkar')->insertGetId([
                'adhkar_category_id' => $cat, 'text_arabic' => $k['ar'], 'transliteration' => $k['tl'],
                'reference' => $k['ref'], 'repeat_count' => 1, 'status' => 'in_review', 'sort' => $i + 1,
                // same hash the Adhkar model computes on save
                'content_hash' => hash('sha256', $k['ar'].'|'.$k['ref'].'|1'),
                'created_at' => $now, 'updated_at' => $now,
            ]);
            foreach (['en', 'ur'] as $lang) {
                DB::table('adhkar_translations')->insert(['adhkar_id' => $id, 'language_code' => $lang, 'text' => $k[$lang], 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        $cat = DB::table('adhkar_categories')->where('slug', 'kalimas')->value('id');
        if (! $cat) return;
        // Leave approved kalimas alone; remove only the unreviewed starter set
        DB::table('adhkar')->where('adhkar_category_id', $cat)->where('status', 'in_review')->delete();
        if (! DB::table('adhkar')->where('adhkar_category_id', $cat)->exists()) DB::table('adhkar_categories')->where('id', $cat)->delete();
    }
};
