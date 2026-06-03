"""
Generate comment-casser-un-automatisme-en-24h.pdf
Author: Roland Crettaz — Neuro-Odyssée

Run:
    python3 /Users/martialnicola/Downloads/neuro-odyssee/pdf/generate_automatisme.py

Requirements:
    pip3 install fpdf2
    DejaVu fonts at /tmp/dejavu-fonts/dejavu-fonts-ttf-2.37/ttf/
"""

import os
from fpdf import FPDF

# ---------------------------------------------------------------------------
# Config
# ---------------------------------------------------------------------------
FONT_DIR = "/tmp/dejavu-fonts/dejavu-fonts-ttf-2.37/ttf"
OUTPUT   = "/Users/martialnicola/Downloads/neuro-odyssee/pdf/comment-casser-un-automatisme-en-24h.pdf"

TEAL  = (30, 107, 94)
GOLD  = (240, 165, 0)
DARK  = (26, 35, 50)
CREAM = (248, 246, 241)
WHITE = (255, 255, 255)

LM = 25   # left margin mm
RM = 25   # right margin mm
TM = 20   # top margin mm


# ---------------------------------------------------------------------------
# PDF class
# ---------------------------------------------------------------------------
class Doc(FPDF):

    def __init__(self):
        super().__init__(orientation="P", unit="mm", format="A4")
        self.set_margins(LM, TM, RM)
        self.set_auto_page_break(auto=True, margin=22)
        self._chapter = ""
        self._load_fonts()

    def _load_fonts(self):
        self.add_font("D",  "",   os.path.join(FONT_DIR, "DejaVuSans.ttf"))
        self.add_font("D",  "B",  os.path.join(FONT_DIR, "DejaVuSans-Bold.ttf"))
        self.add_font("D",  "I",  os.path.join(FONT_DIR, "DejaVuSans-Oblique.ttf"))
        self.add_font("D",  "BI", os.path.join(FONT_DIR, "DejaVuSans-BoldOblique.ttf"))

    # ------------------------------------------------------------------
    # Header / footer (skip cover)
    # ------------------------------------------------------------------
    def header(self):
        if self.page_no() <= 1:
            return
        self.set_draw_color(*GOLD)
        self.set_line_width(0.5)
        self.line(LM, 10, self.w - RM, 10)
        if self._chapter:
            self.set_font("D", "I", 8)
            self.set_text_color(*TEAL)
            self.set_xy(LM, 12)
            self.cell(self.w - LM - RM, 5, self._chapter, align="R")
        self.set_y(TM + 3)

    def footer(self):
        if self.page_no() <= 1:
            return
        self.set_y(-14)
        self.set_draw_color(*GOLD)
        self.set_line_width(0.35)
        self.line(LM, self.h - 14, self.w - RM, self.h - 14)
        self.set_font("D", "", 8)
        self.set_text_color(*TEAL)
        self.cell(0, 8, f"neuro-odyssee.com  \u00b7  {self.page_no()}", align="C")

    # ------------------------------------------------------------------
    # Utilities
    # ------------------------------------------------------------------
    def chapter(self, name: str):
        self._chapter = name

    def usable_w(self) -> float:
        return self.w - LM - RM

    def gold_hr(self, t: float = 0.8, before: float = 3, after: float = 4):
        self.ln(before)
        self.set_draw_color(*GOLD)
        self.set_line_width(t)
        self.line(LM, self.get_y(), self.w - RM, self.get_y())
        self.ln(after)

    def teal_hr(self, before: float = 2, after: float = 3):
        self.ln(before)
        self.set_draw_color(*TEAL)
        self.set_line_width(0.35)
        self.line(LM, self.get_y(), self.w - RM, self.get_y())
        self.ln(after)

    def body(self, txt: str, size: float = 11, lh: float = 7):
        self.set_font("D", "", size)
        self.set_text_color(*DARK)
        self.multi_cell(self.usable_w(), lh, txt, align="J")
        self.ln(2)

    def bold(self, txt: str, size: float = 11, lh: float = 7):
        self.set_font("D", "B", size)
        self.set_text_color(*DARK)
        self.multi_cell(self.usable_w(), lh, txt, align="J")
        self.ln(1)

    def italic(self, txt: str, size: float = 11, lh: float = 7):
        self.set_font("D", "I", size)
        self.set_text_color(80, 80, 80)
        self.multi_cell(self.usable_w(), lh, txt, align="J")
        self.ln(1)

    def chapter_title(self, label: str, title: str):
        self.ln(4)
        if label:
            self.set_font("D", "B", 12)
            self.set_text_color(*GOLD)
            self.cell(0, 8, label, align="L")
            self.ln(7)
        self.set_font("D", "B", 20)
        self.set_text_color(*TEAL)
        self.multi_cell(self.usable_w(), 11, title, align="L")
        self.ln(2)
        self.gold_hr(1.2, 1, 6)

    def section(self, title: str, size: float = 14):
        self.ln(5)
        self.set_font("D", "B", size)
        self.set_text_color(*TEAL)
        self.multi_cell(self.usable_w(), 8, title, align="L")
        self.teal_hr(1, 3)

    def sub(self, title: str):
        self.ln(3)
        self.set_font("D", "B", 11)
        self.set_text_color(*GOLD)
        self.multi_cell(self.usable_w(), 7, title, align="L")
        self.ln(1)

    def bullet(self, txt: str, indent: float = 4):
        y0 = self.get_y()
        self.set_font("D", "B", 14)
        self.set_text_color(*GOLD)
        self.set_xy(LM + indent, y0)
        self.cell(6, 7, "\u2022")
        self.set_font("D", "", 11)
        self.set_text_color(*DARK)
        self.set_xy(LM + indent + 6, y0)
        self.multi_cell(self.usable_w() - indent - 6, 7, txt, align="J")
        self.ln(1)

    def step(self, n: int, title: str, body: str):
        y0 = self.get_y()
        # teal badge
        self.set_fill_color(*TEAL)
        self.set_font("D", "B", 11)
        self.set_text_color(*WHITE)
        self.set_xy(LM, y0)
        self.cell(8, 8, str(n), fill=True, align="C")
        # title
        self.set_font("D", "B", 11)
        self.set_text_color(*TEAL)
        self.set_xy(LM + 11, y0)
        self.multi_cell(self.usable_w() - 11, 8, title, align="L")
        # body
        self.set_font("D", "", 11)
        self.set_text_color(*DARK)
        self.set_x(LM + 11)
        self.multi_cell(self.usable_w() - 11, 7, body, align="J")
        self.ln(4)

    def quote(self, txt: str, src: str = ""):
        self.ln(3)
        y0 = self.get_y()
        self.set_fill_color(*TEAL)
        self.rect(LM, y0, 1.5, 22, "F")
        self.set_xy(LM + 6, y0 + 2)
        self.set_font("D", "I", 11)
        self.set_text_color(70, 70, 80)
        self.multi_cell(self.usable_w() - 8, 7,
                        f"\u201c{txt}\u201d", align="J")
        if src:
            self.set_x(LM + 6)
            self.set_font("D", "B", 9)
            self.set_text_color(*TEAL)
            self.cell(0, 6, f"\u2014 {src}", align="R")
        self.ln(5)

    def cream_box(self, title: str, lines: list[str], icon: str = ""):
        """lines: list of strings; prefix '•' for bullets, '**text**' for bold."""
        self.ln(4)
        bw = self.usable_w() + 6
        x0 = LM - 3
        y0 = self.get_y()

        # Estimate height
        self.set_font("D", "", 10.5)
        char_w = 1.95  # approx mm per char at 10.5pt
        wrap_w = bw - 16
        est_lines = sum(
            max(1, int(len(p) * char_w / wrap_w) + 1)
            for p in lines if p.strip()
        )
        h = 10 + est_lines * 6.5 + len([p for p in lines if p == ""]) * 3 + 8

        if y0 + h > self.h - 25:
            self.add_page()
            y0 = self.get_y()

        # Gold left stripe
        self.set_fill_color(*GOLD)
        self.rect(x0, y0, 2, h, "F")
        # Cream bg
        self.set_fill_color(*CREAM)
        self.rect(x0 + 2, y0, bw - 2, h, "F")

        # Title
        self.set_xy(x0 + 8, y0 + 4)
        self.set_font("D", "B", 12)
        self.set_text_color(*TEAL)
        label = f"{icon}  {title}" if icon else title
        self.cell(bw - 10, 8, label, align="L")
        self.ln(9)

        for ln_text in lines:
            if ln_text == "":
                self.ln(3)
                self.set_x(x0 + 8)
                continue
            if ln_text.startswith("**") and ln_text.endswith("**"):
                self.set_x(x0 + 8)
                self.set_font("D", "B", 10.5)
                self.set_text_color(*TEAL)
                self.multi_cell(bw - 16, 6.5, ln_text.strip("*"), align="L")
                self.set_font("D", "", 10.5)
                self.set_text_color(*DARK)
                self.set_x(x0 + 8)
            elif ln_text.startswith("\u2022"):
                self.set_x(x0 + 8)
                self.set_font("D", "B", 12)
                self.set_text_color(*GOLD)
                self.cell(5, 6.5, "\u2022")
                self.set_font("D", "", 10.5)
                self.set_text_color(*DARK)
                self.multi_cell(bw - 22, 6.5, ln_text[1:].strip(), align="J")
                self.set_x(x0 + 8)
            else:
                self.set_x(x0 + 8)
                self.set_font("D", "", 10.5)
                self.set_text_color(*DARK)
                self.multi_cell(bw - 16, 6.5, ln_text, align="J")
                self.set_x(x0 + 8)

        self.set_xy(LM, y0 + h + 4)


