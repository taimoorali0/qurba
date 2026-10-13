# Qurba uploaded content update

Prepared against taimoorali0/qurba main commit 0086f7a. This update adds local imports and PDF book support. Importing on the live server is a separate step after checking out this change.

## Included data
- Arabic Quran: Tanzil Uthmani Minimal 1.1 (keep its source spelling and basmala representation).
- English: Ali Quli Qarai.
- Urdu: Tahir ul Qadri, Syed Zeeshan Haider Jawadi, Muhammad Junagarhi, Muhammad Hussain Najafi, Abul A'ala Maududi.
- Sindhi: Taj Mehmood Amroti. The TXT copy was identical to the SQL copy and was deduplicated.
- Arabic tafsir: Jalalayn, stored as commentary rather than translation.

Each of the nine unique editions has 6,236 unique canonical references across 114 surahs: 56,124 records altogether. Validation proves structural completeness and conversion preservation, not scholarly accuracy or the latest source version. Per-edition source dates and authors are preserved in manifest.json. Original text has not been rewritten by AI. The converter never executes uploaded SQL DROP/CREATE/USE statements.

## Install into the existing app
Back up the database and app first. Check out the content update branch or merge its pull request, then pull the change into the existing Qurba project. The original PDFs are uploaded separately; dependencies and existing settings are not included.

```bash
php artisan migrate --force
php artisan qurba:import-uploaded-content --dry-run
php artisan qurba:import-uploaded-content
php artisan qurba:verify-quran
npm ci
npm run build
php artisan optimize:clear
```

If this is a new installation without 114 surah records, run `php artisan db:seed --class=QurbaSeeder --force` before importing. Existing seeded installations do not need reseeding.

A different installed Arabic edition is blocked by default. Compare the edition first; use `--replace-quran` only when you deliberately want the uploaded Uthmani Minimal edition to replace it. Identical installed Arabic text is left unchanged. Importing translations/tafsir again updates their existing rows and returns them to review. All text editions are checked before writes, and database changes are transactional.

## Original PDF books
The two large PDFs are already in your attachments and are NOT duplicated inside this ZIP. Upload both original files into one server folder, e.g. `/var/www/qurba/storage/app/imports/kanz`:
- english-translation-kanz-ul-iman.pdf: 1,306 pages.
- al-quran-ul-kareem-kanzul-iman-ma-khazain-ul-irfan.pdf: 1,222 pages.

```bash
php artisan qurba:import-kanz-books storage/app/imports/kanz --dry-run
php artisan qurba:import-kanz-books storage/app/imports/kanz
```

The command checks exact uploaded-file SHA-256 hashes, streams the PDFs into private storage and creates two review entries under Islamic Books. /explore links to this section. The PDF route checks published entry + approved source + redistribution permission on every request. Scanned books are retained as complete books, not claimed as searchable ayah text. OCR with proofreading is a separate future step.

## Review and publish
In /admin → Content sources, review each author, edition, content and reuse terms. Quran text retains the Tanzil notice and source link. Tanzil translation downloads specify non-commercial use; other use requires translator/publisher permission. Mark redistribution/offline permission only where permitted. For PDFs, approve their sources and publish the corresponding Content entries after review. No importer automatically approves sources or publishes book entries.

## Validation
- Complete canonical ayah references, uniqueness and non-empty text checked for every dataset.
- SQL decode → pipe text round-trip preserves all eight SQL editions exactly.
- Duplicate Sindhi editions compared exactly.
- Sample PDF pages rendered and inspected; sampled text extraction is insufficient for reliable verse imports.
- Modified PHP files parsed for syntax; changed Vue components compile; production frontend build passed.
- Laravel feature tests supplied in UploadedContentTest.php but cannot be executed in the preparation environment, which has no PHP runtime. Run `php artisan test --filter=UploadedContent` on a configured development/staging installation before deployment.
