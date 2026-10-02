#!/usr/bin/env python3
"""Erzeugt Logo, Favicons, Social-Image und Illustrationen für Vereinsverleih.

Benötigt: inkscape, imagemagick (convert). Aufruf: python3 brand/build.py
Farben/Formen oben anpassen, Skript erneut laufen lassen.
"""
import os, subprocess, shutil, textwrap

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
PUB = os.path.join(ROOT, 'public')
DOCS = os.path.join(ROOT, 'docs')
TMP = os.path.join(ROOT, 'brand', '.tmp')
os.makedirs(os.path.join(PUB, 'images'), exist_ok=True)
os.makedirs(TMP, exist_ok=True)

TEAL, TEAL_D, TEAL_L = '#1f6f5c', '#17594a', '#2a8a73'
ORANGE, ORANGE_D = '#e8833a', '#c8501e'
CREAM, MINT = '#fbfaf7', '#e7f2ee'


def write(path, content):
    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)


def png(svg, out, w, h=None):
    h = h or w
    subprocess.run(['inkscape', svg, '--export-type=png', f'--export-filename={out}',
                    f'--export-width={w}', f'--export-height={h}'], check=True, capture_output=True)


# ---------- Logo: Würfel (Inventar) + Tausch-Badge (Verleih) ----------
def mark_body():
    return f'''
  <polygon points="32,11 51,21.5 32,32 13,21.5" fill="#ffffff"/>
  <polygon points="13,21.5 32,32 32,53 13,42.5" fill="#cfeae1"/>
  <polygon points="51,21.5 32,32 32,53 51,42.5" fill="#9fd3c3"/>
  <circle cx="47" cy="46" r="10.5" fill="{ORANGE}" stroke="{TEAL_D}" stroke-width="2.5"/>
  <g fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
    <path d="M41.5 43.5H51.5M48.8 40.8L51.5 43.5L48.8 46.2"/>
    <path d="M52.5 48.5H42.5M45.2 45.8L42.5 48.5L45.2 51.2"/>
  </g>'''


def mark_svg(rx=14, scale=1.0):
    defs = f'<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="{TEAL_L}"/><stop offset="1" stop-color="{TEAL_D}"/></linearGradient></defs>'
    off = (64 - 64 * scale) / 2
    body = mark_body() if scale == 1 else f'<g transform="translate({off} {off}) scale({scale})">{mark_body()}</g>'
    return (f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="64" height="64">{defs}'
            f'<rect width="64" height="64" rx="{rx}" fill="url(#g)"/>{body}</svg>\n')