# ---------------------------------------------------------------------------
# Page builders
# ---------------------------------------------------------------------------

def page_cover(pdf: Doc):
    pdf.add_page()

    # Top gold rule
    pdf.set_draw_color(*GOLD)
    pdf.set_line_width(2.5)
    pdf.line(LM, 16, pdf.w - RM, 16)

    # Teal hero block
    pdf.set_fill_color(*TEAL)
    pdf.rect(0, 26, pdf.w, 118, "F")

    # Gold accent band
    pdf.set_fill_color(*GOLD)
    pdf.rect(0, 142, pdf.w, 4, "F")

    # Cream lower section
    pdf.set_fill_color(*CREAM)
    pdf.rect(0, 146, pdf.w, pdf.h - 146, "F")

    # Title
    pdf.set_xy(LM, 36)
    pdf.set_font("D", "B", 30)
    pdf.set_text_color(*WHITE)
    pdf.multi_cell(pdf.w - LM - RM, 15,
                   "Comment casser\nun automatisme\nen 24h", align="C")

    # Subtitle
    pdf.ln(3)
    pdf.set_x(LM)
    pdf.set_font("D", "I", 11.5)
    pdf.set_text_color(*GOLD)
    pdf.multi_cell(pdf.w - LM - RM, 7.5,
                   "La m\u00e9thode pour interrompre n\u2019importe quel sch\u00e9ma r\u00e9p\u00e9titif"
                   " \u2014 d\u00e8s aujourd\u2019hui",
                   align="C")

    # Dots
    cx = pdf.w / 2
    for i in range(5):
        pdf.set_fill_color(*GOLD)
        pdf.ellipse(cx - 20 + i * 10, 128, 3, 3, "F")

    # Author section (cream)
    pdf.set_xy(LM, 156)
    pdf.set_font("D", "B", 16)
    pdf.set_text_color(*TEAL)
    pdf.cell(0, 9, "Roland Crettaz", align="C")
    pdf.ln(10)
    pdf.set_x(LM)
    pdf.set_font("D", "", 10.5)
    pdf.set_text_color(70, 80, 90)
    pdf.cell(0, 7, "1\u202f900 km \u00b7 St-Maurice \u2192 Santiago de Compostela", align="C")
    pdf.ln(9)

    # Short gold dash
    pdf.set_draw_color(*GOLD)
    pdf.set_line_width(0.7)
    pdf.line(pdf.w / 2 - 28, pdf.get_y(), pdf.w / 2 + 28, pdf.get_y())
    pdf.ln(8)

    pdf.set_x(LM)
    pdf.set_font("D", "B", 13)
    pdf.set_text_color(*TEAL)
    pdf.cell(0, 7, "NEURO-OD\u00c9SS\u00c9E", align="C")
    pdf.ln(8)
    pdf.set_x(LM)
    pdf.set_font("D", "", 10)
    pdf.set_text_color(110, 120, 130)
    pdf.cell(0, 6, "neuro-odyssee.com", align="C")

    # Bottom gold rule
    pdf.set_draw_color(*GOLD)
    pdf.set_line_width(2.5)
    pdf.line(LM, pdf.h - 16, pdf.w - RM, pdf.h - 16)


