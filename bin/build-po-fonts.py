"""Make static, Latin-subset TTFs of the brand fonts plus PHP metric files for the PDF writer.

The variable fonts come from github.com/google/fonts (ofl/sora, ofl/inter, ofl/jetbrainsmono).
Needs fontTools (pip install fonttools).

Usage: python bin/build-po-fonts.py <folder-with-the-variable-fonts> mine-mirth-purchase-orders/fonts
"""
import io
import os
import sys

from fontTools.ttLib import TTFont
from fontTools.varLib import instancer
from fontTools import subset

SRC, OUT = sys.argv[1], sys.argv[2]
os.makedirs(OUT, exist_ok=True)

FONTS = [
    # key, file, axes, stemV, mono
    ('sora-bold', 'Sora[wght].ttf', {'wght': 700}, 140, False),
    ('sora-semibold', 'Sora[wght].ttf', {'wght': 600}, 120, False),
    ('inter-regular', 'Inter[opsz,wght].ttf', {'wght': 400, 'opsz': 14}, 80, False),
    ('inter-semibold', 'Inter[opsz,wght].ttf', {'wght': 600, 'opsz': 14}, 120, False),
    ('mono-medium', 'JetBrainsMono[wght].ttf', {'wght': 500}, 100, True),
]

UNICODES = list(range(0x20, 0x7F)) + list(range(0xA0, 0x180)) + [
    0x2013, 0x2014, 0x2018, 0x2019, 0x201A, 0x201C, 0x201D, 0x201E, 0x2020, 0x2021, 0x2022, 0x2026,
    0x2030, 0x2039, 0x203A, 0x20AC, 0x20BF, 0x2122, 0x2190, 0x2191, 0x2192, 0x2193, 0x2212, 0x2116,
    0x00D7, 0x2713, 0x2715, 0x25CF, 0x2219, 0x02C6, 0x02DC, 0x0152, 0x0153, 0x0160, 0x0161, 0x0178,
    0x017D, 0x017E, 0x0192, 0x2032, 0x2033, 0x20B9, 0x00A3, 0x00A5,
]
KERN_CHARS = [chr(c) for c in range(0x20, 0x7F)] + [chr(c) for c in range(0xC0, 0x100)] + ['’', '“', '”']


def kern_pairs(font):
    """Pair kerning (font units) for the characters in KERN_CHARS, from the GPOS kern feature."""
    if 'GPOS' not in font:
        return {}
    cmap = font.getBestCmap()
    gpos = font['GPOS'].table
    lookups = set()
    for fr in gpos.FeatureList.FeatureRecord:
        if fr.FeatureTag == 'kern':
            lookups.update(fr.Feature.LookupListIndex)
    subtables = []
    for li in sorted(lookups):
        lk = gpos.LookupList.Lookup[li]
        subs = []
        for st in lk.SubTable:
            if lk.LookupType == 9:
                if st.ExtensionLookupType != 2:
                    continue
                st = st.ExtSubTable
            elif lk.LookupType != 2:
                continue
            subs.append(st)
        if subs:
            subtables.append(subs)
    glyph = {ch: cmap.get(ord(ch)) for ch in KERN_CHARS}
    glyph = {ch: g for ch, g in glyph.items() if g}
    cov_cache = {}

    def covset(st):
        k = id(st)
        if k not in cov_cache:
            cov_cache[k] = {g: i for i, g in enumerate(st.Coverage.glyphs)}
        return cov_cache[k]

    pairs = {}
    for a, ga in glyph.items():
        for b, gb in glyph.items():
            total = 0
            for subs in subtables:
                for st in subs:
                    cov = covset(st)
                    if ga not in cov:
                        continue
                    if st.Format == 1:
                        ps = st.PairSet[cov[ga]]
                        hit = None
                        for rec in ps.PairValueRecord:
                            if rec.SecondGlyph == gb:
                                hit = rec
                                break
                        if hit is None:
                            continue
                        v = getattr(hit, 'Value1', None)
                        total += getattr(v, 'XAdvance', 0) or 0 if v is not None else 0
                        break
                    elif st.Format == 2:
                        c1 = st.ClassDef1.classDefs.get(ga, 0) if st.ClassDef1 else 0
                        c2 = st.ClassDef2.classDefs.get(gb, 0) if st.ClassDef2 else 0
                        rec = st.Class1Record[c1].Class2Record[c2]
                        v = getattr(rec, 'Value1', None)
                        total += (getattr(v, 'XAdvance', 0) or 0) if v is not None else 0
                        break
            if total:
                pairs[(a, b)] = total
    return pairs


