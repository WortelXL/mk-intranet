import re
import sys

path = "assets/style.css"
src = open(path, encoding="utf-8").read()
orig = src

def must_replace(s, old, new, count=1):
    n = s.count(old)
    if n < count:
        raise SystemExit(f"EXPECTED at least {count}x: {old!r} (found {n})")
    return s.replace(old, new, count)

# ---- 1. :root palette --------------------------------------------------
src = must_replace(src, """    --bg:        #0a0d13;
    --panel:     #121722;
    --panel-2:   #171d2b;
    --border:    #232b3d;
    --text:      #e7eaf1;
    --muted:     #8b93a7;
    --teal:      #2dd4bf;
    --teal-dim:  #175c53;
    --red:       #ef4444;
    --green:     #22c55e;
    --blue:      #3b82f6;
    --gray:      #6b7280;""", """    /* Huisstijl in de kleuren van het MKintranet-logo (V0.1.35), zelfde
       merkpalet als mkapp (V2.0.2.46) en de MDT-app (V0.0.18). De
       variabelenaam --teal is bewust behouden zodat bestaande
       verwijzingen blijven werken, maar de waarde is nu het merkmagenta
       van het logo. */
    --bg:        #030917;
    --panel:     #0a1330;
    --panel-2:   #101c44;
    --border:    #1c2b5e;
    --text:      #f2f5ff;
    --muted:     #9faad3;
    --teal:      #e6007e;      /* merkmagenta: vlakken, randen, knoppen */
    --teal-text: #ff5cb1;      /* zelfde kleur, lichter voor tekst op donker (contrast) */
    --teal-dim:  #7a0a4b;
    --red:       #ff4d5a;
    --green:     #39b54a;
    --blue:      #0394d2;
    --gray:      #6e7ba8;""")

# ---- 2. background grid tint (was teal-tinted, now brand-blue like mkapp) --
src = must_replace(src, """    background-image:
        linear-gradient(rgba(45,212,191,0.025) 1px, transparent 1px),
        linear-gradient(90deg, rgba(45,212,191,0.025) 1px, transparent 1px);""",
"""    background-image:
        linear-gradient(rgba(60,110,255,0.035) 1px, transparent 1px),
        linear-gradient(90deg, rgba(60,110,255,0.035) 1px, transparent 1px);""")

# ---- 3. nav active state: white text on magenta instead of dark-on-teal --
src = must_replace(src, "nav.mainnav a.active { color: var(--bg); background: var(--teal); }",
                         "nav.mainnav a.active { color: #fff; background: var(--teal); }")
src = must_replace(src, ".nav-dropdown summary.active { color: var(--bg); background: var(--teal); }",
                         ".nav-dropdown summary.active { color: #fff; background: var(--teal); }")
src = must_replace(src, ".nav-dropdown-menu a.active { color: var(--bg); background: var(--teal); }",
                         ".nav-dropdown-menu a.active { color: #fff; background: var(--teal); }")

# ---- 4. rol-badge beheerder: tinted bg + light text -----------------------
src = must_replace(src, ".rol-badge.rol-beheerder { background: rgba(45,212,191,0.15); color: var(--teal); }",
                         ".rol-badge.rol-beheerder { background: rgba(230,0,126,0.18); color: var(--teal-text); }")

# ---- 5. btn-primary: white text on magenta, brightness hover like mkapp --
src = must_replace(src, """.btn-primary {
    background: var(--teal);
    color: #06231f;
    border-color: var(--teal);
    font-weight: 600;
}
.btn-primary:hover { background: #26bfab; }""", """.btn-primary {
    background: var(--teal);
    color: #fff;
    border-color: var(--teal);
    font-weight: 600;
}
.btn-primary:hover { border-color: var(--teal); filter: brightness(1.1); }""")

# ---- 6. chip-icon tinted bg + light text ----------------------------------
src = must_replace(src, "font-size: 14px; background: rgba(45,212,191,0.15); color: var(--teal);",
                         "font-size: 14px; background: rgba(230,0,126,0.18); color: var(--teal-text);")

# ---- 7. melding-log-bron: hardcoded hex blue -> brand blue ----------------
src = must_replace(src, "    color: #3b82f6;\n", "    color: var(--blue);\n")

# ---- 8. hardcoded inline SVG hex colours (plotbord status dropdown arrow) --
src = must_replace(src, "%2322c55e", "%2339b54a")
src = must_replace(src, "%238b93a7", "%239faad3")

