// ===== QURBA: the 28 letters of the Arabic alphabet in Qaida order =====
export interface Letter { n: number; char: string; name: string; latin: string; joins: boolean }

// joins = false for the six letters that never connect to the letter after them
export const LETTERS: Letter[] = [
  { n: 1, char: 'ا', name: 'أَلِف', latin: 'Alif', joins: false },
  { n: 2, char: 'ب', name: 'بَاء', latin: 'Ba', joins: true },
  { n: 3, char: 'ت', name: 'تَاء', latin: 'Ta', joins: true },
  { n: 4, char: 'ث', name: 'ثَاء', latin: 'Tha', joins: true },
  { n: 5, char: 'ج', name: 'جِيم', latin: 'Jim', joins: true },
  { n: 6, char: 'ح', name: 'حَاء', latin: 'Ha', joins: true },
  { n: 7, char: 'خ', name: 'خَاء', latin: 'Kha', joins: true },
  { n: 8, char: 'د', name: 'دَال', latin: 'Dal', joins: false },
  { n: 9, char: 'ذ', name: 'ذَال', latin: 'Dhal', joins: false },
  { n: 10, char: 'ر', name: 'رَاء', latin: 'Ra', joins: false },
  { n: 11, char: 'ز', name: 'زَاي', latin: 'Zay', joins: false },
  { n: 12, char: 'س', name: 'سِين', latin: 'Sin', joins: true },
  { n: 13, char: 'ش', name: 'شِين', latin: 'Shin', joins: true },
  { n: 14, char: 'ص', name: 'صَاد', latin: 'Sad', joins: true },
  { n: 15, char: 'ض', name: 'ضَاد', latin: 'Dad', joins: true },
  { n: 16, char: 'ط', name: 'طَاء', latin: 'Taa', joins: true },
  { n: 17, char: 'ظ', name: 'ظَاء', latin: 'Zaa', joins: true },
  { n: 18, char: 'ع', name: 'عَيْن', latin: 'Ayn', joins: true },
  { n: 19, char: 'غ', name: 'غَيْن', latin: 'Ghayn', joins: true },
  { n: 20, char: 'ف', name: 'فَاء', latin: 'Fa', joins: true },
  { n: 21, char: 'ق', name: 'قَاف', latin: 'Qaf', joins: true },
  { n: 22, char: 'ك', name: 'كَاف', latin: 'Kaf', joins: true },
  { n: 23, char: 'ل', name: 'لَام', latin: 'Lam', joins: true },
  { n: 24, char: 'م', name: 'مِيم', latin: 'Mim', joins: true },
  { n: 25, char: 'ن', name: 'نُون', latin: 'Nun', joins: true },
  { n: 26, char: 'و', name: 'وَاو', latin: 'Waw', joins: false },
  { n: 27, char: 'ه', name: 'هَاء', latin: 'Ha', joins: true },
  { n: 28, char: 'ي', name: 'يَاء', latin: 'Ya', joins: true },
];

const T = 'ـ'; // tatweel, shows how a letter looks when joined
/** Isolated, start, middle and end shapes of a letter. */
export function forms(l: Letter) {
  return l.joins
    ? { isolated: l.char, initial: l.char + T, medial: T + l.char + T, final: T + l.char }
    : { isolated: l.char, initial: l.char, medial: T + l.char, final: T + l.char };
}

/** The three short vowels on a letter: fatha, kasra, damma (e.g. بَ بِ بُ). */
export const harakat = (l: Letter) => [l.char + 'َ', l.char + 'ِ', l.char + 'ُ'];