def php_str(s):
    return "'" + s.replace('\\', '\\\\').replace("'", "\\'") + "'"


for key, file, axes, stemv, mono in FONTS:
    vf = TTFont(os.path.join(SRC, file))
    inst = instancer.instantiateVariableFont(vf, axes, inplace=False, updateFontNames=False)
    upm = inst['head'].unitsPerEm
    scale = 1000.0 / upm
    kern = kern_pairs(inst)

    opts = subset.Options()
    opts.layout_features = []
    opts.hinting = False
    opts.notdef_outline = True
    opts.name_IDs = [0, 1, 2, 3, 4, 5, 6]
    opts.drop_tables += ['GSUB', 'GPOS', 'GDEF', 'STAT', 'DSIG', 'MVAR', 'HVAR', 'avar', 'fvar', 'gvar']
    sub = subset.Subsetter(opts)
    sub.populate(unicodes=UNICODES)
    sub.subset(inst)
    buf = io.BytesIO()
    inst.save(buf)
    data = buf.getvalue()
    with open(os.path.join(OUT, key + '.ttf'), 'wb') as fh:
        fh.write(data)

    f = TTFont(io.BytesIO(data))
    cmap = f.getBestCmap()
    order = f.getGlyphOrder()
    gid = {name: i for i, name in enumerate(order)}
    hmtx = f['hmtx']
    head, os2, post, hhea = f['head'], f['OS/2'], f['post'], f['hhea']
    ps_name = f['name'].getDebugName(6) or key
    # Give each weight its own PostScript name: the instancer keeps the variable font's default.
    ps_name = {
        'sora-bold': 'Sora-Bold', 'sora-semibold': 'Sora-SemiBold', 'inter-regular': 'Inter-Regular',
        'inter-semibold': 'Inter-SemiBold', 'mono-medium': 'JetBrainsMono-Medium',
    }[key]
    widths = {}
    for name in order:
        widths[gid[name]] = int(round(hmtx[name][0] * scale))
    cmap_gid = {u: gid[n] for u, n in cmap.items() if n in gid}

    lines = ['<?php', '// Generated from ' + file + ' ' + repr(axes) + ' (SIL Open Font License 1.1). Do not edit.', 'return array(']
    lines.append("\t'name' => " + php_str(ps_name) + ',')
    lines.append("\t'asc' => %d," % round(os2.sTypoAscender * scale))
    lines.append("\t'desc' => %d," % round(os2.sTypoDescender * scale))
    lines.append("\t'cap' => %d," % round(getattr(os2, 'sCapHeight', os2.sTypoAscender * 0.7) * scale))
    lines.append("\t'bbox' => array( %d, %d, %d, %d )," % tuple(round(v * scale) for v in (head.xMin, head.yMin, head.xMax, head.yMax)))
    lines.append("\t'italic' => %d," % round(post.italicAngle))
    lines.append("\t'stemv' => %d," % stemv)
    lines.append("\t'flags' => %d," % (32 + (1 if mono else 0)))
    lines.append("\t'missing' => %d," % widths.get(0, 500))
    lines.append("\t'cmap' => array( " + ', '.join('%d => %d' % (u, g) for u, g in sorted(cmap_gid.items())) + ' ),')
    lines.append("\t'w' => array( " + ', '.join(str(widths[i]) for i in range(len(order))) + ' ),')
    kp = {}
    for (a, b), v in kern.items():
        if ord(a) in cmap_gid and ord(b) in cmap_gid:
            kp.setdefault(ord(a), {})[ord(b)] = int(round(v * scale))
    kparts = []
    for a in sorted(kp):
        inner = ', '.join('%d => %d' % (b, v) for b, v in sorted(kp[a].items()) if v)
        if inner:
            kparts.append('%d => array( %s )' % (a, inner))
    lines.append("\t'kern' => array( " + ', '.join(kparts) + ' ),')
    lines.append(');')
    with open(os.path.join(OUT, key + '.php'), 'w') as fh:
        fh.write('\n'.join(lines) + '\n')
    print(key, ps_name, 'glyphs', len(order), 'ttf', len(data), 'kern', sum(len(v) for v in kp.values()))