# ---------- Szene (Hero + Social-Image) ----------
def scene():
    flags = ''
    colors = [ORANGE, '#ffffff', TEAL, '#f6c453']
    n = 14
    for i in range(n):
        x = 60 + i * (680 / (n - 1))
        t = i / (n - 1)
        y = 118 + 34 * (1 - (2 * t - 1) ** 2)  # Durchhang der Wimpelkette
        flags += f'<polygon points="{x-14:.1f},{y:.1f} {x+14:.1f},{y:.1f} {x:.1f},{y+30:.1f}" fill="{colors[i % 4]}"/>'
    string = 'M60 118 Q400 190 740 118'

    stripes = ''.join(f'<rect x="{190 + i*40}" y="262" width="20" height="116" fill="{ORANGE}" opacity=".9"/>' for i in range(6))
    scallops = ''.join(f'<path d="M{170 + i*40} 262 a20 20 0 0 0 40 0z" fill="{ORANGE_D}"/>' for i in range(7))

    return f'''
  <defs>
    <linearGradient id="sky" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#cfe8f1"/><stop offset="1" stop-color="{CREAM}"/></linearGradient>
  </defs>
  <rect width="800" height="480" fill="url(#sky)"/>
  <circle cx="660" cy="86" r="62" fill="#f6c453" opacity=".25"/>
  <circle cx="660" cy="86" r="40" fill="#f6c453"/>
  <g fill="#fff" opacity=".9"><ellipse cx="150" cy="70" rx="46" ry="14"/><ellipse cx="190" cy="60" rx="30" ry="12"/><ellipse cx="520" cy="52" rx="40" ry="12"/><ellipse cx="552" cy="44" rx="24" ry="10"/></g>
  <path d="M0 330 Q150 250 320 310 T640 290 T800 320 V480 H0Z" fill="#bfe0d5"/>
  <path d="M0 380 Q200 320 400 370 T800 350 V480 H0Z" fill="#8cc9b2"/>
  <rect y="410" width="800" height="70" fill="#5fae93"/>
  <rect y="410" width="800" height="6" fill="#4f9d82" opacity=".6"/>

  <path d="{string}" stroke="#8a6a4a" stroke-width="2.5" fill="none"/>
  {flags}

  <!-- Festzelt -->
  <rect x="170" y="262" width="260" height="124" fill="#ffffff"/>
  {stripes}
  <polygon points="150,262 300,168 450,262" fill="{ORANGE_D}"/>
  <polygon points="300,168 450,262 300,262" fill="#a93f16" opacity=".55"/>
  {scallops}
  <path d="M268 386 V318 a32 32 0 0 1 64 0 V386z" fill="#2b3a42"/>
  <rect x="296" y="150" width="8" height="22" fill="#8a6a4a"/>
  <polygon points="304,150 330,158 304,166" fill="{TEAL}"/>

  <!-- Hüpfburg -->
  <g>
    <rect x="30" y="322" width="112" height="64" rx="14" fill="#f4b942"/>
    <circle cx="52" cy="314" r="18" fill="#f08a5d"/><circle cx="86" cy="308" r="18" fill="#f4b942"/><circle cx="120" cy="314" r="18" fill="#f08a5d"/>
    <rect x="56" y="346" width="40" height="40" rx="10" fill="#fff" opacity=".35"/>
  </g>

  <!-- Bierzeltgarnitur -->
  <g>
    <rect x="500" y="338" width="170" height="12" rx="3" fill="#b9824f"/>
    <rect x="512" y="350" width="8" height="40" fill="#8a6a4a"/><rect x="650" y="350" width="8" height="40" fill="#8a6a4a"/>
    <rect x="492" y="364" width="186" height="9" rx="3" fill="#d09a62"/>
    <rect x="506" y="373" width="7" height="22" fill="#8a6a4a"/><rect x="657" y="373" width="7" height="22" fill="#8a6a4a"/>
    <rect x="540" y="322" width="16" height="16" rx="3" fill="#fff"/><rect x="540" y="326" width="16" height="4" fill="#f6c453"/>
    <rect x="600" y="322" width="16" height="16" rx="3" fill="#fff"/><rect x="600" y="326" width="16" height="4" fill="#f6c453"/>
  </g>

  <!-- Lautsprecher -->
  <g>
    <rect x="700" y="296" width="54" height="96" rx="8" fill="#2f3b45"/>
    <circle cx="727" cy="346" r="17" fill="#46586a"/><circle cx="727" cy="346" r="8" fill="#2f3b45"/>
    <circle cx="727" cy="316" r="8" fill="#46586a"/><circle cx="727" cy="316" r="3" fill="#2f3b45"/>
  </g>

  <!-- Kistenstapel (Inventar) -->
  <g transform="translate(448 372) scale(.9)"><polygon points="32,11 51,21.5 32,32 13,21.5" fill="#ffffff"/><polygon points="13,21.5 32,32 32,53 13,42.5" fill="#cfeae1"/><polygon points="51,21.5 32,32 32,53 51,42.5" fill="#9fd3c3"/></g>
  <g transform="translate(482 372) scale(.9)"><polygon points="32,11 51,21.5 32,32 13,21.5" fill="#ffffff"/><polygon points="13,21.5 32,32 32,53 13,42.5" fill="#cfeae1"/><polygon points="51,21.5 32,32 32,53 51,42.5" fill="#9fd3c3"/></g>
  <g transform="translate(465 349) scale(.9)"><polygon points="32,11 51,21.5 32,32 13,21.5" fill="#ffffff"/><polygon points="13,21.5 32,32 32,53 13,42.5" fill="#cfeae1"/><polygon points="51,21.5 32,32 32,53 51,42.5" fill="#9fd3c3"/></g>

  <!-- Tausch-Badge -->
  <g transform="translate(300 84)">
    <circle r="30" fill="{ORANGE}" stroke="#fff" stroke-width="5"/>
    <g fill="none" stroke="#fff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round">
      <path d="M-14 -7H14M7 -14L14 -7L7 0"/><path d="M14 9H-14M-7 2L-14 9L-7 16"/>
    </g>
  </g>
'''