def page_intro(pdf: Doc):
    pdf.add_page()
    pdf.chapter("Introduction")

    pdf.set_font("D", "B", 22)
    pdf.set_text_color(*TEAL)
    pdf.cell(0, 12, "Introduction", align="L")
    pdf.ln(2)
    pdf.gold_hr(1.4, 0, 8)

    pdf.body(
        "Chaque comportement qui te semble aujourd\u2019hui \u00ab\u00a0automatique\u00a0\u00bb "
        "\u00e9tait autrefois un choix conscient. Une d\u00e9cision que tu as prise "
        "\u2014 une fois, deux fois, cent fois \u2014 jusqu\u2019\u00e0 ce que ton cerveau "
        "d\u00e9cide de l\u2019automatiser pour \u00e9conomiser de l\u2019\u00e9nergie."
    )
    pdf.body(
        "C\u2019est \u00e7a, un automatisme\u00a0: un raccourci neurologique. Pas un d\u00e9faut "
        "de caract\u00e8re. Pas un manque de volont\u00e9. Juste ton cerveau qui fait son "
        "travail en optimisant les circuits les plus utilis\u00e9s."
    )
    pdf.body(
        "La bonne nouvelle\u00a0? Si ton cerveau peut c\u00e2bler un comportement, "
        "il peut le rec\u00e2bler. La plasticit\u00e9 c\u00e9r\u00e9brale n\u2019est pas "
        "un concept r\u00e9serv\u00e9 \u00e0 la r\u00e9habilitation apr\u00e8s un AVC. "
        "C\u2019est une r\u00e9alit\u00e9 quotidienne. Chaque exp\u00e9rience nouvelle, "
        "chaque d\u00e9cision diff\u00e9rente, chaque habitude consciente remodelle "
        "litt\u00e9ralement la structure de tes connexions neuronales."
    )

    pdf.quote(
        "Ce que le feu a construit, le feu peut le d\u00e9faire.",
        "principe de neuroplasticit\u00e9"
    )

    pdf.body(
        "Ce guide te donne la m\u00e9thode exacte pour casser UN automatisme dans les "
        "prochaines 24\u00a0heures. Pas une promesse vague. Pas un programme de 30\u00a0jours "
        "dont tu abandonneras \u00e0 J+3. Un protocole concret, heure par heure, bas\u00e9 "
        "sur le fonctionnement r\u00e9el des circuits neuronaux."
    )

    pdf.section("Ce que j\u2019ai appris sur le terrain")

    pdf.body(
        "Je m\u2019appelle Roland Crettaz. En 2024, j\u2019ai march\u00e9 1\u202f900\u00a0km "
        "de Saint-Maurice, en Suisse, jusqu\u2019\u00e0 Santiago de Compostela \u2014 seul, "
        "avec un sac \u00e0 dos. Ce n\u2019\u00e9tait pas seulement un d\u00e9fi physique. "
        "C\u2019\u00e9tait un laboratoire grandeur nature pour tester ce que je savais sur "
        "le cerveau humain."
    )
    pdf.body(
        "Avant ce voyage, j\u2019avais d\u00e9j\u00e0 utilis\u00e9 ces m\u00eames principes "
        "pour me lib\u00e9rer de l\u2019alcool, puis des m\u00e9dicaments, puis du tabac. "
        "Trois automatismes profond\u00e9ment ancr\u00e9s. Trois boucles comportementales "
        "que j\u2019avais construites sur des ann\u00e9es \u2014 et que j\u2019ai d\u00e9mont\u00e9es "
        "en appliquant exactement ce que tu vas lire ici."
    )
    pdf.body(
        "Ce n\u2019est pas de la magie. Ce n\u2019est pas de la motivation. C\u2019est de la "
        "m\u00e9thode. Et la m\u00e9thode, \u00e7a s\u2019apprend."
    )

    pdf.section("Comment utiliser ce guide")

    pdf.body(
        "Lis les deux premiers chapitres pour comprendre le m\u00e9canisme \u2014 c\u2019est "
        "essentiel, parce que comprendre pourquoi quelque chose fonctionne multiplie par deux "
        "ton taux de r\u00e9ussite. Ensuite, le chapitre\u00a03 est ton plan d\u2019action\u00a0: "
        "tu l\u2019appliques aujourd\u2019hui, maintenant, sur UN automatisme pr\u00e9cis que "
        "tu veux briser."
    )
    pdf.body(
        "Un seul. Pas trois. Pas \u00ab\u00a0arr\u00eater de procrastiner en g\u00e9n\u00e9ral\u00a0\u00bb. "
        "Un comportement sp\u00e9cifique, observable, avec un d\u00e9clencheur identifiable. "
        "Tu verras \u2014 c\u2019est bien plus puissant."
    )
    pdf.italic("\u00bb Pr\u00eat\u00a0? Commence par la boucle. Tout part de l\u00e0. \u00ab")


