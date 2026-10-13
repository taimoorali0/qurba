"""Convert Tanzil data without executing its SQL; preserve decoded text verbatim."""
import hashlib, json, re, sys
from pathlib import Path

ROW = re.compile(r"^\((\d+),\s*(\d+),\s*(\d+),\s*'((?:[^'\\]|\\.|'')*)'\)[,;]\s*$", re.M)
def decode(value):
    escapes = {'0':'\0','n':'\n','r':'\r','t':'\t','b':'\b','Z':'\x1a'}
    return re.sub(r"\\(.)|''", lambda m: escapes.get(m[1],m[1]) if m[1] else "'", value)
def read(path):
    text = path.read_text(encoding='utf-8-sig')
    if path.suffix == '.sql':
        matches = list(ROW.finditer(text))
        expected = len(re.findall(r'^\(\d+,\s*\d+,\s*\d+,',text,re.M))
        assert len(matches)==expected, f'Unparsed SQL row in {path}'
        rows = [(int(m[2]),int(m[3]),decode(m[4])) for m in matches]
    else:
        rows = [(int(s),int(a),t) for s,a,t in (line.split('|',2) for line in text.splitlines() if re.match(r'^\d+\|\d+\|',line))]
    structure = Path(__file__).resolve().parents[1]/'app/Support/Quran/QuranStructure.php'
    counts = [int(x) for x in re.findall(r'\d+',re.search(r'public const AYAHS = \[1 => (.*?)\];',structure.read_text(),re.S)[1])]
    expected = {(s,a) for s,n in enumerate(counts,1) for a in range(1,n+1)}
    assert len(rows)==6236 and len({(s,a) for s,a,_ in rows})==6236
    assert {(s,a) for s,a,_ in rows}==expected
    assert all(t.strip() and '\n' not in t and '\r' not in t for _,_,t in rows)
    return text, rows

def main(folder, output):
    output.mkdir(parents=True,exist_ok=True)
    entries=[]
    for path in sorted(folder.glob('*')):
        if path.suffix not in ['.sql','.txt']: continue
        source,rows=read(path)
        if path.name=='sd.amroti.txt':
            assert rows==read(folder/'sd.amroti.sql')[1], 'Sindhi copies differ'
            continue
        arabic=path.name=='quran-uthmani-min.sql'
        identity='quran-uthmani-min' if arabic else path.stem
        meta=lambda key: (re.search(r'^#\s*'+key+r':\s*(.+)$',source,re.M) or [None,''])[1]
        header='\n'.join(line for line in source.splitlines() if line.startswith('#'))
        # Keep complete source copyright and metadata comments in the converted edition.
        content=header+'\n'+'\n'.join(f'{s}|{a}|{t}' for s,a,t in rows)+'\n'
        target=output/(identity+'.txt');target.write_text(content,encoding='utf-8')
        kind='quran' if arabic else ('tafsir' if identity=='ar.jalalayn' else 'translation')
        entry={'id':identity,'kind':kind,'file':target.name,'sha256':hashlib.sha256(target.read_bytes()).hexdigest(),'items':6236,'name':'Tanzil Quran Text (Uthmani Minimal)' if arabic else meta('Name'),'author':meta('Translator'),'lang':'ar' if arabic else identity.split('.')[0],'edition':'Uthmani Minimal 1.1' if arabic else meta('Last Update'),'url':'https://tanzil.net/download/' if arabic else 'https://tanzil.net/trans/','license':'Tanzil Quran Text License; CC BY 3.0; verbatim text and attribution required' if arabic else 'Tanzil translations: non-commercial use only; other use requires permission'}
        entries.append(entry)
    (output/'manifest.json').write_text(json.dumps({'editions':entries},ensure_ascii=False,indent=2)+'\n')
    print(json.dumps({'unique_editions':len(entries),'records':sum(e['items'] for e in entries),'duplicate_removed':'sd.amroti.txt'},indent=2))
if __name__=='__main__': main(Path(sys.argv[1]),Path(sys.argv[2]))