def hero_svg():
    return f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 480" role="img" aria-label="Festzelt, Hüpfburg, Bierzeltgarnitur und Lautsprecher auf einer Wiese">{scene()}</svg>\n'


def empty_svg():
    return f'''<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 220" role="img" aria-label="Leere Kiste">
  <ellipse cx="160" cy="188" rx="96" ry="14" fill="#7fc0a8" opacity=".25"/>
  <g stroke-linejoin="round">
    <polygon points="160,78 232,114 160,150 88,114" fill="#fff" stroke="#7fc0a8" stroke-width="3"/>
    <polygon points="88,114 160,150 160,186 88,150" fill="#e7f2ee" stroke="#7fc0a8" stroke-width="3"/>
    <polygon points="232,114 160,150 160,186 232,150" fill="#cfe7de" stroke="#7fc0a8" stroke-width="3"/>
    <polygon points="160,78 232,114 160,100 88,114" fill="#b5e0d3" opacity=".0"/>
  </g>
  <polygon points="88,114 120,98 160,118 124,134" fill="#b5e0d3" stroke="#7fc0a8" stroke-width="3" stroke-linejoin="round" opacity=".9"/>
  <polygon points="232,114 200,98 160,118 196,134" fill="#9fd3c3" stroke="#7fc0a8" stroke-width="3" stroke-linejoin="round" opacity=".9"/>
  <g stroke="{ORANGE}" stroke-width="4" stroke-linecap="round"><path d="M160 36V54"/><path d="M118 48L128 62"/><path d="M202 48L192 62"/></g>
  <circle cx="250" cy="60" r="5" fill="#f6c453"/><circle cx="70" cy="84" r="4" fill="{ORANGE}" opacity=".7"/><circle cx="262" cy="150" r="3" fill="#7fc0a8"/>
</svg>
'''