def page_ch1(pdf: Doc):
    pdf.add_page()
    pdf.chapter("Chapitre 1 \u2014 La boucle comportementale")
    pdf.chapter_title("CHAPITRE 1", "La boucle comportementale")

    pdf.body(
        "En 2012, le journaliste Charles Duhigg publie The Power of Habit. Il y d\u00e9crit "
        "un mod\u00e8le simple en trois temps\u00a0: D\u00e9clencheur \u2192 Routine \u2192 "
        "R\u00e9compense. Ce mod\u00e8le vient des travaux du MIT sur les rats dans des "
        "labyrinthes \u2014 mais il s\u2019applique \u00e0 chaque comportement humain r\u00e9p\u00e9t\u00e9, "
        "sans exception."
    )
    pdf.body(
        "Un d\u00e9clencheur active un signal dans le cerveau. Ce signal d\u00e9clenche une "
        "routine (le comportement automatique). La routine produit une r\u00e9compense "
        "(un soulagement, un plaisir, une stimulation). Et la r\u00e9compense renforce le "
        "signal pour la prochaine fois. Voil\u00e0 la boucle. Voil\u00e0 ton automatisme."
    )

    pdf.section("Le r\u00f4le des ganglions de la base")

    pdf.body(
        "Au c\u0153ur de cette automatisation se trouve une structure c\u00e9r\u00e9brale "
        "ancienne\u00a0: les ganglions de la base (ou noyaux gris centraux). Ces structures "
        "sous-corticales \u2014 pr\u00e9sentes chez tous les mammif\u00e8res \u2014 ont un "
        "r\u00f4le pr\u00e9cis\u00a0: prendre en charge les comportements r\u00e9p\u00e9t\u00e9s "
        "pour lib\u00e9rer le cortex pr\u00e9frontal (si\u00e8ge de la pens\u00e9e consciente) "
        "pour des t\u00e2ches nouvelles."
    )
    pdf.body(
        "Concr\u00e8tement\u00a0: la premi\u00e8re fois que tu as allum\u00e9 une cigarette "
        "apr\u00e8s ton caf\u00e9 du matin, c\u2019est ton cortex pr\u00e9frontal qui "
        "g\u00e9rait \u00e7a \u2014 d\u00e9cision consciente, attention soutenue. La "
        "cinquanti\u00e8me fois, les ganglions de la base avaient pris le relais. Ton "
        "cortex \u00e9tait d\u00e9j\u00e0 pass\u00e9 \u00e0 autre chose. Le geste "
        "\u00e9tait devenu invisible \u00e0 ta conscience."
    )
    pdf.body(
        "Ton cerveau n\u2019est pas cass\u00e9. Il est ultra-efficace. Le probl\u00e8me, "
        "c\u2019est que l\u2019efficacit\u00e9 ne discrimine pas entre les bonnes et les "
        "mauvaises habitudes."
    )

    pdf.section("Les trois types de d\u00e9clencheurs")

    pdf.sub("1. D\u00e9clencheurs \u00e9motionnels")
    pdf.body(
        "Ce sont les plus puissants et les plus insidieux. Le stress, l\u2019ennui, "
        "la solitude, l\u2019anxi\u00e9t\u00e9, la frustration \u2014 chacun peut d\u00e9clencher "
        "sa routine propre. La personne qui mange quand elle s\u2019ennuie. Celle qui "
        "scrolle Instagram quand elle est stress\u00e9e. Celle qui reporte ses t\u00e2ches "
        "quand elle ressent de l\u2019anxi\u00e9t\u00e9 anticipatoire."
    )
    pdf.sub("2. D\u00e9clencheurs environnementaux")
    pdf.body(
        "Un lieu, un horaire, une personne, un objet \u2014 tout \u00e7a peut servir de "
        "d\u00e9clencheur. Le canap\u00e9 du salon qui active le mode Netflix. La machine "
        "\u00e0 caf\u00e9 qui d\u00e9clenche l\u2019envie de cigarette. Le vendredi soir "
        "qui active l\u2019envie d\u2019alcool. Ces d\u00e9clencheurs sont tellement "
        "int\u00e9gr\u00e9s \u00e0 ton environnement qu\u2019ils semblent faire partie du d\u00e9cor."
    )
    pdf.sub("3. D\u00e9clencheurs physiques")
    pdf.body(
        "La fatigue, la faim, la douleur physique \u2014 l\u2019\u00e9tat corporel modifie "
        "directement le seuil d\u2019activation des automatismes. C\u2019est pourquoi les "
        "rechutes arrivent souvent le soir (fatigue d\u00e9cisionnelle) ou quand la "
        "glyc\u00e9mie est basse (milieu d\u2019apr\u00e8s-midi)."
    )

    pdf.section("Pourquoi \u00ab\u00a0arr\u00eate juste\u00a0\u00bb ne fonctionne pas")

    pdf.body(
        "Imagine que tu effaces une destination dans ton GPS. Le GPS te demande de "
        "recalculer. Mais si tu ne rentres pas une nouvelle destination, il reste bloqu\u00e9. "
        "Ton cerveau fonctionne pareil."
    )
    pdf.body(
        "Quand tu supprimes un comportement sans le remplacer, le d\u00e9clencheur continue "
        "de s\u2019activer. Le signal monte. La pression augmente. Mais il n\u2019y a pas "
        "de sortie. R\u00e9sultat\u00a0: une frustration croissante qui finit par craquer "
        "sur la vieille routine \u2014 souvent avec plus d\u2019intensit\u00e9 qu\u2019avant. "
        "C\u2019est le cycle de la rechute."
    )
    pdf.body(
        "Ce n\u2019est pas un manque de volont\u00e9. C\u2019est de la neurobiologie. "
        "La volont\u00e9 est une ressource \u00e9puisable \u2014 les \u00e9tudes le montrent "
        "clairement. S\u2019appuyer dessus pour briser un automatisme, c\u2019est comme "
        "essayer de vider une baignoire avec une cuill\u00e8re pendant que le robinet coule."
    )

    pdf.section("La courbe du craving")

    pdf.body(
        "Voici une information qui peut litt\u00e9ralement tout changer pour toi\u00a0: "
        "les envies ne durent pas ind\u00e9finiment. Elles ont une courbe \u2014 un pic, "
        "puis une descente naturelle."
    )
    pdf.body(
        "Des \u00e9tudes sur les d\u00e9pendances montrent que la plupart des cravings "
        "atteignent leur pic entre 10 et 20\u00a0minutes apr\u00e8s le d\u00e9clencheur, "
        "puis d\u00e9clinent d\u2019eux-m\u00eames \u2014 que tu aies c\u00e9d\u00e9 ou non. "
        "La plupart des gens c\u00e8dent \u00e0 la minute\u00a08, alors qu\u2019\u00e0 la "
        "minute\u00a015, l\u2019envie aurait commenc\u00e9 \u00e0 redescendre."
    )
    pdf.body(
        "Conna\u00eetre \u00e7a ne rend pas la vague moins haute. Mais \u00e7a change ta "
        "relation \u00e0 elle. Au lieu de \u00ab\u00a0comment je r\u00e9siste \u00e0 \u00e7a "
        "pour toujours\u00a0?\u00a0\u00bb tu te poses la question\u00a0: \u00ab\u00a0comment "
        "je tiens encore 7\u00a0minutes\u00a0?\u00a0\u00bb C\u2019est psychologiquement "
        "tr\u00e8s diff\u00e9rent."
    )

    pdf.cream_box(
        "EXERCICE \u2014 Identifie TA boucle",
        [
            "Prends 10 minutes et un stylo. R\u00e9ponds honn\u00eatement \u00e0 ces quatre "
            "questions pour l\u2019automatisme que tu veux briser\u00a0:",
            "",
            "\u2022 D\u00c9CLENCHEUR : Qu\u2019est-ce qui pr\u00e9c\u00e8de toujours ce "
            "comportement\u00a0? Quel lieu, moment, \u00e9motion, personne ou \u00e9tat physique\u00a0?",
            "",
            "\u2022 ROUTINE : D\u00e9cris le comportement en termes pr\u00e9cis et observables. "
            "Pas \u00ab\u00a0je mange trop\u00a0\u00bb mais \u00ab\u00a0je prends un paquet de "
            "biscuits et je le mange devant la t\u00e9l\u00e9\u00a0\u00bb.",
            "",
            "\u2022 R\u00c9COMPENSE APPARENTE : Qu\u2019est-ce que tu obtiens en surface\u00a0? "
            "(plaisir gustatif, soulagement, stimulation...)",
            "",
            "\u2022 R\u00c9COMPENSE R\u00c9ELLE : Qu\u2019est-ce qui change \u00e9motionnellement "
            "apr\u00e8s\u00a0? Quelle tension dispara\u00eet\u00a0? Quel vide se comble\u00a0?",
            "",
            "La r\u00e9compense r\u00e9elle est presque toujours \u00e9motionnelle. "
            "C\u2019est elle que tu dois cibler.",
        ],
        icon="\u270f"
    )

    pdf.section("Exemples concrets de boucles")

    pdf.sub("La cigarette apr\u00e8s le caf\u00e9")
    pdf.body(
        "D\u00e9clencheur\u00a0: le caf\u00e9 + le moment de pause. Routine\u00a0: allumer "
        "une cigarette. R\u00e9compense apparente\u00a0: la nicotine. R\u00e9compense "
        "r\u00e9elle\u00a0: la permission de souffler, de sortir, de s\u2019arr\u00eater. "
        "La nicotine est un vecteur \u2014 la vraie r\u00e9compense, c\u2019est la pause "
        "l\u00e9gitim\u00e9e."
    )
    pdf.sub("Le t\u00e9l\u00e9phone au lit")
    pdf.body(
        "D\u00e9clencheur\u00a0: le silence et l\u2019obscurit\u00e9 (signal de transition). "
        "Routine\u00a0: scroller les r\u00e9seaux sociaux. R\u00e9compense apparente\u00a0: "
        "se tenir inform\u00e9. R\u00e9compense r\u00e9elle\u00a0: stimulation sensorielle "
        "et cognitive pour \u00e9viter de rester seul avec ses pens\u00e9es. Le t\u00e9l\u00e9phone "
        "est un \u00e9vitement d\u00e9guis\u00e9 en information."
    )
    pdf.sub("Le grignotage de 16h")
    pdf.body(
        "D\u00e9clencheur\u00a0: la chute naturelle de la glyc\u00e9mie et la fatigue en "
        "fin d\u2019apr\u00e8s-midi. Routine\u00a0: chercher du sucre ou du gras. "
        "R\u00e9compense apparente\u00a0: combler la faim. R\u00e9compense r\u00e9elle\u00a0: "
        "pic de dopamine rapide pour contrecarrer la baisse d\u2019\u00e9nergie. "
        "Le probl\u00e8me n\u2019est pas nutritionnel \u2014 c\u2019est un besoin de "
        "stimulation dopaminergique."
    )


