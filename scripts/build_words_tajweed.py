"""Build Qurba's word-by-word and tajweed data files from published open datasets.

Inputs (download them first, see resources/content/2026-10-words-tajweed/README.md):
  1. data.json from npm @kmaslesa/holy-quran-word-by-word-full-data@1.0.6 (Quran.com word data)
  2. data/qurandb from npm quran@0.0.9 (Tanzil Uthmani text, SQLite)
  3. output/tajweed.hafs.uthmani-pause-sajdah.json from github.com/cpfair/quran-tajweed (CC BY 4.0)
Nothing is edited by hand: Arabic words come from Qurba's installed Tanzil text, meanings from
Quran.com word data, tajweed positions from cpfair/quran-tajweed. The script refuses to write if
anything does not line up.

usage: python3 -I scripts/build_words_tajweed.py WBW_JSON QURANDB TAJWEED_JSON OUT_DIR
"""
import hashlib, json, re, sqlite3, sys, unicodedata
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
BASE_TEXT = ROOT / 'resources/content/2026-10-upload/quran-uthmani-min.txt'
# Tanzil writes these as two words where Quran.com counts one
JOINED = {('بَعدَ', 'ما'), ('إِل', 'ياسينَ')}

def ayah_keys():
    structure = (ROOT / 'app/Support/Quran/QuranStructure.php').read_text()
    counts = [int(x) for x in re.findall(r'\d+', re.search(r'public const AYAHS = \[1 => (.*?)\];', structure, re.S)[1])]
    return [f'{s}:{a}' for s, n in enumerate(counts, 1) for a in range(1, n + 1)]

def read_base():
    rows = {}
    for line in BASE_TEXT.read_text(encoding='utf-8').splitlines():
        if re.match(r'^\d+\|\d+\|', line):
            s, a, t = line.split('|', 2)
            rows[f'{s}:{a}'] = t
    return rows

def is_word(token):
    return any(unicodedata.category(c).startswith('L') for c in token)

def tokens(key, text, expected):
    """Words of the installed text, without the Bismillah Tanzil prefixes to ayah 1 and with pause marks dropped."""
    words = [w for w in text.split() if is_word(w)]
    s, a = map(int, key.split(':'))
    if a == 1 and s not in (1, 9) and len(words) >= expected + 4 and words[0].startswith('بِسم'):
        words = words[4:]
    merged, i = [], 0
    while i < len(words):
        if len(words) > expected and i + 1 < len(words) and (words[i], words[i + 1]) in JOINED:
            merged.append(words[i] + ' ' + words[i + 1]); i += 2
        else:
            merged.append(words[i]); i += 1
    return merged

MAP = {'ٱ': 'ا', 'ٰ': 'ا', 'ى': 'ي', 'ی': 'ي', 'أ': 'ا', 'إ': 'ا',
       'آ': 'ا', 'ؤ': 'و', 'ئ': 'ي'}
DROP = set('ءۥۦـ')
def skeleton(t):
    return ''.join(MAP.get(c, c) for c in t if c not in DROP and unicodedata.category(MAP.get(c, c)).startswith('L'))

def main(wbw_path, db_path, tajweed_path, out_dir):
    keys, base = ayah_keys(), read_base()
    assert len(keys) == 6236 and set(base) == set(keys)

    # ---- word by word ----
    meanings = {}
    for page in json.loads(Path(wbw_path).read_text(encoding='utf-8')):
        for ayah in page['ayahs']:
            for w in ayah['words'] or []:
                if w and w['char_type_name'] == 'word':
                    meanings.setdefault(w['parentAyahVerseKey'], []).append(w)
    words, skipped = {}, []
    for key in keys:
        src = sorted(meanings[key], key=lambda w: w['position'])
        ar = tokens(key, base[key], len(src))
        if len(ar) != len(src):
            skipped.append(key); continue
        words[key] = [[a, (w['transliteration'] or {}).get('text') or '', (w['translation'] or {}).get('text') or ''] for a, w in zip(ar, src)]
    assert not skipped, f'word counts differ: {skipped}'

    # ---- tajweed ----
    db = sqlite3.connect(db_path)
    full = {f'{s}:{v}': t for s, v, t in db.execute('select chapter, verse, ar from ar')}
    assert set(full) == set(keys)
    bism = skeleton('بِسمِ اللَّهِ الرَّحمـٰنِ الرَّحيمِ')
    differ = []
    for key in keys:
        a, b = skeleton(base[key]), skeleton(full[key])
        s, v = map(int, key.split(':'))
        if v == 1 and s not in (1, 9) and a.startswith(bism) and not b.startswith(bism):
            a = a[len(bism):]
        if a != b:
            differ.append(key)
    # Known spelling differences between Tanzil editions (one letter, same reading)
    assert set(differ) <= {'2:72', '12:39', '12:41'}, f'Tajweed text differs from the installed Quran: {differ}'
    tajweed = {}
    for e in json.loads(Path(tajweed_path).read_text(encoding='utf-8')):
        key = f"{e['surah']}:{e['ayah']}"
        text = full[key]
        spans = sorted([an['rule'], an['start'], an['end']] for an in e['annotations'])
        assert all(0 <= st < en <= len(text) for _, st, en in spans), key
        assert all(text[st:en] == 'ٱ' for r, st, en in spans if r == 'hamzat_wasl'), key
        tajweed[key] = {'text': text, 'rules': spans}
    assert set(tajweed) == set(keys)

    out = Path(out_dir); out.mkdir(parents=True, exist_ok=True)
    files = {'quran-words-en.json': words, 'quran-tajweed.json': tajweed}
    for name, data in files.items():
        (out / name).write_text(json.dumps(data, ensure_ascii=False, separators=(',', ':')), encoding='utf-8')
    manifest = {'editions': [
        {'id': 'words-en', 'kind': 'word_by_word', 'file': 'quran-words-en.json', 'lang': 'en',
         'name': 'Quran.com word-by-word (English)', 'author': 'Quran.com',
         'edition': '@kmaslesa/holy-quran-word-by-word-full-data 1.0.6', 'url': 'https://quran.com',
         'license': 'Quran.com word-by-word data; confirm permission for publication. Arabic words from Tanzil Uthmani Minimal (CC BY 3.0).'},
        {'id': 'tajweed', 'kind': 'tajweed', 'file': 'quran-tajweed.json', 'lang': 'ar',
         'name': 'Tajweed (Tanzil Uthmani with cpfair/quran-tajweed annotations)', 'author': 'Collin Fair; Tanzil Project',
         'edition': 'quran-tajweed (hafs, uthmani-pause-sajdah); Tanzil Uthmani via npm quran 0.0.9', 'url': 'https://github.com/cpfair/quran-tajweed',
         'license': 'Annotations CC BY 4.0 (Collin Fair); Quran text Tanzil terms, verbatim with attribution'},
    ]}
    for e in manifest['editions']:
        e['sha256'] = hashlib.sha256((out / e['file']).read_bytes()).hexdigest()
        e['items'] = 6236
    (out / 'manifest.json').write_text(json.dumps(manifest, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')
    print('words', sum(len(v) for v in words.values()), 'tajweed rules', sum(len(v['rules']) for v in tajweed.values()))

if __name__ == '__main__':
    main(*sys.argv[1:5])