def og_svg():
    # Karte rechts: Szene 800x480 auf 430x258 skaliert
    cx, cy, cw = 720, 130, 430
    sc = cw / 800
    ch = 480 * sc
    return f'''<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 630" width="1200" height="630">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="{TEAL}"/><stop offset="1" stop-color="#123f35"/></linearGradient>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="{TEAL_L}"/><stop offset="1" stop-color="{TEAL_D}"/></linearGradient>
    <clipPath id="card"><rect x="{cx}" y="{cy}" width="{cw}" height="{ch:.1f}" rx="22"/></clipPath>
  </defs>
  <rect width="1200" height="630" fill="url(#bg)"/>
  <circle cx="1100" cy="590" r="260" fill="#ffffff" opacity=".05"/><circle cx="60" cy="30" r="190" fill="#ffffff" opacity=".05"/>

  <g transform="translate(70 62) scale(1.25)"><rect width="64" height="64" rx="14" fill="url(#g)" stroke="#ffffff" stroke-opacity=".35" stroke-width="1.5"/>{mark_body()}</g>
  <text x="168" y="124" font-family="Noto Sans, DejaVu Sans, sans-serif" font-weight="700" font-size="40" fill="#ffffff" letter-spacing="1">Vereinsverleih</text>

  <text x="70" y="262" font-family="Noto Sans, DejaVu Sans, sans-serif" font-weight="800" font-size="56" fill="#ffffff">Was ein Verein hat,</text>
  <text x="70" y="330" font-family="Noto Sans, DejaVu Sans, sans-serif" font-weight="800" font-size="56" fill="#f6c453">kann der nächste</text>
  <text x="70" y="398" font-family="Noto Sans, DejaVu Sans, sans-serif" font-weight="800" font-size="56" fill="#f6c453">leihen.</text>
  <text x="70" y="468" font-family="Noto Sans, DejaVu Sans, sans-serif" font-size="28" fill="#cfeae1">Vereinsinventar teilen, anfragen, verwalten.</text>

  <g clip-path="url(#card)"><g transform="translate({cx} {cy}) scale({sc:.4f})">{scene()}</g></g>
  <rect x="{cx}" y="{cy}" width="{cw}" height="{ch:.1f}" rx="22" fill="none" stroke="#ffffff" stroke-opacity=".55" stroke-width="3"/>
  <text x="{cx + cw}" y="{cy + ch + 46:.0f}" text-anchor="end" font-family="Noto Sans, DejaVu Sans, sans-serif" font-size="24" fill="#cfeae1">Open Source · MIT-Lizenz</text>

  <rect x="70" y="520" width="370" height="56" rx="28" fill="#ffffff" opacity=".13"/>
  <text x="104" y="558" font-family="Noto Sans, DejaVu Sans, sans-serif" font-weight="600" font-size="26" fill="#ffffff">vereinsverleih.rmbn.de</text>
</svg>
'''



# ---------- Anmeldeseiten: zwei Hochformat-Szenen (800x1000) ----------
CUBE = '''<polygon points="32,11 51,21.5 32,32 13,21.5" fill="#ffffff"/><polygon points="13,21.5 32,32 32,53 13,42.5" fill="#cfeae1"/><polygon points="51,21.5 32,32 32,53 51,42.5" fill="#9fd3c3"/>'''


def swap_badge(x, y, r=34):
    k = r / 30
    return (f'<g transform="translate({x} {y}) scale({k:.3f})"><circle r="30" fill="{ORANGE}" stroke="#fff" stroke-width="5"/>'
            '<g fill="none" stroke="#fff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round">'
            '<path d="M-14 -7H14M7 -14L14 -7L7 0"/><path d="M14 9H-14M-7 2L-14 9L-7 16"/></g></g>')


def tent_local(stripe=ORANGE, roof=ORANGE_D, shade='#a93f16', body='#ffffff', door='#2b3a42'):
    stripes = ''.join(f'<rect x="{-110 + i*40}" y="-124" width="20" height="124" fill="{stripe}"/>' for i in range(6))
    scallops = ''.join(f'<path d="M{-150 + i*40} -124 a20 20 0 0 0 40 0z" fill="{roof}"/>' for i in range(8))
    return (f'<rect x="-130" y="-124" width="260" height="124" fill="{body}"/>{stripes}'
            f'<polygon points="-150,-124 0,-218 150,-124" fill="{roof}"/><polygon points="0,-218 150,-124 0,-124" fill="{shade}" opacity=".55"/>'
            f'{scallops}<path d="M-32 0V-68a32 32 0 0 1 64 0V0z" fill="{door}"/>'
            f'<rect x="-4" y="-240" width="8" height="24" fill="#8a6a4a"/><polygon points="4,-240 30,-232 4,-224" fill="{TEAL}"/>')


