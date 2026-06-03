"""
Generate: reprendre-le-controle-de-son-cerveau.pdf
Author: Roland Crettaz — Neuro-Odyssée
Target: 40-50 pages, 220€ PREMIUM tier
"""

import os
from fpdf import FPDF
from fpdf.enums import XPos, YPos

# ---------------------------------------------------------------------------
# Colours
# ---------------------------------------------------------------------------
TEAL       = (30, 107, 94)
GOLD       = (240, 165, 0)
DARK       = (26, 35, 50)
CREAM      = (248, 246, 241)
TEAL_BG    = (232, 244, 240)
WHITE      = (255, 255, 255)
LIGHT_GREY = (200, 200, 200)

# ---------------------------------------------------------------------------
# Font paths
# ---------------------------------------------------------------------------
FONT_DIR  = "/tmp/dejavu-fonts/dejavu-fonts-ttf-2.37/ttf"
FONT_REG  = os.path.join(FONT_DIR, "DejaVuSans.ttf")
FONT_BOLD = os.path.join(FONT_DIR, "DejaVuSans-Bold.ttf")
FONT_ITAL = os.path.join(FONT_DIR, "DejaVuSans-Oblique.ttf")
FONT_BOIT = os.path.join(FONT_DIR, "DejaVuSans-BoldOblique.ttf")

OUTPUT = "/Users/martialnicola/Downloads/neuro-odyssee/pdf/reprendre-le-controle-de-son-cerveau.pdf"

