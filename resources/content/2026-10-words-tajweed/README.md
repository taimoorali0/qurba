# Word-by-word and tajweed data

Built by `scripts/build_words_tajweed.py`; imported with `php artisan qurba:import-words-tajweed`.
Both editions are imported as **pending review** and are shown to users only after a Religious
Content Reviewer approves them in Admin → Content sources.

| File | What | Source | Licence |
|---|---|---|---|
| `quran-words-en.json` | Per ayah: `[arabic, transliteration, English meaning]` for every word | Meanings and transliteration: Quran.com word data, from npm `@kmaslesa/holy-quran-word-by-word-full-data@1.0.6`. Arabic words: Qurba's installed Tanzil Uthmani Minimal text | Quran.com word-by-word data — confirm publication permission with Quran.com. Tanzil text CC BY 3.0 |
| `quran-tajweed.json` | Per ayah: Tanzil Uthmani text and tajweed rule spans `[rule, start, end]` (Unicode code points) | Text: Tanzil Uthmani from npm `quran@0.0.9`. Rules: github.com/cpfair/quran-tajweed `output/tajweed.hafs.uthmani-pause-sajdah.json` | Annotations CC BY 4.0 (Collin Fair). Text: Tanzil terms (verbatim, attribution) |

The build script checks that word counts match every ayah, that every tajweed rule falls inside its
ayah, that every hamzat-wasl mark sits on ٱ, and that the tajweed text has the same letters as the
installed Quran text (3 known one-letter spelling differences: 2:72, 12:39, 12:41).

To rebuild:

```bash
npm pack @kmaslesa/holy-quran-word-by-word-full-data@1.0.6 quran@0.0.9
# extract both, download the tajweed JSON from github.com/cpfair/quran-tajweed, then:
python3 -I scripts/build_words_tajweed.py WBW/package/data.json QURAN/package/data/qurandb tajweed.hafs.uthmani-pause-sajdah.json resources/content/2026-10-words-tajweed
```