def house_local(wall, roof, door):
    return (f'<rect x="-70" y="-90" width="140" height="90" fill="{wall}"/>'
            f'<polygon points="-88,-90 0,-152 88,-90" fill="{roof}"/><rect x="38" y="-146" width="16" height="34" fill="{roof}"/>'
            f'<rect x="-16" y="-52" width="32" height="52" rx="4" fill="{door}"/>'
            f'<rect x="-56" y="-70" width="28" height="26" rx="3" fill="#cfe8f1"/><rect x="28" y="-70" width="28" height="26" rx="3" fill="#cfe8f1"/>'
            f'<rect x="-1.5" y="-190" width="3" height="40" fill="#8a6a4a"/><polygon points="1.5,-190 26,-182 1.5,-174" fill="{ORANGE}"/>')


def auth_welcome_svg():
    stars = ''.join(f'<circle cx="{x}" cy="{y}" r="{r}" fill="#fff" opacity="{o}"/>' for x, y, r, o in
                    [(80,90,2,.8),(180,200,1.6,.6),(300,60,2.2,.9),(420,170,1.5,.6),(520,80,2,.8),(700,260,1.8,.7),(130,330,1.5,.5),
                     (360,290,1.4,.5),(600,350,1.6,.5),(740,70,2.2,.9),(250,140,1.2,.5),(470,40,1.6,.7),(60,240,1.4,.5),(660,170,1.3,.6)])
    bulbs = ''
    for i in range(11):
        t = i / 10
        x = 90 + t * 620
        y = 560 + 46 * (1 - (2 * t - 1) ** 2)
        bulbs += f'<circle cx="{x:.0f}" cy="{y:.0f}" r="14" fill="#fde68a" opacity=".22"/><circle cx="{x:.0f}" cy="{y:.0f}" r="6" fill="#fde68a"/>'
    flies = ''.join(f'<circle cx="{x}" cy="{y}" r="2.4" fill="#fde68a" opacity=".85"/>' for x, y in [(110,700),(250,640),(560,690),(690,640),(640,760),(60,760)])
    return f'''<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 1000" preserveAspectRatio="xMidYMid slice" role="img" aria-label="Abendstimmung auf der Vereinswiese: beleuchtetes Festzelt unter Sternen">
  <defs>
    <linearGradient id="dusk" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#0c2b24"/><stop offset=".45" stop-color="#18594a"/><stop offset=".66" stop-color="#e59a4a"/><stop offset=".74" stop-color="#f6c453"/></linearGradient>
    <radialGradient id="glow" cx=".5" cy=".5" r=".5"><stop offset="0" stop-color="#fde68a" stop-opacity=".8"/><stop offset="1" stop-color="#fde68a" stop-opacity="0"/></radialGradient>
  </defs>
  <rect width="800" height="1000" fill="url(#dusk)"/>
  {stars}
  <circle cx="620" cy="170" r="84" fill="#fdf3c8" opacity=".14"/><circle cx="620" cy="170" r="48" fill="#fdf3c8"/>
  <rect y="900" width="800" height="100" fill="#0b2f26"/>
  <g transform="translate(0 -85)">
  <path d="M0 700 Q160 610 360 680 T800 650 V1000 H0Z" fill="#1d6b58"/>
  <path d="M0 760 Q220 690 430 750 T800 730 V1000 H0Z" fill="#14503f"/>
  <path d="M0 820 Q260 780 520 825 T800 810 V1000 H0Z" fill="#0e3a2f"/>
  <rect y="880" width="800" height="120" fill="#0b2f26"/>

  <rect x="86" y="520" width="8" height="270" fill="#6b4f35"/><rect x="706" y="520" width="8" height="270" fill="#6b4f35"/>
  <path d="M90 560 Q400 650 710 560" stroke="#6b4f35" stroke-width="2.5" fill="none"/>
  {bulbs}

  <circle cx="400" cy="722" r="190" fill="url(#glow)"/>
  <g transform="translate(400 800) scale(1.18)">{tent_local(stripe="#d9772f", roof="#9c3d15", shade="#7e2f10", body="#f1dcc6", door="#fcd877")}</g>

  <g transform="translate(150 835) scale(.9)">{CUBE}</g><g transform="translate(186 835) scale(.9)">{CUBE}</g><g transform="translate(168 812) scale(.9)">{CUBE}</g>
  <g opacity=".95">
    <rect x="560" y="800" width="170" height="11" rx="3" fill="#8a6a4a"/><rect x="574" y="811" width="8" height="38" fill="#5f4630"/><rect x="708" y="811" width="8" height="38" fill="#5f4630"/>
    <rect x="552" y="824" width="186" height="9" rx="3" fill="#a07448"/>
    <rect x="598" y="784" width="16" height="16" rx="3" fill="#fde68a"/><rect x="660" y="784" width="16" height="16" rx="3" fill="#fde68a"/>
  </g>
  {flies}
  {swap_badge(400, 430, 38)}
  </g>
</svg>
'''


