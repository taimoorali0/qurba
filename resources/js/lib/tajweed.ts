// ===== QURBA: tajweed rule colours (the usual tajweed-mushaf colour scheme) =====
export type Rule = [string, number, number];

/** Colour groups shown in the legend; several rules share a colour as in printed tajweed masahif. */
export const GROUPS: { key: string; color: string; rules: string[] }[] = [
  { key: 'silent', color: '#9CA3AF', rules: ['hamzat_wasl', 'lam_shamsiyyah', 'silent'] },
  { key: 'madd', color: '#2563EB', rules: ['madd_2', 'madd_246'] },
  { key: 'maddLong', color: '#1E3A8A', rules: ['madd_muttasil', 'madd_munfasil', 'madd_6'] },
  { key: 'ghunnah', color: '#EA580C', rules: ['ghunnah'] },
  { key: 'ikhfa', color: '#9333EA', rules: ['ikhfa', 'ikhfa_shafawi'] },
  { key: 'idghaam', color: '#15803D', rules: ['idghaam_ghunnah', 'idghaam_no_ghunnah', 'idghaam_shafawi', 'idghaam_mutajanisayn', 'idghaam_mutaqaribayn'] },
  { key: 'iqlab', color: '#0891B2', rules: ['iqlab'] },
  { key: 'qalqalah', color: '#DC2626', rules: ['qalqalah'] },
];
const COLOR: Record<string, string> = Object.fromEntries(GROUPS.flatMap((g) => g.rules.map((r) => [r, g.color])));

/** Splits an ayah into plain and coloured pieces. Where rules overlap, the later-starting one wins. */
export function segments(text: string, rules: Rule[]): { text: string; color?: string; rule?: string }[] {
  const chars = Array.from(text); // rule offsets are Unicode code points
  const owner: (string | undefined)[] = new Array(chars.length);
  for (const [rule, start, end] of [...rules].sort((a, b) => a[1] - b[1])) {
    for (let i = start; i < end && i < chars.length; i++) owner[i] = rule;
  }
  const out: { text: string; color?: string; rule?: string }[] = [];
  chars.forEach((c, i) => {
    const last = out[out.length - 1];
    if (last && last.rule === owner[i]) last.text += c;
    else out.push({ text: c, rule: owner[i], color: owner[i] ? COLOR[owner[i]!] : undefined });
  });
  return out;
}