def page_ch2(pdf: Doc):
    pdf.add_page()
    pdf.chapter("Chapitre 2 \u2014 La technique d\u2019interruption")
    pdf.chapter_title("CHAPITRE 2", "La technique d\u2019interruption")

    pdf.body(
        "Voici la v\u00e9rit\u00e9 que beaucoup ne veulent pas entendre\u00a0: tu ne peux "
        "pas effacer un circuit neuronal. Une fois form\u00e9, il reste l\u00e0. Les "
        "connexions synaptiques ne disparaissent pas comme \u00e7a."
    )
    pdf.body(
        "Mais voici l\u2019autre v\u00e9rit\u00e9 \u2014 celle qui lib\u00e8re\u00a0: "
        "un circuit inutilis\u00e9 s\u2019affaiblit. La plasticit\u00e9 synaptique "
        "fonctionne dans les deux sens. Les connexions qui ne s\u2019activent pas "
        "r\u00e9guli\u00e8rement perdent leur efficacit\u00e9. Et les nouvelles connexions "
        "que tu construis, elles, se renforcent \u00e0 chaque r\u00e9p\u00e9tition."
    )
    pdf.body(
        "C\u2019est le principe du pattern interrupt\u00a0: tu ne supprimes pas la vieille "
        "route \u2014 tu en construis une nouvelle tellement plus emprunt\u00e9e que "
        "l\u2019ancienne devient un chemin de campagne envahi par les herbes."
    )

    pdf.quote(
        "Les neurones qui s\u2019activent ensemble se c\u00e2blent ensemble.",
        "Donald Hebb, neuropsychologue"
    )

    pdf.section("Les 5 \u00e9tapes de la m\u00e9thode")

    pdf.step(
        1,
        "IDENTIFIER \u2014 Sois chirurgical",
        "\u00c9cris l\u2019automatisme que tu veux briser avec une pr\u00e9cision maximale. "
        "Pas \u00ab\u00a0je mange mal\u00a0\u00bb mais \u00ab\u00a0chaque soir vers 22h, "
        "assis sur le canap\u00e9, je mange du chocolat devant ma s\u00e9rie\u00a0\u00bb. "
        "Plus tu es pr\u00e9cis, plus le plan sera efficace. La vagueness est l\u2019ennemie "
        "du changement."
    )
    pdf.step(
        2,
        "D\u00c9CODER LE SIGNAL \u2014 Observe avant d\u2019agir",
        "Avant d\u2019essayer de changer quoi que ce soit, observe trois occurrences du "
        "comportement. Pose-toi ces questions\u00a0: Qu\u2019est-ce qui s\u2019est pass\u00e9 "
        "juste avant\u00a0? Qu\u2019est-ce que je ressentais\u00a0? O\u00f9 \u00e9tais-je\u00a0? "
        "Quelle heure \u00e9tait-il\u00a0? Qui \u00e9tait l\u00e0\u00a0? Souvent, on "
        "d\u00e9couvre des d\u00e9clencheurs qu\u2019on n\u2019avait pas identifi\u00e9s. "
        "C\u2019est de l\u2019intelligence, pas de la procrastination."
    )
    pdf.step(
        3,
        "TROUVER LA VRAIE R\u00c9COMPENSE \u2014 Creuse jusqu\u2019\u00e0 la racine",
        "Test simple\u00a0: imm\u00e9diatement apr\u00e8s avoir r\u00e9alis\u00e9 le comportement, "
        "note comment tu te sens. Pas dans une heure \u2014 l\u00e0, maintenant. Qu\u2019est-ce "
        "qui a chang\u00e9\u00a0? Quelle tension s\u2019est dissip\u00e9e\u00a0? Quel \u00e9tat "
        "\u00e9motionnel a \u00e9t\u00e9 modifi\u00e9\u00a0? Si tu peux nommer la r\u00e9compense "
        "r\u00e9elle, tu peux chercher un substitut qui l\u2019offre aussi."
    )
    pdf.step(
        4,
        "INS\u00c9RER UN SUBSTITUT \u2014 M\u00eame r\u00e9compense, nouvelle route",
        "La r\u00e8gle d\u2019or\u00a0: le substitut doit livrer la m\u00eame cat\u00e9gorie "
        "de r\u00e9compense. Si la r\u00e9compense est \u00ab\u00a0pause du stress\u00a0\u00bb "
        "\u2192 remplace la cigarette par 2\u00a0minutes de marche dehors ou une respiration "
        "profonde. Si la r\u00e9compense est \u00ab\u00a0stimulation\u00a0\u00bb \u2192 "
        "remplace le scroll par un puzzle rapide ou une micro-t\u00e2che engageante. "
        "Si la r\u00e9compense est \u00ab\u00a0connexion sociale\u00a0\u00bb \u2192 remplace "
        "l\u2019alcool par un appel \u00e0 quelqu\u2019un. M\u00eame cat\u00e9gorie. Nouvelle route."
    )
    pdf.step(
        5,
        "ANCRER LE NOUVEAU CIRCUIT \u2014 La r\u00e9p\u00e9tition est tout",
        "D\u00e8s que le d\u00e9clencheur se manifeste, ex\u00e9cute le substitut \u2014 "
        "imm\u00e9diatement, sans n\u00e9gocier. Chaque fois que tu le fais, tu renforces "
        "la nouvelle connexion synaptique. Chaque fois que tu c\u00e8des \u00e0 l\u2019ancienne, "
        "tu la r\u00e9actives. Pendant les premi\u00e8res 24\u00a0heures, ce duel neurologique "
        "se joue presque \u00e0 chaque activation du d\u00e9clencheur. Chaque rep compte."
    )

    pdf.section("La science derri\u00e8re\u00a0: \u00e9lagage synaptique")

    pdf.body(
        "Le cerveau proc\u00e8de r\u00e9guli\u00e8rement \u00e0 ce que les neuroscientifiques "
        "appellent l\u2019\u00e9lagage synaptique (synaptic pruning)\u00a0: il \u00e9limine "
        "les connexions sous-utilis\u00e9es pour optimiser l\u2019efficacit\u00e9 globale "
        "du r\u00e9seau. Ce processus est particuli\u00e8rement actif pendant l\u2019adolescence "
        "\u2014 mais il continue tout au long de la vie."
    )
    pdf.body(
        "Concr\u00e8tement\u00a0: quand tu arr\u00eates de r\u00e9p\u00e9ter un comportement, "
        "les connexions qui le sous-tendent commencent \u00e0 s\u2019affaiblir. Ce n\u2019est "
        "pas imm\u00e9diat \u2014 \u00e7a prend du temps. Mais chaque heure sans r\u00e9activer "
        "l\u2019ancienne routine est une heure o\u00f9 l\u2019\u00e9lagage travaille pour toi."
    )
    pdf.body(
        "Et inversement\u00a0: chaque r\u00e9p\u00e9tition du nouveau comportement renforce "
        "les connexions du nouveau circuit. La potentiation \u00e0 long terme (LTP) \u2014 "
        "le m\u00e9canisme par lequel des synapses s\u2019activant ensemble deviennent "
        "progressivement plus efficaces \u2014 est litt\u00e9ralement en train de sculpter "
        "ton cerveau en temps r\u00e9el."
    )

    pdf.section("La qualit\u00e9 du substitut\u00a0: crit\u00e8res")

    pdf.body("Tous les substituts ne se valent pas. Voici les crit\u00e8res d\u2019un bon substitut\u00a0:")
    pdf.bullet("Accessible imm\u00e9diatement quand le d\u00e9clencheur se manifeste (pas dans 10\u00a0minutes)")
    pdf.bullet("Livrant la m\u00eame cat\u00e9gorie de r\u00e9compense \u00e9motionnelle")
    pdf.bullet("Compatible avec ton contexte r\u00e9el (pas \u00ab\u00a0m\u00e9dite 20\u00a0minutes\u00a0\u00bb si tu es au bureau)")
    pdf.bullet("Neutre ou positif pour ta sant\u00e9 \u00e0 long terme")
    pdf.bullet("Suffisamment satisfaisant pour que la tentation de l\u2019ancien comportement diminue")
    pdf.ln(2)
    pdf.body(
        "Un substitut imparfait mais accessible vaut cent fois mieux qu\u2019un substitut "
        "parfait mais inaccessible. Ne cherche pas l\u2019id\u00e9al \u2014 cherche le praticable."
    )

    pdf.cream_box(
        "LA R\u00c8GLE DES 15 MINUTES",
        [
            "Quand le craving frappe, fais une seule chose\u00a0: r\u00e8gle un minuteur sur 15\u00a0minutes.",
            "",
            "Pendant ces 15\u00a0minutes, fais ton substitut. N\u2019importe lequel. Marche. "
            "Respire. Bois un verre d\u2019eau. Envoie un message. Peu importe \u2014 "
            "l\u2019objectif est de traverser la vague sans c\u00e9der \u00e0 l\u2019ancienne routine.",
            "",
            "\u00c0 la fin des 15\u00a0minutes, r\u00e9\u00e9value. Est-ce que l\u2019envie "
            "est aussi forte\u00a0? Dans 80\u00a0% des cas, elle a significativement baiss\u00e9. "
            "Dans les 20\u00a0% restants, tu as au moins gagn\u00e9 15\u00a0minutes pendant "
            "lesquelles l\u2019\u00e9lagage synaptique travaillait en ta faveur.",
            "",
            "**La vague ne dure jamais aussi longtemps que tu le crois.**",
            "",
            "\u2022 Craving d\u00e9tect\u00e9 \u2192 minuteur 15\u00a0min",
            "\u2022 Substitut imm\u00e9diat pendant 15\u00a0min",
            "\u2022 R\u00e9\u00e9valuation \u00e0 la fin",
            "\u2022 R\u00e9p\u00e9ter si n\u00e9cessaire",
        ],
        icon="\u23f1"
    )

    pdf.body(
        "Cette r\u00e8gle est simple, mais ne la sous-estime pas. C\u2019est l\u2019outil "
        "le plus puissant de ce guide. La raison\u00a0: elle d\u00e9place ton objectif de "
        "\u00ab\u00a0r\u00e9sister pour toujours\u00a0\u00bb (puisant) \u00e0 "
        "\u00ab\u00a0tenir 15\u00a0minutes\u00a0\u00bb (faisable). Et faisable, c\u2019est "
        "ce qui produit des r\u00e9sultats r\u00e9els."
    )