# ---------------------------------------------------------------------------
# PDF class
# ---------------------------------------------------------------------------
class PDF(FPDF):
    def __init__(self):
        super().__init__(orientation="P", unit="mm", format="A4")
        self.set_margins(25, 20, 25)
        self.set_auto_page_break(auto=True, margin=22)
        self.add_font("DJ",  "",   FONT_REG,  uni=True)
        self.add_font("DJ",  "B",  FONT_BOLD, uni=True)
        self.add_font("DJ",  "I",  FONT_ITAL, uni=True)
        self.add_font("DJ",  "BI", FONT_BOIT, uni=True)

    def header(self):
        if self.page <= 1:
            return
        self.set_font("DJ", "I", 8)
        self.set_text_color(*TEAL)
        self.cell(0, 6, "Reprendre le contrôle de son cerveau  —  Roland Crettaz  •  Neuro-Odyssée",
                  align="L", new_x=XPos.LMARGIN, new_y=YPos.NEXT)
        self._gold_rule(0.3)
        self.ln(2)

    def footer(self):
        if self.page <= 1:
            return
        self.set_y(-16)
        self._gold_rule(0.3)
        self.set_font("DJ", "", 9)
        self.set_text_color(*TEAL)
        self.cell(0, 7, str(self.page), align="C")

    # ---- helpers -----------------------------------------------------------

    def _gold_rule(self, lw=0.7):
        self.set_draw_color(*GOLD)
        self.set_line_width(lw)
        self.line(25, self.get_y(), 185, self.get_y())

    def _set_dark(self):
        self.set_text_color(*DARK)

    def h1(self, txt, center=False):
        self.set_font("DJ", "B", 22)
        self.set_text_color(*TEAL)
        self.multi_cell(0, 10, txt, align="C" if center else "L",
                        new_x=XPos.LMARGIN, new_y=YPos.NEXT)
        self.ln(1)

    def h2(self, txt):
        self.set_font("DJ", "B", 16)
        self.set_text_color(*TEAL)
        self.multi_cell(0, 8, txt, new_x=XPos.LMARGIN, new_y=YPos.NEXT)
        self.ln(1)

    def h3(self, txt):
        self.set_font("DJ", "B", 13)
        self.set_text_color(*TEAL)
        self.multi_cell(0, 7, txt, new_x=XPos.LMARGIN, new_y=YPos.NEXT)
        self.ln(1)

    def h4(self, txt):
        self.set_font("DJ", "B", 11)
        self.set_text_color(*DARK)
        self.multi_cell(0, 6, txt, new_x=XPos.LMARGIN, new_y=YPos.NEXT)

    def body(self, txt):
        self.set_font("DJ", "", 11)
        self._set_dark()
        self.multi_cell(0, 7, txt, new_x=XPos.LMARGIN, new_y=YPos.NEXT)
        self.ln(2)

    def italic(self, txt):
        self.set_font("DJ", "I", 11)
        self._set_dark()
        self.multi_cell(0, 7, txt, new_x=XPos.LMARGIN, new_y=YPos.NEXT)
        self.ln(2)

    def bullets(self, items):
        self.set_font("DJ", "", 11)
        self._set_dark()
        for item in items:
            self.set_x(25)
            self.cell(6, 7, "\u2022", new_x=XPos.RIGHT, new_y=YPos.TOP)
            self.multi_cell(0, 7, item, new_x=XPos.LMARGIN, new_y=YPos.NEXT)
        self.ln(2)

    def numbered(self, items):
        self.set_font("DJ", "", 11)
        self._set_dark()
        for i, item in enumerate(items, 1):
            self.set_x(25)
            self.set_font("DJ", "B", 11)
            self.cell(8, 7, f"{i}.", new_x=XPos.RIGHT, new_y=YPos.TOP)
            self.set_font("DJ", "", 11)
            self.multi_cell(0, 7, item, new_x=XPos.LMARGIN, new_y=YPos.NEXT)
        self.ln(2)

    def box(self, title, lines, style="cream"):
        bg     = CREAM    if style == "cream" else TEAL_BG
        accent = GOLD     if style == "cream" else TEAL
        x = 25
        y = self.get_y()
        w = 160
        # estimate height
        est = sum(1 + max(0, len(l) - 2) // 85 for l in lines)
        h   = 14 + est * 6.5 + 8
        if h < 26:
            h = 26
        # background + border
        self.set_fill_color(*bg)
        self.set_draw_color(*accent)
        self.set_line_width(0.6)
        self.rect(x, y, w, h, style="FD")
        # left accent bar
        self.set_fill_color(*accent)
        self.rect(x, y, 3, h, style="F")
        # title
        self.set_xy(x + 7, y + 5)
        self.set_font("DJ", "B", 11)
        self.set_text_color(*accent)
        self.cell(w - 10, 7, title, new_x=XPos.LMARGIN, new_y=YPos.NEXT)
        # content
        self.set_font("DJ", "", 10)
        self._set_dark()
        for line in lines:
            self.set_x(x + 7)
            if line == "":
                self.ln(3)
            elif line.startswith("**") and line.endswith("**"):
                self.set_font("DJ", "B", 10)
                self.multi_cell(w - 14, 6, line[2:-2],
                                new_x=XPos.LMARGIN, new_y=YPos.NEXT)
                self.set_font("DJ", "", 10)
            elif line.startswith("- "):
                self.cell(5, 6, "\u2022")
                self.set_x(x + 13)
                self.multi_cell(w - 19, 6, line[2:],
                                new_x=XPos.LMARGIN, new_y=YPos.NEXT)
            else:
                self.multi_cell(w - 14, 6, line,
                                new_x=XPos.LMARGIN, new_y=YPos.NEXT)
        self.set_y(y + h + 4)
        self._set_dark()

    def part_banner(self, num, title, sub=""):
        self.add_page()
        self.set_fill_color(*TEAL)
        self.rect(0, 0, 210, 50, style="F")
        self.set_xy(25, 10)
        self.set_font("DJ", "B", 10)
        self.set_text_color(*GOLD)
        self.cell(0, 7, f"PARTIE {num}", new_x=XPos.LMARGIN, new_y=YPos.NEXT)
        self.set_x(25)
        self.set_font("DJ", "B", 24)
        self.set_text_color(*WHITE)
        self.multi_cell(0, 12, title, new_x=XPos.LMARGIN, new_y=YPos.NEXT)
        if sub:
            self.set_x(25)
            self.set_font("DJ", "I", 12)
            self.set_text_color(200, 230, 225)
            self.multi_cell(0, 7, sub, new_x=XPos.LMARGIN, new_y=YPos.NEXT)
        self.set_xy(25, 60)

    def week_banner(self, num, title, sub=""):
        self.ln(4)
        self.set_font("DJ", "B", 12)
        self.set_text_color(*GOLD)
        self.cell(0, 7, f"SEMAINE {num}", new_x=XPos.LMARGIN, new_y=YPos.NEXT)
        self.set_font("DJ", "B", 18)
        self.set_text_color(*TEAL)
        self.multi_cell(0, 9, title, new_x=XPos.LMARGIN, new_y=YPos.NEXT)
        if sub:
            self.set_font("DJ", "I", 11)
            self._set_dark()
            self.multi_cell(0, 6, sub, new_x=XPos.LMARGIN, new_y=YPos.NEXT)
        self._gold_rule(0.5)
        self.ln(4)

    def day_bar(self, n, title):
        self.ln(3)
        self.set_fill_color(*TEAL)
        self.set_text_color(*WHITE)
        self.set_font("DJ", "B", 11)
        self.set_x(25)
        self.cell(160, 9, f"  JOUR {n} \u2014 {title}", fill=True,
                  new_x=XPos.LMARGIN, new_y=YPos.NEXT)
        self.ln(2)

    def toc_row(self, title, page, level=0):
        indent = level * 8
        self.set_x(25 + indent)
        w = 160 - indent
        if level == 0:
            self.set_font("DJ", "B", 11)
            self.set_text_color(*TEAL)
        else:
            self.set_font("DJ", "", 10)
            self._set_dark()
        tw = self.get_string_width(title) + 3
        ps = str(page)
        pw = self.get_string_width(ps) + 3
        dw = w - tw - pw
        self.cell(tw, 7, title)
        if dw > 0:
            self.set_font("DJ", "", 9)
            self.set_text_color(*LIGHT_GREY)
            dots = "." * max(1, int(dw / max(0.1, self.get_string_width("."))))
            self.cell(dw, 7, dots, align="C")
        if level == 0:
            self.set_font("DJ", "B", 11)
            self.set_text_color(*GOLD)
        else:
            self.set_font("DJ", "", 10)
            self._set_dark()
        self.cell(pw, 7, ps, align="R", new_x=XPos.LMARGIN, new_y=YPos.NEXT)


# ===========================================================================
# SECTIONS
# ===========================================================================

def cover(pdf: PDF):
    pdf.add_page()
    # gold top stripe
    pdf.set_fill_color(*GOLD)
    pdf.rect(0, 0, 210, 6, style="F")
    pdf.ln(12)
    # EDITION PREMIUM
    pdf.set_font("DJ", "B", 9)
    pdf.set_text_color(*GOLD)
    pdf.cell(0, 6, "ÉDITION PREMIUM", align="C", new_x=XPos.LMARGIN, new_y=YPos.NEXT)
    pdf.ln(6)
    # dark teal hero block
    pdf.set_fill_color(*TEAL)
    pdf.rect(0, 30, 210, 130, style="F")
    pdf.set_xy(25, 42)
    pdf.set_font("DJ", "B", 30)
    pdf.set_text_color(*WHITE)
    pdf.multi_cell(160, 15, "Reprendre le contrôle\nde son cerveau", align="C",
                   new_x=XPos.LMARGIN, new_y=YPos.NEXT)
    # gold separator
    pdf.set_draw_color(*GOLD)
    pdf.set_line_width(1.5)
    gx = pdf.get_y() + 6
    pdf.line(55, gx, 155, gx)
    pdf.set_y(gx + 8)
    pdf.set_x(25)
    pdf.set_font("DJ", "B", 14)
    pdf.set_text_color(*GOLD)
    pdf.cell(160, 8, "LA MÉTHODE COMPLÈTE", align="C",
             new_x=XPos.LMARGIN, new_y=YPos.NEXT)
    pdf.set_x(25)
    pdf.set_font("DJ", "I", 11)
    pdf.set_text_color(200, 230, 225)
    pdf.multi_cell(160, 7,
        "Le système complet en 30 jours pour reprogrammer\n"
        "ton cerveau, tes habitudes et ta vie",
        align="C", new_x=XPos.LMARGIN, new_y=YPos.NEXT)
    pdf.set_xy(25, 170)
    pdf.set_font("DJ", "B", 14)
    pdf.set_text_color(*DARK)
    pdf.cell(0, 8, "Roland Crettaz", align="C",
             new_x=XPos.LMARGIN, new_y=YPos.NEXT)
    pdf.set_font("DJ", "I", 10)
    pdf.set_text_color(80, 100, 90)
    pdf.cell(0, 6, "Neuro-Odyssée  •  neuro-odyssee.com", align="C",
             new_x=XPos.LMARGIN, new_y=YPos.NEXT)
    # bottom gold band
    pdf.set_fill_color(*GOLD)
    pdf.rect(0, 281, 210, 16, style="F")
    pdf.set_xy(25, 284)
    pdf.set_font("DJ", "B", 10)
    pdf.set_text_color(*DARK)
    pdf.cell(0, 8, "NEURO-ODYSSÉE  •  neuro-odyssee.com", align="C")


def toc(pdf: PDF):
    pdf.add_page()
    pdf.h1("Table des matières", center=True)
    pdf._gold_rule()
    pdf.ln(5)
    rows = [
        ("Introduction — Pourquoi ce guide existe", "4", 0),
        ("PARTIE 1 — COMPRENDRE", "7", 0),
        ("Chapitre 1 : Ton cerveau n'est pas ton ennemi", "7", 1),
        ("Chapitre 2 : La chimie de tes comportements", "10", 1),
        ("Chapitre 3 : TDAH, addiction et le cerveau qui compense", "13", 1),
        ("PARTIE 2 — LE SYSTÈME", "15", 0),
        ("Chapitre 4 : Les 4 piliers du changement durable", "15", 1),
        ("Chapitre 5 : Comment reprogrammer un comportement", "18", 1),
        ("Chapitre 6 : L'architecture de la journée optimale", "21", 1),
        ("PARTIE 3 — LE PLAN 30 JOURS", "23", 0),
        ("Semaine 1 : Le nettoyage (Jours 1–7)", "23", 1),
        ("Semaine 2 : La reconstruction (Jours 8–14)", "28", 1),
        ("Semaine 3 : L'approfondissement (Jours 15–21)", "33", 1),
        ("Semaine 4 : L'intégration (Jours 22–30)", "38", 1),
        ("PARTIE 4 — LES OUTILS", "43", 0),
        ("Journal quotidien (template imprimable)", "43", 1),
        ("Évaluation hebdomadaire", "44", 1),
        ("Protocole d'urgence", "45", 1),
        ("Ressources recommandées", "45", 1),
        ("Conclusion & À propos de Roland", "46", 0),
    ]
    for title, page, level in rows:
        pdf.toc_row(title, page, level)
        if level == 0:
            pdf.ln(2)
    pdf.ln(8)
    pdf.set_font("DJ", "I", 10)
    pdf.set_text_color(120, 130, 130)
    pdf.multi_cell(0, 6,
        "Ce guide est conçu pour être lu séquentiellement. Chaque chapitre construit "
        "sur le précédent. Les exercices ne sont pas optionnels — ils sont la méthode.",
        align="C")


def introduction(pdf: PDF):
    pdf.add_page()
    pdf.h1("Introduction")
    pdf.set_font("DJ", "B", 16)
    pdf.set_text_color(*GOLD)
    pdf.cell(0, 8, "Pourquoi ce guide existe",
             new_x=XPos.LMARGIN, new_y=YPos.NEXT)
    pdf._gold_rule(0.5)
    pdf.ln(4)

    pdf.body(
        "Je vais commencer par quelque chose que je n'aurais jamais dit à voix haute "
        "il y a vingt ans : j'ai passé la majeure partie de ma vie adulte à ne pas comprendre "
        "pourquoi je fonctionnais comme je fonctionnais. Pas par manque d'intelligence. "
        "Pas par manque de volonté. Mais parce que personne ne m'avait jamais expliqué "
        "comment mon cerveau était câblé — et surtout, pourquoi il semblait câblé différemment "
        "des autres."
    )
    pdf.body(
        "J'ai grandi avec ce sentiment permanent d'être décalé. Trop dans ma tête, "
        "pas assez dans le présent. Capable de grandes choses par éclairs, puis incapable "
        "de finir quoi que ce soit pendant des semaines. Le TDAH n'a pas été diagnostiqué "
        "avant mes quarante ans. Pendant des décennies, j'ai cru que c'était un défaut "
        "de caractère. Une faiblesse. Quelque chose à cacher."
    )
    pdf.body(
        "Dans ma trentaine, j'ai sombré dans l'alcool. Pas le genre de sombrage spectaculaire "
        "qu'on voit dans les films — le genre discret, fonctionnel, qui te permet de continuer "
        "à aller travailler, à sourire aux réunions, à paraître normal. Pendant deux ans, "
        "j'ai remplacé ça par des médicaments. Puis pendant douze ans, c'est le tabac qui "
        "a joué ce rôle. Un divorce. Des problèmes financiers. Plusieurs fois, j'ai touché "
        "ce que je reconnais maintenant comme le fond."
    )
    pdf.body(
        "Le moment charnière n'a pas été un éclair de lucidité dramatique. C'est arrivé "
        "progressivement, presque silencieusement. Je me suis posé une question différente. "
        "Plus « comment est-ce que je tiens ? » — mais « pourquoi est-ce que ça se passe "
        "comme ça ? ». Cette nuance a tout changé. Passer de la survie à la compréhension."
    )
    pdf.body(
        "Je me suis formé en neurosciences et en nutrition. Pas pour avoir un diplôme — "
        "pour comprendre. Et ce que j'ai découvert m'a littéralement changé. La neuroplasticité "
        "n'est pas une métaphore : chaque pensée répétée, chaque comportement maintenu, "
        "chaque émotion cultivée modifie physiquement les connexions neuronales. Le cerveau "
        "est un organe vivant qui se remodèle tout au long de la vie. Ce n'est pas de la "
        "pensée positive. C'est de la biologie."
    )
    pdf.body(
        "Ce guide, c'est tout ce que je sais condensé en trente jours. C'est le guide "
        "que j'aurais voulu avoir à trente ans — celui qui m'aurait évité une décennie "
        "entière de souffrance inutile. Pas la souffrance qui fait grandir. L'autre. "
        "Celle qui tourne en rond."
    )

    pdf.h3("À qui s'adresse ce guide ?")
    pdf.bullets([
        "Tu sais exactement ce que tu devrais faire, mais tu ne le fais pas — et tu ne comprends pas pourquoi.",
        "Tu as essayé la force de volonté. Plusieurs fois. Ça marche quelques semaines, puis tout s'effondre.",
        "Tu as l'impression d'être gouverné par tes habitudes plutôt que de les gouverner.",
        "Tu es épuisé par tes propres schémas répétitifs — dans les relations, le travail, la santé.",
        "Tu sens qu'il y a une version meilleure de toi quelque part, mais tu ne sais pas comment l'atteindre durablement.",
        "Tu as vécu une dépendance — à une substance, un comportement, une émotion — et tu veux comprendre le pourquoi, pas juste « tenir ».",
    ])
    pdf.body(
        "Ce guide n'est pas pour ceux qui cherchent des astuces rapides. Il n'y en a pas. "
        "Il est pour ceux qui sont prêts à comprendre en profondeur, à faire le travail, "
        "et à transformer durablement leur façon de fonctionner."
    )

    pdf.h3("Comment utiliser ce guide")
    pdf.numbered([
        "Lis chaque chapitre en entier avant de passer au suivant.",
        "Fais les exercices. Ils ne sont pas optionnels — ils sont la méthode. La lecture seule ne reprogramme rien.",
        "Utilise le journal quotidien fourni en Partie 4. L'écriture externalise les schémas et les rend visibles.",
        "Suis le plan 30 jours dans l'ordre. Ne saute pas de jours. Ne rattrape pas plusieurs jours à la fois.",
        "Quand tu glisses — et tu glisseras — utilise le protocole de rechute en Partie 4. Ne l'improvise pas dans l'urgence.",
    ])

    pdf.italic(
        "« Ce n'est pas une question de motivation. C'est une question de système. "
        "La motivation est une émotion — elle fluctue. Le système tient même quand "
        "la motivation est absente. »  — Roland Crettaz"
    )


# ---------------------------------------------------------------------------
# PARTIE 1 — COMPRENDRE
# ---------------------------------------------------------------------------

def part1(pdf: PDF):
    pdf.part_banner("1", "COMPRENDRE",
                    "Les fondations neuroscientifiques du changement")

    # ---- Chapitre 1 --------------------------------------------------------
    pdf.h2("Chapitre 1 — Ton cerveau n'est pas ton ennemi")
    pdf._gold_rule(0.4)
    pdf.ln(3)

    pdf.body(
        "La première chose à comprendre — et c'est peut-être la plus importante de ce guide — "
        "c'est que ton cerveau ne cherche pas à te saboter. Il fait exactement ce pour quoi "
        "il a été optimisé : te maintenir en vie, préserver l'énergie, et répéter ce qui "
        "a fonctionné dans le passé. Le problème, c'est que « fonctionné dans le passé » "
        "inclut des comportements qui ne te servent plus aujourd'hui."
    )

    pdf.h3("Le modèle triunique : trois cerveaux en un")
    pdf.body(
        "Paul MacLean a proposé dans les années 1960 un modèle simplifié mais "
        "pédagogiquement utile : le cerveau triunique. Trois couches, trois systèmes, "
        "trois logiques différentes qui coexistent dans le même crâne."
    )
    pdf.bullets([
        "Le cerveau reptilien (tronc cérébral + cervelet) : survie pure. Respiration, rythme cardiaque, réflexes. Il ne raisonne pas — il réagit. C'est lui qui active la réponse fight-or-flight en quelques millisecondes.",
        "Le système limbique (amygdale, hippocampe, hypothalamus) : émotions, mémoire émotionnelle, attachement. Il mémorise que « cette situation = danger » ou « cette situation = plaisir ». Il parle le langage des sensations, pas des mots.",
        "Le néocortex (cortex préfrontal en particulier) : raisonnement, planification, contrôle des impulsions, langage. C'est lui qui lit ces lignes et décide de changer.",
    ])
    pdf.body(
        "Ces trois systèmes coexistent mais ne communiquent pas de façon équitable. "
        "En situation de stress intense, le reptilien et le limbique prennent le dessus. "
        "Le cortex préfrontal — ton centre de contrôle rationnel — se met en veille. "
        "C'est littéralement pourquoi tu « sais » ce qu'il faudrait faire mais ne le fais pas "
        "quand tu es sous pression, épuisé, ou en proie à une forte envie."
    )

    pdf.h3("Le Default Mode Network : le pilote automatique")
    pdf.body(
        "Quand tu ne fais « rien » — quand tu regardes par la fenêtre, que tu conduis sur "
        "une route familière, que tu te douches — ton cerveau n'est pas au repos. "
        "Il est en mode Default Mode Network (DMN), un réseau de régions interconnectées "
        "qui s'active précisément quand tu n'es pas focalisé sur une tâche externe."
    )
    pdf.body(
        "Le DMN représente environ 50 % de ton temps de veille. C'est là que vivent "
        "la rumination, l'inquiétude, les scénarios catastrophes, et les comportements "
        "automatiques. Si tu te retrouves à manger sans faim, à scroller sans but, "
        "ou à rejouer une conversation stressante en boucle — c'est ton DMN. "
        "Reprendre le contrôle de son cerveau, c'est en grande partie apprendre à "
        "sortir du DMN quand il ne te sert pas."
    )

    pdf.h3("La neuroplasticité : ton cerveau se remodèle")
    pdf.body(
        "Donald Hebb a formulé en 1949 ce qui est devenu l'un des principes fondamentaux "
        "des neurosciences : « Les neurones qui s'activent ensemble se connectent ensemble. » "
        "Chaque fois que tu répètes une pensée, un comportement ou une réaction émotionnelle, "
        "tu renforces les connexions synaptiques correspondantes. Les chemins les plus utilisés "
        "deviennent des autoroutes neuronales. C'est la définition biologique d'une habitude."
    )
    pdf.body(
        "La bonne nouvelle : le processus fonctionne dans les deux sens. Tu peux créer "
        "de nouvelles connexions et laisser s'atrophier les anciennes. À tout âge. "
        "La neuroplasticité ne disparaît jamais — elle diminue avec l'âge, certes, "
        "mais ton cerveau à 40, 50 ou 60 ans est toujours capable de se reconfigurer "
        "structurellement."
    )

    pdf.box(
        "Concepts clés — Chapitre 1",
        [
            "**Cerveau triunique** : reptilien (survie), limbique (émotions), néocortex (raison).",
            "- En stress, le reptilien/limbique prime — le raisonnement se coupe.",
            "**Default Mode Network** : actif ~50 % du temps de veille.",
            "- Source de rumination, comportements automatiques, addictions comportementales.",
            "**Neuroplasticité** : les connexions se renforcent par la répétition.",
            "- Hebb (1949) : « Neurons that fire together, wire together. »",
            "- Applicable à tout âge. Le changement est structurel, pas métaphorique.",
        ],
        style="cream",
    )

    # ---- Chapitre 2 --------------------------------------------------------
    pdf.add_page()
    pdf.h2("Chapitre 2 — La chimie de tes comportements")
    pdf._gold_rule(0.4)
    pdf.ln(3)

    pdf.body(
        "Pour comprendre pourquoi tu fais ce que tu fais, tu dois comprendre les molécules "
        "qui orchestrent tes états intérieurs. Ce n'est pas du réductionnisme — c'est de "
        "l'honnêteté. Tes émotions, tes envies, tes résistances ont une biochimie précise. "
        "Comprendre les mécanismes, c'est commencer à ne plus en être la victime."
    )

    pdf.h3("La dopamine : la molécule de l'anticipation")
    pdf.body(
        "La dopamine est souvent décrite comme la « molécule du plaisir ». C'est une "
        "simplification trompeuse. La dopamine est la molécule de la motivation et de "
        "l'anticipation. La distinction est cruciale."
    )
    pdf.body(
        "Les expériences de Wolfram Schultz (années 1990) ont montré que la dopamine "
        "est libérée au moment de l'anticipation d'une récompense, pas à sa réception. "
        "Mieux encore : l'incertitude amplifie la réponse dopaminergique. C'est pour ça "
        "que les jeux d'argent, les réseaux sociaux et les notifications sont si difficiles "
        "à résister — l'algorithme de récompense variable maximise précisément la "
        "libération de dopamine."
    )
    pdf.body(
        "Comprendre ça change tout sur l'addiction. Tu n'es pas accro au plaisir de la "
        "cigarette, du scroll, du verre — tu es accro à l'anticipation. Au signal qui "
        "précède. C'est pourquoi les déclencheurs (cues) restent si puissants, longtemps "
        "après que le comportement a cessé."
    )

    pdf.h3("La sérotonine : le contentement stable")
    pdf.body(
        "Si la dopamine est la carotte qui te fait courir, la sérotonine est la satisfaction "
        "d'être là où tu es. Elle régule l'humeur, l'estime de soi, et le sentiment "
        "d'appartenance sociale. Fait peu connu : 90 % de la sérotonine du corps est "
        "produite dans l'intestin, pas dans le cerveau. Ton microbiome intestinal n'est "
        "pas un détail — c'est une véritable fabrique d'humeur."
    )

    pdf.h3("Le cortisol : le gardien du stress")
    pdf.body(
        "Le cortisol est l'hormone du stress. En situation aiguë, il est salvateur : "
        "il mobilise l'énergie, affûte les sens, prépare l'action. Le problème est "
        "chronique. Un cortisol constamment élevé — comme dans nos vies modernes avec "
        "leurs stimulations permanentes — détériore le cerveau de façon mesurable."
    )
    pdf.body(
        "Bruce McEwen (Rockefeller University) a montré que le stress chronique rétrécit "
        "littéralement l'hippocampe — région centrale de la mémoire et de l'apprentissage. "
        "Il augmente également l'activité de l'amygdale, rendant le cerveau plus réactif, "
        "plus impulsif, plus anxieux. Le stress chronique te rend structurellement moins "
        "capable de prendre de bonnes décisions."
    )

    pdf.h3("GABA, noradrénaline et le reste du cocktail")
    pdf.body(
        "Le GABA est le principal neurotransmetteur inhibiteur : il calme l'activité "
        "neuronale. Un faible taux de GABA produit anxiété, pensées envahissantes, "
        "insomnie, irritabilité. L'alcool simule l'action du GABA — c'est "
        "neurochimiquement pourquoi il « calme » dans un premier temps."
    )
    pdf.body(
        "La noradrénaline est la molécule de la vigilance et de la concentration. "
        "L'exposition au froid et l'exercice intense sont parmi les stimulants les plus "
        "efficaces de la noradrénaline — sans les effets de rebond des stimulants synthétiques."
    )

    pdf.box(
        "Le cocktail chimique de chaque état",
        [
            "**Peur / menace** : cortisol + adrénaline — corps en alerte maximale.",
            "**Joie / accomplissement** : dopamine + sérotonine — motivation + contentement.",
            "**Calme / sécurité** : GABA + sérotonine — système nerveux au repos.",
            "**Focus / concentration** : noradrénaline + dopamine — attention affûtée.",
            "**Lien social** : ocytocine + sérotonine — co-régulation du système nerveux.",
            "**Envie compulsive** : pic de dopamine anticipatoire sans l'ancrage de la sérotonine.",
        ],
        style="teal",
    )

    # ---- Chapitre 3 --------------------------------------------------------
    pdf.add_page()
    pdf.h2("Chapitre 3 — TDAH, addiction et le cerveau qui compense")
    pdf._gold_rule(0.4)
    pdf.ln(3)

    pdf.body(
        "Je n'aborde pas le TDAH pour que tu te poses un diagnostic. Je l'aborde parce "
        "que le mécanisme sous-jacent — un cerveau sous-dopaminé qui cherche activement "
        "la stimulation — est bien plus répandu qu'on ne le croit. Et parce que comprendre "
        "ce mécanisme explique une grande partie des comportements que les gens désignent "
        "comme de la faiblesse ou du manque de discipline."
    )

    pdf.h3("Le TDAH et le déficit dopaminergique")
    pdf.body(
        "Le cerveau TDAH produit et régule la dopamine différemment du cerveau neurotypique. "
        "Résultat concret : un niveau de base de stimulation insuffisant. Ce cerveau n'est "
        "pas paresseux — il est sous-stimulé. Pour fonctionner, il cherche activement "
        "les sources de dopamine rapide : danger, nouveauté, crise, conflit, sucre, écran, substance."
    )
    pdf.body(
        "C'est pour ça que le cerveau TDAH peut rester concentré des heures sur quelque "
        "chose qui le passionne (hyperfocus), mais s'avère incapable de soutenir l'attention "
        "sur une tâche ennuyeuse même importante. Ce n'est pas un choix. C'est de la neurochimie."
    )

    pdf.h3("La comorbidité TDAH-addiction : 30 à 40 %")
    pdf.body(
        "Les études montrent de façon consistante que les personnes TDAH ont deux à quatre "
        "fois plus de risque de développer une dépendance que la population générale. "
        "La comorbidité n'est pas une coïncidence — c'est une conséquence logique. "
        "Un cerveau chroniquement sous-stimulé découvre qu'une substance ou un comportement "
        "compense ce déficit. Il s'adapte. Il s'y attache."
    )
    pdf.body(
        "Mon alcool était ma médication de fortune. Ma cigarette aussi. Pas parce que "
        "j'étais « faible » — mais parce que mon cerveau avait trouvé quelque chose qui "
        "résolvait temporairement un problème réel. Cette compréhension ne retire pas "
        "la responsabilité, mais elle transforme la honte en curiosité. Et la curiosité "
        "est un bien meilleur point de départ que la culpabilité."
    )

    pdf.h3("Le cycle de la compensation")
    pdf.numbered([
        "Sous-stimulation : ennui, vide, difficulté à démarrer, sentiment de « trop peu ».",
        "Recherche de dopamine : comportement impulsif, substance, écran, conflit — tout ce qui produit un pic rapide.",
        "Pic et soulagement temporaire : ça fonctionne. Le cerveau note : « solution efficace ».",
        "Crash : le niveau de dopamine chute sous la baseline. Tu te sens pire qu'avant.",
        "Besoin augmenté : tolérance. Il en faut plus pour le même effet.",
        "Retour à l'étape 1, amplifié.",
    ])

    pdf.box(
        "Signes que tu pourrais être en train de compenser",
        [
            "- Du mal à démarrer les tâches importantes sans pression externe.",
            "- Tu te retrouves souvent à chercher « quelque chose » sans savoir quoi.",
            "- Tu fonctionnes mieux dans l'urgence et la crise qu'en mode calme.",
            "- Tu sautes d'une chose à l'autre, incapable de finir.",
            "- Tu as recours à des substances ou comportements pour « te mettre en route ».",
            "- Tu ressens vide ou agitation dans les moments calmes.",
            "- Ta vie ressemble à une série de pics et de crashes, jamais un plateau stable.",
        ],
        style="cream",
    )


# ---------------------------------------------------------------------------
# PARTIE 2 — LE SYSTÈME
# ---------------------------------------------------------------------------

def part2(pdf: PDF):
    pdf.part_banner("2", "LE SYSTÈME",
                    "La méthode — comprendre pour agir")

    # ---- Chapitre 4 --------------------------------------------------------
    pdf.h2("Chapitre 4 — Les 4 piliers du changement durable")
    pdf._gold_rule(0.4)
    pdf.ln(3)

    pdf.body(
        "Après des années de recherche et d'expérimentation sur moi-même, j'ai identifié "
        "quatre piliers sans lesquels le changement ne tient pas. Pas deux. Pas six. "
        "Quatre — et ils sont tous nécessaires, parce qu'ils s'alimentent mutuellement."
    )

    pdf.h3("Pilier 1 — Nutrition : ton cerveau mange ce que tu manges")
    pdf.body(
        "Le cerveau représente 2 % du poids corporel mais consomme 20 % de l'énergie totale. "
        "C'est l'organe le plus énergivore du corps, d'une sensibilité extraordinaire "
        "à la qualité de son carburant. Ce que les neurosciences nutritionnelles ont établi : "
        "le microbiome intestinal — les milliards de bactéries dans ton intestin — "
        "communique directement avec le cerveau via le nerf vague, l'axe HPA et la production "
        "de neurotransmetteurs. 90 % de la sérotonine est produite dans l'intestin. "
        "Tes choix alimentaires concernent directement ta capacité à réguler tes émotions, "
        "résister aux envies, et prendre de bonnes décisions."
    )

    pdf.h3("Pilier 2 — Mouvement : l'antidépresseur le plus efficace qui existe")
    pdf.body(
        "L'exercice physique est l'intervention la mieux documentée pour améliorer la santé "
        "mentale. Une méta-analyse publiée en 2023 dans le British Journal of Sports Medicine "
        "place l'exercice au-dessus des médicaments et de la psychothérapie pour les "
        "dépressions légères à modérées. Mécanismes : le BDNF (Brain-Derived Neurotrophic "
        "Factor, souvent appelé le « Miracle-Gro du cerveau »), les endorphines, la "
        "normalisation du cortisol, la stimulation de la noradrénaline, l'amélioration "
        "de la sensibilité à l'insuline."
    )

    pdf.h3("Pilier 3 — Sommeil : le nettoyage neuronal obligatoire")
    pdf.body(
        "Le système glymphatique — découvert en 2013 par Maiken Nedergaard — est le système "
        "de drainage du cerveau. Il s'active principalement pendant le sommeil profond, "
        "éliminant les déchets métaboliques accumulés dans la journée. Un manque de sommeil "
        "chronique n'est pas juste de la fatigue — c'est une dégradation mesurable de la "
        "prise de décision, de la régulation émotionnelle, de la mémoire et de la résistance "
        "aux envies. Matthew Walker résume : « Il n'existe aucun organe ni processus "
        "physiologique qui ne soit amélioré par le sommeil ou détérioré par sa privation. »"
    )

    pdf.h3("Pilier 4 — Connexion sociale : l'isolement tue")
    pdf.body(
        "Julianne Holt-Lunstad (Brigham Young University) a mené une méta-analyse sur "
        "plus de 300 000 participants : l'isolement social augmente le risque de mortalité "
        "prématurée de 29 %, soit l'équivalent de fumer 15 cigarettes par jour. "
        "Nous sommes câblés pour nous réguler en présence les uns des autres. "
        "L'isolement n'est pas neutre — c'est un état de stress chronique."
    )

    pdf.box(
        "Auto-évaluation des 4 piliers — Note-toi de 1 à 10",
        [
            "**NUTRITION** (1 = ultra-transformé quotidien, 10 = cuisine maison, légumes, fermentés)",
            "  Ta note : ___ / 10",
            "**MOUVEMENT** (1 = sédentaire, 10 = exercice quotidien modéré à intense)",
            "  Ta note : ___ / 10",
            "**SOMMEIL** (1 = moins de 6h irrégulier, 10 = 7–9h horaires fixes réveil naturel)",
            "  Ta note : ___ / 10",
            "**CONNEXION** (1 = isolé, peu de contacts réels, 10 = relations profondes régulières)",
            "  Ta note : ___ / 10",
            "Ton score le plus bas est ton premier levier. C'est là que commencer.",
        ],
        style="cream",
    )

    # ---- Chapitre 5 --------------------------------------------------------
    pdf.add_page()
    pdf.h2("Chapitre 5 — Comment reprogrammer un comportement")
    pdf._gold_rule(0.4)
    pdf.ln(3)

    pdf.body(
        "Charles Duhigg et James Clear ont popularisé la structure de la boucle habitude. "
        "Ce que je propose ici va plus loin : un protocole complet de recâblage, basé sur "
        "la neuroplasticité et sur ce qui a réellement fonctionné — sur moi, et avec "
        "les gens que j'accompagne."
    )

    pdf.h3("Le protocole de recâblage complet")
    pdf.numbered([
        "CARTOGRAPHIER — Passe une semaine à observer tes comportements automatiques sans les juger. Pas seulement les « mauvais ». Tous. À quelle heure ? Dans quel contexte ? Précédé de quoi ? Suivi de quoi ? Tu ne peux pas changer ce que tu n'as pas rendu visible.",
        "PRIORISER — Choisis UN comportement à modifier. Un seul. Le cerveau ne peut pas recâbler plusieurs chemins simultanément de façon efficace. Tenter tout changer à la fois garantit l'échec de tout.",
        "DÉCONSTRUIRE — Identifie la boucle complète : Déclencheur (cue) → Envie (craving) → Routine → Récompense. Le déclencheur peut être une heure, un lieu, une émotion, une personne, un état physique. La récompense est souvent différente de ce qu'on croit.",
        "SUBSTITUER — Conçois un comportement de remplacement qui délivre la même catégorie de récompense. Si la cigarette = pause + socialisation, le remplacement doit offrir pause + socialisation, pas juste une alternative nicotinique. La substitution qui échoue est celle qui ignore le besoin réel.",
        "RÉPÉTER — Minimum 21 jours de pratique cohérente pour établir le chemin. Mais la vraie consolidation prend 66 jours en moyenne (Phillippa Lally, UCL). Le mythe des 21 jours t'a peut-être déjà fait abandonner trop tôt.",
        "CONSOLIDER — Récompense-toi délibérément et immédiatement après les répétitions. Le cerveau a besoin du signal dopaminergique pour marquer le nouveau chemin comme « valant la peine d'être conservé ».",
    ])

    pdf.box(
        "La science du 21 vs 66 jours — arrêtons le mythe",
        [
            "Le chiffre de 21 jours vient d'une observation du Dr Maxwell Maltz (1960) "
            "sur ses patients en chirurgie plastique — le temps minimal pour s'habituer à "
            "un changement d'image corporelle. Ce n'était pas une étude sur les habitudes.",
            "",
            "Phillippa Lally et son équipe (UCL, 2010) ont suivi 96 participants pendant "
            "12 semaines. Résultat : la formation d'une habitude prend en moyenne 66 jours. "
            "La plage va de 18 à 254 jours selon la complexité et la personne.",
            "",
            "Ce qui compte plus que le nombre de jours : la cohérence. Les jours manqués "
            "isolés n'impactent pas significativement la consolidation. Ce qui détruit le "
            "processus, c'est l'abandon — pas le glissement.",
        ],
        style="teal",
    )

    # ---- Chapitre 6 --------------------------------------------------------
    pdf.add_page()
    pdf.h2("Chapitre 6 — L'architecture de la journée optimale")
    pdf._gold_rule(0.4)
    pdf.ln(3)

    pdf.body(
        "La façon dont tu structures tes journées n'est pas une question d'organisation "
        "ou de productivité. C'est une question de neurochimie. Les 90 premières minutes "
        "après le réveil programment le ton de la journée entière. Les 90 dernières "
        "minutes avant le sommeil en déterminent la qualité."
    )

    pdf.h3("Le protocole du matin : les 90 premières minutes")
    pdf.body(
        "Ton cortisol atteint naturellement son pic dans les 30 minutes suivant le réveil "
        "(Cortisol Awakening Response). C'est ton état d'alerte naturel maximal. "
        "La plupart des gens le sabotent immédiatement avec une dose de stress (emails, "
        "actualités) ou de dopamine artificielle (téléphone). Protège cette fenêtre."
    )
    pdf.bullets([
        "Lumière naturelle dans les 10 premières minutes — synchronise l'horloge circadienne.",
        "Eau avant café — la déshydratation nocturne amplifie le cortisol négativement.",
        "Mouvement — même 10 minutes de marche suffisent à déclencher le BDNF.",
        "Pas d'écran pendant les 45 premières minutes. Pas de news. Pas de réseaux.",
        "Une intention simple pour la journée — pas une liste de 20 tâches.",
    ])

    pdf.h3("Les cycles ultradiens : travailler avec ta biologie")
    pdf.body(
        "Nathaniel Kleitman (le découvreur du sommeil REM) a identifié les cycles "
        "ultradiens : des oscillations de 90 minutes dans le niveau d'alerte cognitive "
        "qui se répètent tout au long de la journée. À la fin de chaque cycle, le corps "
        "envoie des signaux de transition : bâillement, distraction, difficulté de "
        "concentration. La méthode optimale : blocs de travail focalisé de 90 minutes, "
        "suivis de 15 à 20 minutes de récupération non stimulante."
    )

    pdf.h3("Le protocole du soir : les 90 dernières minutes")
    pdf.bullets([
        "Diminution de la lumière (et surtout lumière bleue) dès 21h — la mélatonine est photosensible.",
        "Température fraîche dans la chambre : 18–19°C optimal pour le sommeil profond.",
        "Revue de la journée — 5 minutes d'écriture : une réussite, une leçon, une intention.",
        "Pas d'écrans 60 minutes avant le coucher si possible, 30 minutes minimum.",
        "Heure de coucher fixe — la cohérence est plus importante que la durée.",
    ])

    pdf.box(
        "Template — Journée type",
        [
            "**6h00** Réveil, lumière naturelle, eau, 10 min mouvement",
            "**6h30** Routine personnelle (lecture, journaling, méditation)",
            "**7h30** Petit-déjeuner, préparation",
            "**9h00–10h30** Bloc de travail 1 — tâche prioritaire",
            "**10h30–11h00** Récupération ultradian (marche, étirements, silence)",
            "**11h00–12h30** Bloc de travail 2",
            "**12h30–14h00** Déjeuner + repos (sieste 20 min acceptable)",
            "**14h00–15h30** Tâches légères, emails, appels",
            "**16h00–17h30** Bloc de travail 3 (créatif ou relationnel)",
            "**17h30–21h00** Famille, cuisine, loisirs",
            "**21h00–22h30** Wind-down — lumière basse, revue, pas d'écran",
            "**22h30** Coucher fixe",
        ],
        style="cream",
    )


# ---------------------------------------------------------------------------
# PARTIE 3 — LE PLAN 30 JOURS
# ---------------------------------------------------------------------------

def part3(pdf: PDF):
    pdf.part_banner("3", "LE PLAN 30 JOURS",
                    "L'implémentation — jour après jour")

    # ===== SEMAINE 1 =====
    pdf.week_banner("1", "Le nettoyage",
                    "Jours 1 à 7 — Préparer le terrain avant de construire")

    pdf.body(
        "On ne reconstruit pas sur des fondations instables. La première semaine n'est "
        "pas spectaculaire — elle est essentielle. Tu vas éliminer les interférences, "
        "créer de l'espace, et établir les routines de base sur lesquelles tout le "
        "reste va s'appuyer."
    )

    pdf.day_bar(1, "Grand nettoyage")
    pdf.bullets([
        "Matin — Brain dump complet : prends une feuille blanche et écris tout ce qui occupe ton esprit. Projets, inquiétudes, tâches, rancœurs, rêves. Sans filtre. L'objectif est d'externaliser pour créer de la clarté intérieure.",
        "Nutrition — Élimine le sucre ajouté aujourd'hui. Sucre dans le café, sodas, snacks industriels. Pas « réduire » — éliminer pour cette semaine. Le cerveau a besoin de stabiliser sa glycémie pour fonctionner clairement.",
        "Écrans — Audit : compte le nombre d'heures passées sur les écrans hier. Note les 3 applications les plus utilisées. Pas pour juger — pour avoir des données.",
        "Soir — Prompt journal : « Quels comportements automatiques ai-je observés aujourd'hui ? »",
    ])

    pdf.day_bar(2, "Jeûne dopaminergique")
    pdf.bullets([
        "Réseaux sociaux : zéro aujourd'hui. Pas « moins » — aucun. Observe ce que tu ressens. L'inconfort est de l'information.",
        "Stimulation minimale : pas de podcasts pendant les déplacements, pas de téléphone aux repas.",
        "Mouvement : marche de 20–30 minutes, sans casque. Laisse ton esprit vagabonder.",
        "Nutrition : protéines au petit-déjeuner (œufs, yaourt grec, noix). Les protéines stabilisent la glycémie et fournissent les précurseurs des neurotransmetteurs.",
        "Soir — Journal : « Qu'est-ce que j'ai cherché à éviter aujourd'hui ? »",
    ])

    pdf.day_bar(3, "Le froid et la respiration")
    pdf.bullets([
        "Cold exposure : termine ta douche par 30 secondes d'eau froide. L'effet noradrénaline est dose-dépendant. 30 secondes suffisent pour commencer.",
        "Cohérence cardiaque : 5 minutes, 3 fois dans la journée. Inspire 5 secondes, expire 5 secondes. Cette pratique module directement le système nerveux parasympathique.",
        "Soir — Journal : note comment tu te sens après le froid. Beaucoup rapportent un état d'alerte calme — c'est la noradrénaline.",
    ])

    pdf.day_bar(4, "Traitement émotionnel")
    pdf.bullets([
        "Exercice — Lettre non envoyée : écris une lettre à une personne envers qui tu portes une rancœur, de la culpabilité, ou une conversation non terminée. Tu ne l'enverras pas. L'objectif est neurologique — externaliser les émotions non traitées libère de la bande passante cognitive.",
        "Supplémentation : commence le magnésium bisglycinate ce soir (200–400 mg avant de dormir). Cofacteur de plus de 300 réactions enzymatiques, dont la synthèse de GABA et de mélatonine.",
        "Soir — Journal : « Quelle émotion j'évite le plus souvent ? Comment je l'évite ? »",
    ])

    pdf.day_bar(5, "Architecture des routines")
    pdf.bullets([
        "Matin : conçois ta routine matinale idéale de 30 minutes. Pas parfaite — faisable. Avec le temps que tu as réellement. Écris-la étape par étape.",
        "Soir : même exercice pour la routine du soir. Quelles sont les 5 choses que tu veux faire chaque soir avant de dormir ?",
        "Première journée avec les deux routines. Elles ne seront pas parfaites. C'est normal.",
    ])

    pdf.day_bar(6, "Audit social")
    pdf.bullets([
        "Réflexion : liste les 5 personnes avec qui tu passes le plus de temps. Pour chacune, note si cette relation t'élève ou te draine.",
        "Action : initie une conversation profonde avec une personne de ton cercle. Pas un échange de nouvelles — une vraie conversation.",
        "Frontière : identifie une situation qui te prive d'énergie de façon systématique. Commence à formuler comment tu veux y répondre différemment.",
    ])

    pdf.day_bar(7, "Intégration et bilan")
    pdf.bullets([
        "Revue : relis tes journaux des 6 premiers jours. Quels patterns as-tu observés ?",
        "Ajustement : qu'est-ce qui a fonctionné ? Qu'est-ce qui n'a pas fonctionné ? Modifie sans culpabilité.",
        "Prépare la semaine 2 : relis le programme des jours 8–14.",
    ])

    pdf.box(
        "Bilan Semaine 1 — À compléter le Jour 7",
        [
            "Pilier Nutrition — ce que j'ai changé : ________________________________",
            "Pilier Mouvement — ce que j'ai pratiqué : ________________________________",
            "Pilier Sommeil — qualité moyenne (1-10) : ________________________________",
            "Pilier Connexion — interaction la plus significative : ________________________________",
            "Comportement automatique le plus visible cette semaine : ________________________________",
            "Ce qui m'a le plus surpris : ________________________________",
            "Intention principale pour la Semaine 2 : ________________________________",
        ],
        style="cream",
    )

    # ===== SEMAINE 2 =====
    pdf.add_page()
    pdf.week_banner("2", "La reconstruction",
                    "Jours 8 à 14 — Poser les nouvelles fondations")

    pdf.body(
        "Le terrain est nettoyé. Maintenant on construit. Cette semaine introduit "
        "les habitudes qui vont former le socle de ton nouveau système. "
        "Principe directeur : habit stacking — ancrer les nouveaux comportements "
        "à des comportements existants pour réduire la friction au démarrage."
    )

    pdf.day_bar(8, "Habit stacking")
    pdf.body(
        "Formule : « Après [habitude existante], je fais [nouvelle habitude]. » "
        "Le cerveau utilise les connexions neuronales existantes comme ancrage — "
        "ce qui réduit considérablement la résistance."
    )
    pdf.bullets([
        "Identifie 3 habitudes solides que tu as déjà (se lever, café du matin, déjeuner).",
        "Attache une nouvelle petite habitude à chacune.",
        "Exemples : après le café, 5 min de journaling. Après le déjeuner, 10 min de marche. Avant de dormir, 5 min de lecture.",
    ])

    pdf.day_bar(9, "Routine d'exercice")
    pdf.bullets([
        "Choisis une forme d'exercice que tu peux faire tous les jours sans planification complexe.",
        "Associe-la à un moment fixe de la journée.",
        "Règle une alarme. Pas « quand j'aurai le temps » — un horaire fixe.",
        "15 minutes de marche rapide suffisent pour les effets BDNF. Ce n'est pas l'intensité qui compte cette semaine — c'est la régularité.",
    ])

    pdf.day_bar(10, "Optimisation du sommeil")
    pdf.bullets([
        "Température : règle la chambre à 18–19°C.",
        "Lumière : installe f.lux ou Night Shift. Ou mieux : pose le téléphone à 21h.",
        "Heure fixe : choisis une heure de coucher et respecte-la 5 jours consécutifs.",
        "Journaling pré-sommeil : 3 gratitudes + intention de demain. La gratitude active le réseau de la récompense et facilite la transition vers le sommeil.",
    ])

    pdf.day_bar(11, "Introduction à la méditation")
    pdf.body(
        "Pas de méditation complexe. Juste ceci : 5 minutes de pleine conscience simple. "
        "Assis, yeux fermés, attention sur la respiration. Quand l'esprit part, tu le ramènes. "
        "Ce retour, c'est le mouvement — l'équivalent d'une répétition au gym pour ton cortex préfrontal."
    )
    pdf.bullets([
        "Application recommandée : Petit Bambou (français) ou Insight Timer (gratuit).",
        "5 minutes ce matin. Construis jusqu'à 15 minutes d'ici la fin de la semaine 3.",
    ])

    pdf.day_bar(12, "Nutrition intestinale")
    pdf.bullets([
        "Introduis un aliment fermenté : kéfir, yaourt nature, choucroute, kimchi, kombucha.",
        "Objectif fibres : 25–30g aujourd'hui. Légumes, légumineuses, fruits entiers.",
        "Élimine les huiles végétales transformées (tournesol raffiné) et remplace par l'huile d'olive.",
    ])

    pdf.day_bar(13, "Le jour sans plainte")
    pdf.body(
        "Défi neurologique : zéro plainte pendant 24 heures. Pas de critique verbale, "
        "pas de commentaire négatif sur les autres, pas de lamentation. "
        "C'est un entraînement actif à recâbler le biais de négativité — cette tendance "
        "évolutive à pondérer les informations négatives 3 à 5 fois plus que les positives."
    )
    pdf.bullets([
        "Si tu te surprends à te plaindre, note-le sans te juger et recommence.",
        "Soir — Journal : compte combien de fois tu as « rattrapé » une plainte.",
    ])

    pdf.day_bar(14, "Connexions et frontières")
    pdf.bullets([
        "Fixe 2 frontières cette semaine. Une frontière n'est pas une confrontation — c'est une décision. « Je ne réponds pas aux messages après 21h. »",
        "Initie 2 connexions significatives : un appel, un café, une conversation en profondeur.",
        "Bilan semaine 2 : complète l'encadré ci-dessous.",
    ])

    pdf.box(
        "Bilan Semaine 2 — À compléter le Jour 14",
        [
            "Habitude stackée avec succès : ________________________________",
            "Routine d'exercice mise en place (O/N) : ________________________________",
            "Qualité de sommeil cette semaine (1-10) : ________________________________",
            "Expérience du jour sans plainte : ________________________________",
            "Frontière posée : ________________________________",
            "Ce que j'ai remarqué sur mon énergie : ________________________________",
            "Ce que je veux amplifier en Semaine 3 : ________________________________",
        ],
        style="cream",
    )

    # ===== SEMAINE 3 =====
    pdf.add_page()
    pdf.week_banner("3", "L'approfondissement",
                    "Jours 15 à 21 — Traverser le plateau")

    pdf.body(
        "Quelque chose d'important se passe autour du jour 14–15 : le plateau. "
        "La nouveauté dopaminergique des premières semaines s'estompe. Les nouvelles "
        "habitudes ne sont pas encore automatiques. L'ancien câblage teste sa résistance. "
        "C'est ici que la majorité des gens abandonnent — pas parce qu'ils ont échoué, "
        "mais parce qu'ils ne comprennent pas ce qui se passe biologiquement. "
        "Comprendre le plateau, c'est le traverser."
    )

    pdf.day_bar(15, "La visualisation")
    pdf.body(
        "Alvaro Pascual-Leone (Harvard) a montré que visualiser des mouvements de piano "
        "active exactement les mêmes régions motrices que jouer réellement. "
        "Les athlètes de haut niveau l'utilisent depuis des décennies — pas pour croire "
        "à leur succès, mais pour précâbler les circuits neuronaux avant la performance."
    )
    pdf.bullets([
        "Matin (10 min) : visualise ta journée en détail. Qui tu vas voir, comment tu vas répondre aux défis prévisibles.",
        "Spécifique : visualise le comportement que tu modifies. Toi, dans le contexte déclencheur, choisissant le nouveau comportement — avec les sensations physiques associées.",
        "Soir — Journal : note la corrélation entre ce que tu as visualisé et ce qui s'est passé.",
    ])

    pdf.day_bar(16, "Micro-habitudes et temptation bundling")
    pdf.body(
        "Le temptation bundling (Katherine Milkman, Wharton) : couple une activité que tu DOIS faire "
        "avec une activité que tu VEUX faire. Exemple : n'écoute ton podcast préféré que pendant "
        "l'exercice. Mange ton déjeuner sain dans ton café préféré. Fais ta méditation dans "
        "ton endroit favori."
    )
    pdf.bullets([
        "Micro-habitudes : réduis encore le seuil de démarrage. Si tu rates ta routine matinale, quelle est la version de 2 minutes que tu peux toujours faire ?",
    ])

    pdf.day_bar(17, "Travail identitaire")
    pdf.body(
        "James Clear distingue trois niveaux de changement : les résultats (ce que tu veux), "
        "les processus (ce que tu fais), et l'identité (ce que tu crois être). "
        "Le changement le plus durable commence par l'identité. "
        "Au lieu de « j'essaie d'arrêter de fumer » : « je suis quelqu'un qui prend soin "
        "de ses poumons ». Chaque comportement aligné avec cette identité devient une preuve."
    )
    pdf.bullets([
        "Exercice : écris 5 affirmations identitaires au présent. « Je suis quelqu'un qui... »",
        "Pour chacune, liste 3 comportements concrets qui en seraient la preuve.",
    ])

    pdf.day_bar(18, "Douleur propre vs douleur sale")
    pdf.body(
        "Steven Hayes (Acceptance and Commitment Therapy) distingue deux types de souffrance."
    )
    pdf.bullets([
        "Douleur propre (clean pain) : l'inconfort inévitable de la croissance. La difficulté de l'exercice. La tristesse d'un deuil. Cette douleur a une direction — elle mène quelque part.",
        "Douleur sale (dirty pain) : la souffrance générée en résistant à la douleur propre. La honte de ne pas avoir fait l'exercice. L'anxiété à propos de l'anxiété. Cette douleur tourne en rond.",
        "Travail du jour : identifier quand tu évites la douleur propre, et pratiquer de la laisser être présente sans y ajouter la couche de résistance.",
    ])

    pdf.day_bar(19, "Approfondissement méditation")
    pdf.bullets([
        "Passe à 15 minutes. Introduis le body scan : parcours systématiquement chaque partie du corps avec l'attention, de la tête aux pieds.",
        "Cold exposure : si tu l'as maintenu, passe à 60 secondes. Observe si l'inconfort a changé de nature.",
    ])

    pdf.day_bar(20, "Le plateau est de l'information")
    pdf.body(
        "Si tu ressens une baisse de motivation ou d'enthousiasme, ce n'est pas un signe "
        "d'échec. C'est la confirmation que tu es au bon endroit. Le plateau est le moment "
        "où le recâblage s'opère en profondeur, silencieusement."
    )
    pdf.bullets([
        "Relis tes notes de la Semaine 1. Mesure le chemin parcouru.",
        "Parle à quelqu'un de confiance de ce que tu es en train de faire. Verbaliser renforce l'engagement.",
        "Ajoute une petite récompense délibérée à la fin de cette journée. Célèbre le plateau.",
    ])

    pdf.day_bar(21, "Bilan mi-parcours")
    pdf.bullets([
        "Refais l'auto-évaluation des 4 piliers. Compare avec le Jour 1.",
        "Relis tes journaux depuis le début. Quels patterns ont évolué ?",
        "Qu'est-ce qui est devenu plus automatique ? Qu'est-ce qui demande encore de l'effort ?",
    ])

    pdf.box(
        "Bilan Semaine 3 — À compléter le Jour 21",
        [
            "Comment j'ai traversé le plateau : ________________________________",
            "Visualisation — ce que j'ai observé : ________________________________",
            "Affirmation identitaire principale : ________________________________",
            "Douleur propre que j'ai appris à traverser : ________________________________",
            "Évolution auto-évaluation 4 piliers vs Jour 1 : ________________________________",
            "Ce qui est devenu automatique : ________________________________",
            "Mon intention pour la semaine finale : ________________________________",
        ],
        style="cream",
    )

    # ===== SEMAINE 4 =====
    pdf.add_page()
    pdf.week_banner("4", "L'intégration",
                    "Jours 22 à 30 — Rendre permanent ce qui fonctionne")

    pdf.body(
        "La quatrième semaine n'est pas la fin. C'est le début du système permanent. "
        "L'objectif : concevoir une version de ce protocole que tu peux maintenir "
        "même quand la vie devient difficile, même quand tu es épuisé, "
        "même quand tout semble s'effondrer."
    )

    pdf.day_bar(22, "Le protocole minimum viable")
    pdf.body(
        "Si tu ne pouvais faire QUE trois choses dans une journée difficile, lesquelles "
        "auraient le plus d'impact sur ton état ? Pour la plupart des gens : mouvement "
        "(même 10 min), sommeil (respecter l'heure de coucher), et une connexion réelle."
    )
    pdf.bullets([
        "Écris ton Protocole Minimum Viable : 3 à 5 comportements non négociables.",
        "Ce sont tes lignes rouges. Les jours où tu fais seulement ceux-là, tu n'as pas échoué. Tout le reste est bonus.",
    ])

    pdf.day_bar(23, "Test de stress")
    pdf.body(
        "Mets-toi délibérément dans un contexte déclencheur — une situation qui, il y a "
        "un mois, aurait activé le comportement que tu modifies. Pas pour te mettre en danger "
        "— pour tester tes nouveaux outils dans des conditions contrôlées."
    )
    pdf.bullets([
        "Avant : visualise ta réponse.",
        "Pendant : observe sans juger. Utilise ta respiration comme ancrage si l'envie monte.",
        "Après : écris ce qui s'est passé. Qu'est-ce qui a fonctionné ?",
    ])

    pdf.day_bar(24, "Le protocole de rechute")
    pdf.body(
        "Si — quand — tu glisses, voici le protocole préparé à froid, "
        "pas l'improvisation émotionnelle de la culpabilité."
    )
    pdf.numbered([
        "STOP : ne rien décider dans l'état émotionnel immédiat de la rechute.",
        "RESPIRE : 5 cycles de cohérence cardiaque (5s inspire, 5s expire).",
        "NOMME : « Je viens de [comportement]. Je ressens [émotion]. Ce n'est pas une catastrophe. »",
        "ANALYSE : qu'est-ce qui a déclenché ce glissement ? Quel besoin n'était pas satisfait ?",
        "REPRENDS : dès la prochaine opportunité, reprends le comportement cible. Pas demain. Maintenant.",
    ])
    pdf.italic(
        "Un glissement ne détruit pas 23 jours de travail. Ce qui détruit le travail, "
        "c'est la spirale de culpabilité qui suit. Le glissement est une information — "
        "la culpabilité est du bruit."
    )

    pdf.day_bar(25, "Consolidation des rituels")
    pdf.bullets([
        "Revue de tous tes rituels actuels : matin, soir, nutrition, mouvement, méditation, connexion.",
        "Lesquels sont devenus naturels ? Lesquels demandent encore de la friction ?",
        "Ajuste les horaires, l'environnement et les déclencheurs pour réduire la friction.",
    ])

    pdf.day_bar(26, "Revue de l'environnement")
    pdf.body(
        "« Tu n'as pas à être la victime de ton environnement. Tu peux aussi en être "
        "l'architecte. »  — James Clear"
    )
    pdf.bullets([
        "Physique : les fruits visibles, les chaussures de sport à la porte, le livre sur la table de chevet.",
        "Numérique : applications supprimées ou cachées, notifications désactivées.",
        "Social : les conversations de la semaine t'ont-elles nourri ou drainé ?",
    ])

    pdf.day_bar(27, "Journée de gratitude active")
    pdf.body(
        "Martin Seligman (Penn) a montré qu'une lettre de gratitude lue à voix haute produit "
        "des effets mesurables sur le bien-être jusqu'à un mois plus tard. La gratitude "
        "stimule la sérotonine et la dopamine, active le cortex préfrontal, diminue "
        "l'activité de l'amygdale."
    )
    pdf.bullets([
        "Écris une lettre de gratitude à quelqu'un qui t'a influencé positivement — et si possible, lis-la lui.",
        "Liste 10 choses qui existent dans ta vie maintenant qui n'existaient pas avant ces 30 jours.",
    ])

    pdf.day_bar(28, "Réévaluation complète")
    pdf.bullets([
        "Refais TOUS les questionnaires du Jour 1 : brain dump, auto-évaluation des 4 piliers, liste des comportements automatiques.",
        "Compare ligne à ligne avec il y a 28 jours.",
        "Écris un paragraphe honnête : ce qui a changé, ce qui n'a pas changé, ce qui doit continuer.",
    ])

    pdf.day_bar(29, "La lettre avant/après")
    pdf.bullets([
        "Lettre 1 — À toi au Jour 1 : qu'est-ce que tu sais maintenant que tu ne savais pas ?",
        "Lettre 2 — À toi dans 90 jours : qui tu aspires à être ? Qu'espérait le « toi du Jour 1 » ?",
    ])

    pdf.day_bar(30, "Les 90 prochains jours")
    pdf.body(
        "Le protocole ne s'arrête pas. Il évolue. Ces 30 jours étaient la phase d'installation. "
        "Les 90 prochains jours sont la phase de consolidation."
    )
    pdf.bullets([
        "Conçois ton plan pour les 90 prochains jours : 3 comportements à consolider, nouveaux comportements à introduire.",
        "Planifie une revue mensuelle dans ton agenda : même date, même format, chaque mois.",
        "Partage ton expérience avec quelqu'un. L'enseignement consolide l'apprentissage mieux que la répétition.",
    ])

    pdf.box(
        "Mon Protocole de Vie — Template final",
        [
            "**MON PROTOCOLE MINIMUM VIABLE (3 non-négociables)**",
            "1. ________________________________",
            "2. ________________________________",
            "3. ________________________________",
            "",
            "**MA ROUTINE MATINALE (30 min)**",
            "________________________________",
            "",
            "**MA ROUTINE DU SOIR (20 min)**",
            "________________________________",
            "",
            "**MES 3 COMPORTEMENTS CIBLES (90 jours)**",
            "1. ________________________________",
            "2. ________________________________",
            "3. ________________________________",
            "",
            "**EN CAS DE GLISSEMENT**",
            "Je fais : ________________________________",
        ],
        style="cream",
    )


# ---------------------------------------------------------------------------
# PARTIE 4 — LES OUTILS
# ---------------------------------------------------------------------------

def part4(pdf: PDF):
    pdf.part_banner("4", "LES OUTILS",
                    "Templates, protocoles et ressources")

    pdf.h2("Journal quotidien — Template imprimable")
    pdf._gold_rule(0.4)
    pdf.ln(3)

    pdf.box(
        "JOURNAL DU JOUR ___  /  DATE : ___________",
        [
            "**MATIN — À compléter au réveil**",
            "Qualité du sommeil (1-10) : ___    Heures dormies : ___",
            "Comment je me sens physiquement : ________________________________",
            "Comment je me sens émotionnellement : ________________________________",
            "Mon intention principale pour aujourd'hui : ________________________________",
            "",
            "**MIDI — Bilan mi-journée**",
            "Ai-je suivi mon protocole ce matin ? (O/N) : ___",
            "Ce qui m'a demandé le plus d'effort : ________________________________",
            "Ce qui a coulé naturellement : ________________________________",
            "",
            "**SOIR — Réflexion du jour**",
            "Un comportement automatique observé : ________________________________",
            "Un moment où j'ai choisi consciemment : ________________________________",
            "Une chose dont je suis fier aujourd'hui : ________________________________",
            "Une leçon ou un apprentissage : ________________________________",
            "Énergie globale (1-10) : ___    Humeur globale (1-10) : ___",
            "Mon intention pour demain : ________________________________",
        ],
        style="cream",
    )

    pdf.add_page()
    pdf.h2("Évaluation hebdomadaire")
    pdf._gold_rule(0.4)
    pdf.ln(3)

    pdf.box(
        "BILAN DE LA SEMAINE ___  /  DU ___ AU ___",
        [
            "**LES 4 PILIERS**",
            "Nutrition (1-10) : ___  Ce que j'ai bien fait : ________________________________",
            "Mouvement (1-10) : ___  Ce que j'ai bien fait : ________________________________",
            "Sommeil (1-10) : ___    Ce que j'ai bien fait : ________________________________",
            "Connexion (1-10) : ___  Ce que j'ai bien fait : ________________________________",
            "",
            "**MON COMPORTEMENT CIBLE**",
            "Jours respectés cette semaine : ___ / 7",
            "Ce qui a aidé : ________________________________",
            "Ce qui a créé de la friction : ________________________________",
            "",
            "**REVUE GÉNÉRALE**",
            "Moment dont je suis le plus fier : ________________________________",
            "Situation difficile et comment je l'ai gérée : ________________________________",
            "Ce que je veux faire différemment la semaine prochaine : ________________________________",
            "Mon intention principale pour la semaine suivante : ________________________________",
        ],
        style="cream",
    )

    pdf.ln(4)
    pdf.h2("Protocole d'urgence")
    pdf._gold_rule(0.4)
    pdf.ln(3)
    pdf.body(
        "Pour les moments où tout s'effondre. Quand tu es en pleine crise émotionnelle, "
        "que tu as glissé, que tu n'arrives pas à penser clairement. "
        "Imprime ce protocole et garde-le accessible."
    )
    pdf.numbered([
        "STOP — Ne prends aucune décision importante maintenant. Tu n'es pas en état de raisonner clairement. Ça passera.",
        "RÉGULE — 5 minutes de cohérence cardiaque : 5s inspiration, 5s expiration. Répète jusqu'à sentir le calme revenir.",
        "NOMME — Dis à voix haute : « Je ressens [émotion]. C'est difficile. Je ne suis pas en danger. » La verbalisation active le cortex préfrontal.",
        "CORPS — Bois un grand verre d'eau. Mange si tu n'as pas mangé. Sors marcher 10 minutes. Le corps d'abord.",
        "CONNEXION — Appelle une personne de confiance. Pas pour trouver une solution — pour ne pas être seul avec la crise.",
    ])

    pdf.ln(2)
    pdf.h2("Ressources recommandées")
    pdf._gold_rule(0.4)
    pdf.ln(3)

    pdf.h4("Livres")
    pdf.bullets([
        "Matthew Walker — Pourquoi nous dormons — La référence absolue sur le sommeil et le cerveau.",
        "James Clear — La méthode des petites habitudes (Atomic Habits) — Structure et psychologie des habitudes.",
        "Robert Sapolsky — Behave — La biologie complète du comportement humain.",
        "Bessel van der Kolk — Le corps n'oublie rien — Trauma, neurologie et guérison.",
        "Viktor Frankl — Découvrir un sens à sa vie — La résilience radicale.",
        "Andrew Huberman — Protocols (Huberman Lab) — Neurosciences appliquées, en anglais.",
    ])

    pdf.h4("Podcasts")
    pdf.bullets([
        "Huberman Lab (Andrew Huberman) — neurosciences appliquées, épisodes de 1–2h, en anglais.",
        "Feel Better Live More (Rangan Chatterjee) — santé intégrative, accessible.",
        "Choses à Savoir Santé — vulgarisation scientifique en français.",
    ])


# ---------------------------------------------------------------------------
# CONCLUSION
# ---------------------------------------------------------------------------

def conclusion(pdf: PDF):
    pdf.add_page()
    pdf.set_fill_color(*TEAL)
    pdf.rect(0, 0, 210, 32, style="F")
    pdf.set_xy(25, 9)
    pdf.set_font("DJ", "B", 20)
    pdf.set_text_color(*WHITE)
    pdf.cell(0, 13, "Conclusion", new_x=XPos.LMARGIN, new_y=YPos.NEXT)

    pdf.set_xy(25, 42)

    pdf.h3("Ce guide, c'est 20 ans de ma vie condensés en 30 jours")
    pdf.body(
        "J'ai commencé à écrire ce guide en me demandant comment transmettre ce que "
        "j'aurais voulu savoir à trente ans. Ce qu'un ami, un frère, quelqu'un qui avait "
        "traversé ce que je traversais, m'aurait dit — pas pour me sauver, mais pour que "
        "je comprenne. Parce que c'est ça le problème avec la souffrance inutile : "
        "elle ne t'apprend rien si tu ne comprends pas pourquoi elle est là."
    )
    pdf.body(
        "J'ai vécu une décennie à « tenir » sans comprendre les mécanismes. "
        "C'était comme conduire dans le noir sans phares — possible un moment, "
        "épuisant, et dangereux. Ce guide, c'est les phares. "
        "Ce n'est pas moi qui conduis à ta place. C'est simplement la lumière sur la route."
    )
    pdf.body(
        "Si j'avais eu ça à 30 ans, j'aurais évité une décennie entière de souffrance. "
        "Mais chaque jour est un nouveau départ. Et tu as commencé aujourd'hui."
    )

    pdf.h3("Le lien avec Compostelle")
    pdf.body(
        "En 2026, j'ai commencé à marcher. De Saint-Maurice, en Valais, "
        "jusqu'à Santiago de Compostela — 1 900 kilomètres. Pas pour fuir. "
        "Pas pour trouver Dieu. Pour incarner physiquement ce que ce guide "
        "décrit intellectuellement : que le changement se fait un pas après l'autre, "
        "jour après jour, jusqu'à ce que la distance parcourue te prouve que tu es capable."
    )
    pdf.body(
        "Il n'y a pas de raccourci sur le Chemin. Tu ne peux pas sauter les jours "
        "de pluie, négocier avec les ampoules ou la fatigue. Tu as juste le prochain pas. "
        "Et le prochain. Et encore le prochain. C'est exactement le processus de ce guide "
        "— dans ta vie, pas sur un chemin de pèlerinage."
    )
    pdf.italic(
        "« Ce n'est pas juste un homme qui marche. C'est un message : rien n'est figé. »"
    )

    pdf.h3("À propos de Roland Crettaz")
    pdf.body(
        "Roland Crettaz est né en Suisse, dans le canton du Valais. Ancien entrepreneur, "
        "diagnostiqué TDAH à 40 ans, en rémission de plusieurs dépendances, formé en "
        "neurosciences comportementales et nutrition fonctionnelle. Il a créé Neuro-Odyssée "
        "pour documenter sa traversée à pied de Saint-Maurice à Santiago de Compostela "
        "et pour partager les outils qui ont transformé son fonctionnement."
    )

    # closing box
    y = pdf.get_y() + 4
    pdf.set_fill_color(*CREAM)
    pdf.set_draw_color(*GOLD)
    pdf.set_line_width(0.6)
    pdf.rect(25, y, 160, 34, style="FD")
    pdf.set_fill_color(*GOLD)
    pdf.rect(25, y, 3, 34, style="F")
    pdf.set_xy(33, y + 5)
    pdf.set_font("DJ", "B", 12)
    pdf.set_text_color(*TEAL)
    pdf.cell(0, 7, "neuro-odyssee.com", new_x=XPos.LMARGIN, new_y=YPos.NEXT)
    pdf.set_x(33)
    pdf.set_font("DJ", "", 10)
    pdf.set_text_color(*DARK)
    pdf.multi_cell(148, 6,
        "En achetant ce guide, tu as financé 10 km de ce voyage. Merci.\n"
        "Partage ton expérience et tes questions — je lis tout.",
        new_x=XPos.LMARGIN, new_y=YPos.NEXT)

    pdf.set_y(y + 44)
    pdf.set_font("DJ", "I", 9)
    pdf.set_text_color(150, 150, 150)
    pdf.multi_cell(0, 5,
        "Ce guide est protégé par le droit d'auteur. Toute reproduction, même partielle, "
        "est interdite sans autorisation écrite de l'auteur.\n"
        "© Roland Crettaz — Neuro-Odyssée — neuro-odyssee.com",
        align="C")


# ===========================================================================
# MAIN
# ===========================================================================

def main():
    pdf = PDF()
    cover(pdf)
    toc(pdf)
    introduction(pdf)
    part1(pdf)
    part2(pdf)
    part3(pdf)
    part4(pdf)
    conclusion(pdf)
    pdf.output(OUTPUT)
    print(f"Generated: {OUTPUT}  ({pdf.page} pages)")


if __name__ == "__main__":
    main()