def auth_register_svg():
    flags = ''
    cols = [ORANGE, '#ffffff', TEAL, '#f6c453']
    n = 12
    for i in range(n):
        t = i / (n - 1)
        x = 170 + t * 460
        y = 468 + 40 * (1 - (2 * t - 1) ** 2)
        flags += f'<polygon points="{x-13:.1f},{y:.1f} {x+13:.1f},{y:.1f} {x:.1f},{y+28:.1f}" fill="{cols[i % 4]}"/>'
    return f'''<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 1000" preserveAspectRatio="xMidYMid slice" role="img" aria-label="Zwei Vereinsheime tauschen per Handwagen Kisten aus">
  <defs><linearGradient id="sky2" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#bfe3ee"/><stop offset=".62" stop-color="{CREAM}"/></linearGradient></defs>
  <rect width="800" height="1000" fill="url(#sky2)"/>
  <circle cx="640" cy="190" r="86" fill="#f6c453" opacity=".25"/><circle cx="640" cy="190" r="52" fill="#f6c453"/>
  <g fill="#fff" opacity=".95"><ellipse cx="170" cy="150" rx="70" ry="20"/><ellipse cx="226" cy="132" rx="44" ry="17"/><ellipse cx="470" cy="300" rx="60" ry="17"/><ellipse cx="514" cy="286" rx="34" ry="14"/></g>
  <rect y="900" width="800" height="100" fill="#5fae93"/>
  <g transform="translate(0 -85)">
  <path d="M0 640 Q170 560 360 620 T800 600 V1000 H0Z" fill="#bfe0d5"/>
  <path d="M0 700 Q240 640 470 690 T800 670 V1000 H0Z" fill="#8cc9b2"/>
  <rect y="760" width="800" height="240" fill="#5fae93"/>
  <path d="M0 800 Q400 770 800 800 V860 Q400 830 0 860Z" fill="#e9dcc6"/>

  <g transform="translate(170 760)">{house_local('#ffffff', ORANGE_D, '#2b3a42')}</g>
  <g transform="translate(630 760)">{house_local('#fdf0dc', TEAL, '#7a4b2a')}</g>
  <path d="M170 470 Q400 540 630 470" stroke="#8a6a4a" stroke-width="2.5" fill="none"/>
  {flags}

  <!-- Handwagen mit Kisten in der Mitte -->
  <g transform="translate(400 836)">
    <rect x="-56" y="-34" width="112" height="30" rx="4" fill="#b9824f"/><rect x="-56" y="-34" width="112" height="7" fill="#d09a62"/>
    <line x1="56" y1="-24" x2="104" y2="-52" stroke="#8a6a4a" stroke-width="6" stroke-linecap="round"/>
    <circle cx="-30" cy="-2" r="16" fill="#5a4630"/><circle cx="-30" cy="-2" r="6" fill="#d9c9ae"/><circle cx="32" cy="-2" r="16" fill="#5a4630"/><circle cx="32" cy="-2" r="6" fill="#d9c9ae"/>
    <g transform="translate(-52 -74) scale(.95)">{CUBE}</g><g transform="translate(-14 -74) scale(.95)">{CUBE}</g><g transform="translate(-33 -98) scale(.95)">{CUBE}</g>
  </g>
  {swap_badge(400, 640, 44)}
  <g stroke="{ORANGE}" stroke-width="4" stroke-linecap="round" opacity=".8"><path d="M400 574V588"/><path d="M360 586L368 598"/><path d="M440 586L432 598"/></g>
  </g>
</svg>
'''