def page_ch3(pdf: Doc):
    pdf.add_page()
    pdf.chapter("Chapitre 3 \u2014 Ton plan d\u2019action 24h")
    pdf.chapter_title("CHAPITRE 3", "Ton plan d\u2019action 24h")

    pdf.body(
        "Assez de th\u00e9orie. Voici le protocole heure par heure. Tu l\u2019appliques "
        "aujourd\u2019hui. Un seul automatisme. Toute la journ\u00e9e. Cette structure "
        "n\u2019est pas arbitraire \u2014 chaque bloc horaire cible une vuln\u00e9rabilit\u00e9 "
        "sp\u00e9cifique de ta journ\u00e9e."
    )

    pdf.section("MATIN \u2014 Au r\u00e9veil (avant m\u00eame de regarder ton t\u00e9l\u00e9phone)")

    pdf.body(
        "C\u2019est le moment le plus important de ta journ\u00e9e. Ton cortex pr\u00e9frontal "
        "est frais, ta volont\u00e9 est au maximum. Profite-en pour installer ton intention."
    )
    pdf.body(
        "Prends une feuille de papier \u2014 oui, du papier, pas ton t\u00e9l\u00e9phone "
        "\u2014 et \u00e9cris cette phrase en la compl\u00e9tant\u00a0:"
    )

    # Intention card
    y0 = pdf.get_y()
    pdf.set_fill_color(*CREAM)
    pdf.set_draw_color(*TEAL)
    pdf.set_line_width(0.5)
    pdf.rect(LM, y0, pdf.w - LM - RM, 28, "FD")
    pdf.set_xy(LM + 5, y0 + 6)
    pdf.set_font("D", "I", 11)
    pdf.set_text_color(*TEAL)
    pdf.multi_cell(
        pdf.w - LM - RM - 10, 8,
        "\u00ab\u00a0Aujourd\u2019hui, quand [TON D\u00c9CLENCHEUR], je fais [NOUVELLE ROUTINE]"
        "\nau lieu de [ANCIENNE ROUTINE].\u00a0\u00bb",
        align="C"
    )
    pdf.set_xy(LM, y0 + 30)
    pdf.ln(4)

    pdf.body(
        "Mets cette feuille l\u00e0 o\u00f9 tu la verras souvent\u00a0: miroir de la salle "
        "de bain, bureau, fond d\u2019\u00e9cran du t\u00e9l\u00e9phone. La r\u00e9p\u00e9tition "
        "visuelle de l\u2019intention amorce les circuits avant m\u00eame que le d\u00e9clencheur "
        "se manifeste."
    )

    pdf.section("MATIN \u2014 8h \u00e0 12h\u00a0: Anticipation active")

    pdf.body("Dans ce bloc, ton travail est l\u2019anticipation. Pose-toi ces questions\u00a0:")
    pdf.bullet("\u00c0 quel moment le d\u00e9clencheur va-t-il probablement se manifester aujourd\u2019hui\u00a0?")
    pdf.bullet("Suis-je dans les conditions qui l\u2019activent habituellement\u00a0?")
    pdf.bullet("Mon substitut est-il pr\u00eat et accessible\u00a0? (Pas \u00ab\u00a0je le ferai quand \u00e7a arrive\u00a0\u00bb \u2014 pr\u00e9pare-le maintenant)")
    pdf.ln(2)
    pdf.body(
        "Si possible, modifie ton environnement pour r\u00e9duire l\u2019exposition au "
        "d\u00e9clencheur. Pas comme strat\u00e9gie permanente \u2014 mais pendant ces "
        "24\u00a0heures, chaque friction que tu ajoutes entre le d\u00e9clencheur et "
        "l\u2019ancien comportement est une aide. Range le paquet de cigarettes hors de "
        "port\u00e9e. D\u00e9sinstalle temporairement l\u2019application. Change ta routine de caf\u00e9."
    )

    pdf.section("MIDI \u2014 Check-in \u00e9motionnel (5\u00a0minutes)")

    pdf.body(
        "\u00c0 mi-journ\u00e9e, prends 5\u00a0minutes pour un check-in honn\u00eate. "
        "Ouvre ton journal (ou une note sur ton t\u00e9l\u00e9phone) et r\u00e9ponds\u00a0:"
    )
    pdf.bullet("Le d\u00e9clencheur s\u2019est-il manifest\u00e9 ce matin\u00a0?")
    pdf.bullet("Qu\u2019est-ce que j\u2019ai fait\u00a0? Le substitut\u00a0? L\u2019ancienne routine\u00a0? Autre chose\u00a0?")
    pdf.bullet("Comment je me sens l\u00e0, maintenant\u00a0?")
    pdf.ln(2)
    pdf.body(
        "Ce n\u2019est pas un examen \u2014 c\u2019est de la collecte de donn\u00e9es. "
        "Si tu as c\u00e9d\u00e9 ce matin, ce n\u2019est pas un \u00e9chec. C\u2019est "
        "une information\u00a0: Qu\u2019est-ce que je n\u2019avais pas anticip\u00e9\u00a0? "
        "Qu\u2019est-ce que je vais ajuster pour l\u2019apr\u00e8s-midi\u00a0?"
    )

    pdf.section("APR\u00c8S-MIDI \u2014 13h \u00e0 17h\u00a0: La zone de danger silencieuse")

    pdf.body(
        "Voici quelque chose que peu de guides mentionnent\u00a0: l\u2019apr\u00e8s-midi "
        "est la p\u00e9riode de plus grande vuln\u00e9rabilit\u00e9 pour la plupart des gens. "
        "Deux raisons biologiques\u00a0:"
    )
    pdf.body(
        "Premi\u00e8rement, la fatigue d\u00e9cisionnelle. Chaque d\u00e9cision que tu prends "
        "dans la journ\u00e9e \u00e9puise une ressource cognitive limit\u00e9e. \u00c0 15h, "
        "tu as d\u00e9j\u00e0 pris des centaines de microd\u00e9cisions. Ton cerveau cherche "
        "\u00e0 \u00e9conomiser de l\u2019\u00e9nergie \u2014 et les automatismes sont son "
        "raccourci favori."
    )
    pdf.body(
        "Deuxi\u00e8mement, le creux glyc\u00e9mique de 15h-16h. La chute naturelle de la "
        "glyc\u00e9mie active les d\u00e9clencheurs physiques et amplifie les d\u00e9clencheurs "
        "\u00e9motionnels. C\u2019est le moment o\u00f9 le grignotage, le scroll, la cigarette "
        "\u2014 tout semble particuli\u00e8rement attrayant."
    )
    pdf.body(
        "Ta strat\u00e9gie pour ce bloc\u00a0: ne compte pas sur ta volont\u00e9. Compte sur "
        "ta pr\u00e9paration. Avoir ton substitut d\u00e9j\u00e0 en place. Avoir mang\u00e9 "
        "un repas qui stabilise ta glyc\u00e9mie. Avoir pr\u00e9vu une courte pause active "
        "(2\u20133\u00a0minutes de marche) vers 15h pour contrecarrer le creux."
    )

    pdf.section("SOIR \u2014 18h \u00e0 22h\u00a0: La fen\u00eatre critique")

    pdf.body(
        "La majorit\u00e9 des rechutes se produisent le soir. C\u2019est quand la fatigue "
        "est maximale, les inhibitions sont basses, et le besoin de d\u00e9comprimer est "
        "le plus fort. C\u2019est aussi quand les environnements d\u00e9clencheurs sont les "
        "plus pr\u00e9sents\u00a0: le canap\u00e9, la cuisine, la routine d\u2019avant-dormir."
    )
    pdf.body(
        "Pour ce bloc, utilise l\u2019engagement social. Dis \u00e0 quelqu\u2019un \u2014 "
        "un ami, un proche, n\u2019importe qui de confiance \u2014 ce que tu es en train "
        "de faire. Pas une longue explication. Juste\u00a0: \u00ab\u00a0Ce soir, j\u2019essaie "
        "de ne pas [ancien comportement]. Je te tiendrai au courant.\u00a0\u00bb"
    )
    pdf.body(
        "L\u2019engagement public active deux m\u00e9canismes c\u00e9r\u00e9braux puissants\u00a0: "
        "les neurones miroirs (qui mod\u00e8lent nos comportements sur les attentes sociales "
        "per\u00e7ues) et la douleur sociale anticip\u00e9e de briser un engagement. Ton "
        "cerveau social est souvent plus fort que ton cerveau rationnel \u2014 autant le "
        "mettre de ton c\u00f4t\u00e9."
    )

    pdf.section("NUIT \u2014 Avant de dormir\u00a0: Debriefing et ajustements")

    pdf.body(
        "Avant de fermer les yeux, prends 5\u00a0minutes pour \u00e9crire\u00a0:"
    )
    pdf.bullet("Ce qui a fonctionn\u00e9 aujourd\u2019hui")
    pdf.bullet("Ce qui a \u00e9t\u00e9 difficile")
    pdf.bullet("Un d\u00e9clencheur que je n\u2019avais pas anticip\u00e9")
    pdf.bullet("Ce que j\u2019ajuste demain")
    pdf.ln(2)
    pdf.body(
        "Ce rituel d\u2019\u00e9criture nocturne sert deux fonctions. D\u2019abord, il "
        "ancre les apprentissages de la journ\u00e9e dans ta m\u00e9moire \u00e0 long "
        "terme (la consolidation m\u00e9morielle se produit principalement pendant le "
        "sommeil \u2014 \u00e9crire juste avant optimise ce processus). Ensuite, il "
        "transforme les difficult\u00e9s en donn\u00e9es exploitables plut\u00f4t "
        "qu\u2019en \u00ab\u00a0preuves\u00a0\u00bb que \u00e7a ne marche pas."
    )

    pdf.section("Si tu rechutes pendant les 24h")

    pdf.body(
        "Une chose importante\u00a0: NE R\u00c9INITIALISE PAS LE COMPTEUR. Cette id\u00e9e "
        "du \u00ab\u00a0tout ou rien\u00a0\u00bb est l\u2019une des principales raisons "
        "d\u2019\u00e9chec dans le changement comportemental. Un \u00e9cart n\u2019efface "
        "pas les progr\u00e8s pr\u00e9c\u00e9dents."
    )
    pdf.body(
        "La rechute est une donn\u00e9e, pas une condamnation. Elle te dit\u00a0: il y "
        "avait un d\u00e9clencheur que je n\u2019avais pas identifi\u00e9, ou mon substitut "
        "n\u2019\u00e9tait pas accessible, ou mon niveau de fatigue \u00e9tait plus \u00e9lev\u00e9 "
        "que pr\u00e9vu. Analyse. Ajuste. Continue."
    )

    pdf.quote(
        "Un \u00e9cart dans une bonne direction reste un progr\u00e8s. Ce qui compte, "
        "c\u2019est la trajectoire, pas la perfection.",
        "Roland Crettaz"
    )

    pdf.cream_box(
        "TON TEMPLATE PLAN 24h",
        [
            "**L\u2019automatisme que je veux briser\u00a0:**",
            ".....................................................................................",
            "",
            "**Mon d\u00e9clencheur principal\u00a0:**",
            ".....................................................................................",
            "",
            "**La r\u00e9compense r\u00e9elle que je cherche\u00a0:**",
            ".....................................................................................",
            "",
            "**Mon substitut choisi\u00a0:**",
            ".....................................................................................",
            "",
            "**Mon intention du matin\u00a0:**",
            "Quand ................, je fais ................ au lieu de ................",
            "",
            "**La personne \u00e0 qui je vais le dire ce soir\u00a0:**",
            ".....................................................................................",
            "",
            "**Mon heure de check-in midi\u00a0:** \u00a0\u00a0\u00a0\u00a0......h......",
            "**Mon heure de debriefing soir\u00a0:** \u00a0\u00a0\u00a0......h......",
        ],
        icon="\U0001f4cb"
    )