# ---- 9. literal color: var(--teal) -> var(--teal-text) (text/foreground) --
literal_color_teal = [
    ("""    color: var(--teal);
    border: 1px solid var(--teal-dim);
    padding: 3px 7px;""", """    color: var(--teal-text);
    border: 1px solid var(--teal-dim);
    padding: 3px 7px;"""),
    ("""    font-size: 12.5px;
    color: var(--teal);
    letter-spacing: 0.06em;""", """    font-size: 12.5px;
    color: var(--teal-text);
    letter-spacing: 0.06em;"""),
    (""".bericht-link {
    color: var(--teal);
    font-size: 13px;
    word-break: break-all;
}""", """.bericht-link {
    color: var(--teal-text);
    font-size: 13px;
    word-break: break-all;
}"""),
    (".melding-link:hover { color: var(--teal); }", ".melding-link:hover { color: var(--teal-text); }"),
    (".pin-tooltip-locatie { font-family: var(--font-mono); font-size: 10.5px; color: var(--teal); font-weight: 700;",
     ".pin-tooltip-locatie { font-family: var(--font-mono); font-size: 10.5px; color: var(--teal-text); font-weight: 700;"),
    ("""    font-size: 13px;
    margin: 0 0 6px;
    color: var(--teal);
}
.wijzigingen-release-kop span""", """    font-size: 13px;
    margin: 0 0 6px;
    color: var(--teal-text);
}
.wijzigingen-release-kop span"""),
    (".versie-knop:hover { color: var(--teal); border-color: var(--teal-dim); }",
     ".versie-knop:hover { color: var(--teal-text); border-color: var(--teal-dim); }"),
    ("""    margin-right: 8px;
    color: var(--teal);
    transition: transform 0.15s ease;
}
.kb-item[open]""", """    margin-right: 8px;
    color: var(--teal-text);
    transition: transform 0.15s ease;
}
.kb-item[open]"""),
    (""".kb-beheer-item-kop-links::before,
.kb-beheer-doc-kop-links::before {
    content: "\\25b8";
    color: var(--teal);
    flex-shrink: 0;""", """.kb-beheer-item-kop-links::before,
.kb-beheer-doc-kop-links::before {
    content: "\\25b8";
    color: var(--teal-text);
    flex-shrink: 0;"""),
    (""".kb-beheer-tag {
    font-family: var(--font-mono);
    font-size: 10px;
    color: var(--teal);
    border: 1px solid var(--teal-dim);""", """.kb-beheer-tag {
    font-family: var(--font-mono);
    font-size: 10px;
    color: var(--teal-text);
    border: 1px solid var(--teal-dim);"""),
    ("""    color: var(--teal);
}
.rijke-editor-wrap .ql-snow.ql-toolbar button:hover .ql-stroke""", """    color: var(--teal-text);
}
.rijke-editor-wrap .ql-snow.ql-toolbar button:hover .ql-stroke"""),
    (".rijke-editor-wrap .ql-snow.ql-toolbar .ql-picker-label:hover .ql-stroke {\n    stroke: var(--teal);\n}",
     ".rijke-editor-wrap .ql-snow.ql-toolbar .ql-picker-label:hover .ql-stroke {\n    stroke: var(--teal-text);\n}"),
    (".rijke-editor-wrap .ql-snow.ql-toolbar button.ql-active .ql-fill {\n    fill: var(--teal);\n}",
     ".rijke-editor-wrap .ql-snow.ql-toolbar button.ql-active .ql-fill {\n    fill: var(--teal-text);\n}"),
    (".rijke-editor-wrap .ql-snow a { color: var(--teal); }",
     ".rijke-editor-wrap .ql-snow a { color: var(--teal-text); }"),
    (".kb-beheer-tekst a,\n.kb-item-antwoord a,\n.bericht-card a { color: var(--teal); }",
     ".kb-beheer-tekst a,\n.kb-item-antwoord a,\n.bericht-card a { color: var(--teal-text); }"),
]

for old, new in literal_color_teal:
    src = must_replace(src, old, new)

remaining = re.findall(r'^\s*([a-zA-Z-]+):\s*[^;]*var\(--teal\)[^;]*;', src, re.M)
allowed_props = {"background", "background-color", "border", "border-color",
                  "border-top-color", "border-left", "outline", "box-shadow",
                  "stroke", "fill"}
bad = [p for p in remaining if p not in allowed_props]
if bad:
    raise SystemExit(f"Unexpected remaining var(--teal) property types: {bad}")

# ---- 10. .brand-mark becomes an image badge instead of a text badge -------
src = must_replace(src, """.brand-mark {
    display: block;
    width: 40px;
    height: 40px;
    border-radius: 10px;
    box-shadow: 0 0 0 1px rgba(255,255,255,0.18);
}""", "PLACEHOLDER_SKIP") if False else src

open(path, "w", encoding="utf-8").write(src)
print("style.css transformed OK. bytes:", len(src), "(was", len(orig), ")")
print("remaining var(--teal) usages (background/border/etc, unchanged):", len(remaining))
