# Qurba content expansion

## Implemented foundation

Plain page backgrounds and headings replace the repeated star pattern and divider.
The library API covers Hadith, Seerah, Kids, Hamd/Naats and Ruqyah:

- GET /api/v1/library/modules
- GET /api/v1/library/{module}?collection=...&chapter=...
- GET /api/v1/library/{module}/{slug}

Lists paginate at 30 items. Entries carry multilingual text, collection/chapter,
reference, grading and source metadata. Recordings have their own source, speaker,
language, duration and publication state. Public output requires a published item
and an approved source with redistribution permission, including in development.
Offline permission is separate. The admin Content library manages these entries.
No placeholder religious content is seeded or automatically approved.

## Next implementation batches

1. Provider adapters and draft imports: IslamicAPI, UmmahAPI, Sunnah.com.
2. Kanz-ul-Iman edition identification, permitted text extraction, ayah mapping and
   separate translation/Tafsir audio support.
3. Native library browse/read/listen screens, topic search and download management.
4. Hijri calendar and fasting services with timezone, method and regional adjustment.
5. Kids lessons/quizzes, Seerah chapters and licensed Naat playlists.
6. Cross-module favorites, progress sync, offline packs and content health checks.

Existing Quran, Adhkar, prayer calculation and account-sync features remain in use.
Provider documentation does not establish permission to redistribute content.
Provider keys stay on the server; adapters should stage data before review.

## Deployment

After merging, run `php artisan migrate --force`, build frontend assets, then clear
application caches. Existing data is retained. Configure real sources and populate
entries through the admin. PHP tests and admin functionality need verification in CI;
this development environment has no PHP runtime.