def mono_icon_note():
    # Die einfarbige Variante (currentColor) liegt als Vue-Komponente in resources/js/components/AppLogoIcon.vue
    pass


def main():
    # SVG-Quellen
    write(os.path.join(ROOT, 'brand', 'mark.svg'), mark_svg())
    write(os.path.join(PUB, 'favicon.svg'), mark_svg())
    write(os.path.join(PUB, 'logo.svg'), mark_svg())
    write(os.path.join(PUB, 'images', 'hero.svg'), hero_svg())
    write(os.path.join(PUB, 'images', 'empty-box.svg'), empty_svg())
    write(os.path.join(PUB, 'images', 'auth-welcome.svg'), auth_welcome_svg())
    write(os.path.join(PUB, 'images', 'auth-register.svg'), auth_register_svg())
    write(os.path.join(ROOT, 'brand', 'og-image.svg'), og_svg())

    # Rastergrafiken
    sq = os.path.join(TMP, 'square.svg'); write(sq, mark_svg(rx=0))
    maskable = os.path.join(TMP, 'maskable.svg'); write(maskable, mark_svg(rx=0, scale=0.72))
    rounded = os.path.join(PUB, 'favicon.svg')

    png(rounded, os.path.join(PUB, 'favicon-16x16.png'), 16)
    png(rounded, os.path.join(PUB, 'favicon-32x32.png'), 32)
    png(rounded, os.path.join(PUB, 'icon-192.png'), 192)
    png(rounded, os.path.join(PUB, 'icon-512.png'), 512)
    png(sq, os.path.join(PUB, 'apple-touch-icon.png'), 180)          # iOS rundet selbst ab
    png(maskable, os.path.join(PUB, 'icon-maskable-512.png'), 512)   # Android "maskable" mit Sicherheitsrand
    png(os.path.join(ROOT, 'brand', 'og-image.svg'), os.path.join(PUB, 'og-image.png'), 1200, 630)

    # favicon.ico (16/32/48)
    for s in (16, 32, 48):
        png(rounded, os.path.join(TMP, f'ico{s}.png'), s)
    subprocess.run(['convert', *[os.path.join(TMP, f'ico{s}.png') for s in (16, 32, 48)], os.path.join(PUB, 'favicon.ico')], check=True)

    # Web-App-Manifest
    write(os.path.join(PUB, 'site.webmanifest'), textwrap.dedent('''\
        {
          "name": "Vereinsverleih",
          "short_name": "Verleih",
          "description": "Vereinsinventar teilen, anfragen und verwalten",
          "start_url": "/",
          "display": "standalone",
          "background_color": "#fbfaf7",
          "theme_color": "#1f6f5c",
          "lang": "de",
          "icons": [
            { "src": "/icon-192.png", "sizes": "192x192", "type": "image/png" },
            { "src": "/icon-512.png", "sizes": "512x512", "type": "image/png" },
            { "src": "/icon-maskable-512.png", "sizes": "512x512", "type": "image/png", "purpose": "maskable" }
          ]
        }
        '''))

    # Onepager (GitHub Pages dient nur /docs aus)
    for name in ('favicon.svg', 'favicon.ico', 'apple-touch-icon.png', 'og-image.png', 'favicon-32x32.png'):
        shutil.copy(os.path.join(PUB, name), os.path.join(DOCS, name))
    shutil.copy(os.path.join(PUB, 'images', 'hero.svg'), os.path.join(DOCS, 'hero.svg'))

    shutil.rmtree(TMP, ignore_errors=True)
    print('Fertig.')


if __name__ == '__main__':
    main()