def page_conclusion(pdf: Doc):
    pdf.add_page()
    pdf.chapter("Conclusion")
    pdf.chapter_title("", "Pour conclure")

    pdf.body("Si tu ne devais retenir que trois choses de ce guide, ce serait celles-ci\u00a0:")
    pdf.ln(2)

    takeaways = [
        (
            "Un automatisme est un circuit, pas un destin.",
            "Tu ne choisis pas d\u2019avoir des automatismes \u2014 ton cerveau les forme "
            "pour \u00eatre efficace. Mais tu peux choisir de les rec\u00e2bler, un "
            "d\u00e9clencheur \u00e0 la fois, une r\u00e9p\u00e9tition \u00e0 la fois."
        ),
        (
            "Le substitut est la cl\u00e9, pas la suppression.",
            "Essayer d\u2019\u00e9liminer un comportement sans le remplacer, c\u2019est "
            "cr\u00e9er un vide que le cerveau cherchera \u00e0 combler \u2014 avec "
            "l\u2019ancien comportement ou un autre aussi probl\u00e9matique. Donne \u00e0 "
            "ton cerveau une nouvelle route, pas juste un mur."
        ),
        (
            "24\u00a0heures suffisent pour amorcer le changement.",
            "Tu n\u2019as pas besoin de 21\u00a0jours pour commencer. La neuroplasticit\u00e9 "
            "s\u2019active \u00e0 chaque r\u00e9p\u00e9tition. Chaque fois que tu choisis "
            "la nouvelle route, tu construis quelque chose de r\u00e9el dans la structure "
            "de ton cerveau. Commence maintenant."
        ),
    ]

    for i, (title, body) in enumerate(takeaways, 1):
        y0 = pdf.get_y()
        pdf.set_fill_color(*GOLD)
        pdf.rect(LM, y0, 1.5, 24, "F")
        pdf.set_xy(LM + 7, y0 + 2)
        pdf.set_font("D", "B", 11)
        pdf.set_text_color(*TEAL)
        pdf.cell(0, 7, f"{i}. {title}")
        pdf.ln(8)
        pdf.set_x(LM + 7)
        pdf.set_font("D", "", 10.5)
        pdf.set_text_color(*DARK)
        pdf.multi_cell(pdf.usable_w() - 8, 7, body, align="J")
        pdf.ln(5)

    pdf.ln(2)
    pdf.gold_hr()

    pdf.set_font("D", "B", 17)
    pdf.set_text_color(*TEAL)
    pdf.cell(0, 10, "\u00ab Tu n\u2019as pas besoin de motivation.", align="C")
    pdf.ln(11)
    pdf.set_font("D", "B", 17)
    pdf.set_text_color(*GOLD)
    pdf.cell(0, 10, "Tu as besoin d\u2019un plan. \u00bb", align="C")
    pdf.ln(13)
    pdf.gold_hr()

    pdf.section("La suite\u00a0: Le protocole reset mental 7\u00a0jours")

    pdf.body(
        "Si ce guide t\u2019a \u00e9t\u00e9 utile, le Pack Or de Neuro-Odyss\u00e9e inclut "
        "le guide complet \u00ab\u00a0Le protocole reset mental 7\u00a0jours\u00a0\u00bb\u00a0: "
        "une approche structur\u00e9e pour rec\u00e2bler plusieurs automatismes en simultan\u00e9, "
        "avec des exercices quotidiens, un journal guid\u00e9, et les protocoles avanc\u00e9s "
        "de gestion des d\u00e9clencheurs \u00e9motionnels profonds."
    )
    pdf.body(
        "Ce guide a \u00e9t\u00e9 con\u00e7u pour les personnes qui veulent aller plus loin "
        "que la surface \u2014 qui comprennent que le vrai changement se passe dans les "
        "circuits, pas dans les r\u00e9solutions."
    )
    pdf.body("Retrouve tous les guides et ressources sur neuro-odyssee.com")

    # Divider
    pdf.ln(2)
    pdf.set_fill_color(*TEAL)
    pdf.rect(LM - 3, pdf.get_y(), pdf.w - LM - RM + 6, 1.5, "F")
    pdf.ln(7)

    pdf.set_font("D", "B", 13)
    pdf.set_text_color(*TEAL)
    pdf.cell(0, 8, "\u00c0 propos de Roland Crettaz", align="L")
    pdf.ln(10)

    pdf.body(
        "Roland Crettaz est l\u2019auteur de Neuro-Odyss\u00e9e, un projet qui documente "
        "le lien entre l\u2019effort physique extr\u00eame et la transformation neurologique. "
        "En 2024, il a march\u00e9 1\u202f900\u00a0km de Saint-Maurice, en Suisse, jusqu\u2019\u00e0 "
        "Santiago de Compostela \u2014 seul, sans aide ext\u00e9rieure, en utilisant le "
        "chemin comme laboratoire pour tester les limites de la plasticit\u00e9 c\u00e9r\u00e9brale humaine."
    )
    pdf.body(
        "Avant ce voyage, il a travers\u00e9 et surmont\u00e9 des d\u00e9pendances \u00e0 "
        "l\u2019alcool, aux m\u00e9dicaments et au tabac \u2014 en appliquant les m\u00eames "
        "principes neuroscientifiques qu\u2019il partage aujourd\u2019hui dans ses guides. "
        "Son approche est r\u00e9solument pratique\u00a0: pas de th\u00e9orie sans application, "
        "pas de changement sans m\u00e9thode."
    )

    # Soutenir box
    pdf.ln(3)
    y0 = pdf.get_y()
    pdf.set_fill_color(*CREAM)
    pdf.set_draw_color(*GOLD)
    pdf.set_line_width(1)
    pdf.rect(LM - 3, y0, pdf.w - LM - RM + 6, 30, "FD")
    pdf.set_xy(LM + 3, y0 + 6)
    pdf.set_font("D", "B", 12)
    pdf.set_text_color(*TEAL)
    pdf.cell(0, 7, "Soutiens le projet Neuro-Odyss\u00e9e", align="C")
    pdf.ln(9)
    pdf.set_x(LM + 3)
    pdf.set_font("D", "", 10)
    pdf.set_text_color(*DARK)
    pdf.cell(0, 6, "Ce projet vit gr\u00e2ce \u00e0 la communaut\u00e9. "
                   "Retrouve tous les guides sur\u00a0:", align="C")
    pdf.ln(8)
    pdf.set_x(LM + 3)
    pdf.set_font("D", "B", 13)
    pdf.set_text_color(*TEAL)
    pdf.cell(0, 6, "neuro-odyssee.com", align="C")


# ---------------------------------------------------------------------------
# Main
# ---------------------------------------------------------------------------

def main():
    os.makedirs(os.path.dirname(OUTPUT), exist_ok=True)
    print("Building PDF...")
    pdf = Doc()
    page_cover(pdf)
    page_intro(pdf)
    page_ch1(pdf)
    page_ch2(pdf)
    page_ch3(pdf)
    page_conclusion(pdf)
    pdf.output(OUTPUT)
    size_kb = os.path.getsize(OUTPUT) / 1024
    print(f"Done. {pdf.page} pages — {size_kb:.0f} KB")
    print(f"Output: {OUTPUT}")


if __name__ == "__main__":
    main()
