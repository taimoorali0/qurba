<?php

namespace App\Support;

use Illuminate\Http\Request;

/** Server-rendered title/description/Open Graph tags, so search engines and link previews see real content. */
class Seo
{
    public const DEFAULT_DESCRIPTION = 'Qurba: Closer Through Remembrance. Read the Quran with translation and tafsir, daily adhkar, tasbeeh, prayer times, Qibla and the 99 Names of Allah.';

    /** Per-route defaults, keyed by route name. */
    private const PAGES = [
        'home' => ['Qurba: Quran, Adhkar, Prayer Times and Qibla', self::DEFAULT_DESCRIPTION],
        'quran.index' => ['The Holy Quran: All 114 Surahs', 'Read all 114 surahs of the Holy Quran with Arabic text, translations, tafsir and recitations.'],
        'zikr' => ['Zikr and Remembrance', 'Daily adhkar, a digital tasbeeh counter and remembrance of Allah, all in one place.'],
        'adhkar' => ['Daily Adhkar', 'Morning, evening and daily adhkar with Arabic text, transliteration and translation.'],
        'zikr.tasbeeh' => ['Digital Tasbeeh Counter', 'A simple digital tasbeeh counter for your daily dhikr, works offline.'],
        'names' => ['99 Names of Allah (Asma ul Husna)', 'Learn the 99 Names of Allah with Arabic, meaning and explanation.'],
        'prayer' => ['Prayer Times', 'Accurate daily prayer times for your location, calculated on your device.'],
        'qibla' => ['Qibla Direction Finder', 'Find the Qibla direction towards the Kaaba from anywhere using your phone.'],
        'khatam' => ['Khatam Planner', 'Plan your complete reading of the Quran in 7, 15, 30 or 60 days and track every juz.'],
        'calendar' => ['Islamic Calendar', 'Today\'s Hijri date and upcoming Islamic dates: Ramadan, Eid, Ashura, Laylat al-Qadr and more.'],
        'zakat' => ['Zakat Calculator', 'Calculate your zakat on cash, savings, gold, silver, investments and business stock, with gold or silver nisab.'],
        'learn' => ['Learn with Qurba', 'Islamic learning courses and resources from Qurba.'],
        'explore' => ['Explore Qurba', 'Explore Hadith, Seerah, Naats, Ruqyah, Islamic books and more on Qurba.'],
        'library.module' => ['Islamic Library', 'Reviewed Islamic content: Hadith, Seerah, kids stories, Naats, Ruqyah and books.'],
    ];

    /** @return array{title: string, description: string, url: string, image: string, noindex: bool} */
    public static function for(Request $request): array
    {
        $name = config('app.name', 'Qurba');
        $route = $request->route()?->getName();
        [$title, $description] = self::PAGES[$route] ?? [$name, self::DEFAULT_DESCRIPTION];

        if ($route === 'library.module' && ($module = $request->route('module'))) {
            $title = ucfirst($module).' | Islamic Library';
        }

        $override = $request->attributes->get('seo', []);
        $title = $override['title'] ?? $title;

        return [
            'title' => str_contains($title, $name) ? $title : "{$title} - {$name}",
            'description' => $override['description'] ?? $description,
            'url' => $request->url(),
            'image' => url('/brand/qurba-icon-512.png'),
            // Account, settings and auth screens should not appear in search results.
            'noindex' => $route === null || ! array_key_exists($route, self::PAGES) && ! isset($override['title']),
        ];
    }
}
