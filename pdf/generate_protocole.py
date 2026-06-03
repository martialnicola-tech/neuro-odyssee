"""
Generate: protocole-reset-mental-7-jours.pdf
Author: Roland Crettaz — Neuro-Odyssée
Target: 25-30 pages, 110€ tier premium guide
"""

import os
from fpdf import FPDF

# ---------------------------------------------------------------------------
# Colours
# ---------------------------------------------------------------------------
TEAL   = (30, 107, 94)
GOLD   = (240, 165, 0)
DARK   = (26, 35, 50)
CREAM  = (248, 246, 241)
WHITE  = (255, 255, 255)
LIGHT_GREY = (200, 200, 200)

# ---------------------------------------------------------------------------
# Font paths
# ---------------------------------------------------------------------------
FONT_DIR = "/tmp/dejavu-fonts/dejavu-fonts-ttf-2.37/ttf"
FONT_REG  = os.path.join(FONT_DIR, "DejaVuSans.ttf")
FONT_BOLD = os.path.join(FONT_DIR, "DejaVuSans-Bold.ttf")
FONT_ITAL = os.path.join(FONT_DIR, "DejaVuSans-Oblique.ttf")
FONT_BOIT = os.path.join(FONT_DIR, "DejaVuSans-BoldOblique.ttf")

# ---------------------------------------------------------------------------
# PDF class
# ---------------------------------------------------------------------------
class Protocole(FPDF):
    def __init__(self):
        super().__init__(orientation="P", unit="mm", format="A4")
        self.set_margins(25, 20, 25)
        self.set_auto_page_break(auto=True, margin=20)
        self.add_font("DV",  "", FONT_REG)
        self.add_font("DV",  "B", FONT_BOLD)
        self.add_font("DV",  "I", FONT_ITAL)
        self.add_font("DV",  "BI", FONT_BOIT)
        self._page_num_enabled = False

    # ---- header / footer ---------------------------------------------------
    def header(self):
        pass  # handled per-page manually

    def footer(self):
        if self._page_num_enabled and self.page_no() > 1:
            self.set_y(-15)
            self.set_font("DV", "", 8)
            self.set_text_color(*LIGHT_GREY)
            self.cell(0, 5, f"{self.page_no()}", align="C")
            self.set_text_color(*DARK)

    # ---- helpers -----------------------------------------------------------
    def gold_line(self, y: float = None, w: float = None):
        if y is None:
            y = self.get_y()
        if w is None:
            w = self.w - self.l_margin - self.r_margin
        self.set_draw_color(*GOLD)
        self.set_line_width(0.8)
        self.line(self.l_margin, y, self.l_margin + w, y)
        self.set_line_width(0.2)
        self.set_draw_color(*DARK)

    def teal_rule(self, y: float = None):
        if y is None:
            y = self.get_y()
        self.set_draw_color(*TEAL)
        self.set_line_width(0.4)
        self.line(self.l_margin, y, self.w - self.r_margin, y)
        self.set_line_width(0.2)
        self.set_draw_color(*DARK)

    def h1(self, text: str, color=TEAL):
        self.set_font("DV", "B", 22)
        self.set_text_color(*color)
        self.multi_cell(0, 10, text, align="C")
        self.ln(2)
        self.set_text_color(*DARK)

    def h2(self, text: str, color=TEAL):
        self.ln(3)
        self.set_font("DV", "B", 16)
        self.set_text_color(*color)
        self.multi_cell(0, 8, text)
        self.ln(1)
        self.set_text_color(*DARK)

    def h3(self, text: str, color=TEAL):
        self.ln(4)
        self.set_font("DV", "B", 13)
        self.set_text_color(*color)
        self.multi_cell(0, 7, text)
        self.ln(4)
        self.set_text_color(*DARK)

    def body(self, text: str, indent: float = 0):
        self.set_font("DV", "", 11)
        self.set_text_color(*DARK)
        x0 = self.get_x()
        if indent:
            self.set_x(self.l_margin + indent)
        self.multi_cell(self.w - self.l_margin - self.r_margin - indent, 7, text)
        if indent:
            self.set_x(x0)
        self.ln(1)

    def italic(self, text: str):
        self.set_font("DV", "I", 11)
        self.set_text_color(*DARK)
        self.multi_cell(0, 7, text)
        self.ln(1)

    def bullet(self, text: str, symbol: str = "\u2022"):
        self.set_font("DV", "", 11)
        self.set_text_color(*DARK)
        self.set_x(self.l_margin + 4)
        self.cell(6, 7, symbol)
        self.multi_cell(self.w - self.l_margin - self.r_margin - 10, 7, text)

    def numbered(self, n: int, text: str):
        self.set_font("DV", "", 11)
        self.set_text_color(*DARK)
        self.set_x(self.l_margin + 4)
        self.set_font("DV", "B", 11)
        self.cell(8, 7, f"{n}.")
        self.set_font("DV", "", 11)
        self.multi_cell(self.w - self.l_margin - self.r_margin - 12, 7, text)

    def cream_box(self, title: str, lines: list[str], numbered_list: bool = False):
        """Draw a cream-background box with optional title and content lines."""
        self.ln(3)
        # estimate height: title + lines
        line_h = 7
        padding = 6
        n_lines_total = sum(
            max(1, len(l) // 78 + 1) for l in lines
        ) + (2 if title else 0)
        box_h = n_lines_total * line_h + padding * 2 + (6 if title else 0)

        x = self.l_margin
        y = self.get_y()
        bw = self.w - self.l_margin - self.r_margin

        # check page break
        if y + box_h > self.h - self.b_margin:
            self.add_page()
            y = self.get_y()

        # background
        self.set_fill_color(*CREAM)
        self.set_draw_color(*GOLD)
        self.set_line_width(0.5)
        self.rect(x, y, bw, box_h, style="FD")
        self.set_line_width(0.2)

        # gold left accent bar
        self.set_fill_color(*GOLD)
        self.rect(x, y, 3, box_h, style="F")

        self.set_xy(x + 8, y + padding)

        if title:
            self.set_font("DV", "B", 12)
            self.set_text_color(*TEAL)
            self.cell(bw - 10, 7, title)
            self.ln(8)
            self.set_x(x + 8)

        self.set_text_color(*DARK)
        for i, line in enumerate(lines):
            self.set_x(x + 8)
            if numbered_list:
                self.set_font("DV", "B", 11)
                self.cell(8, line_h, f"{i+1}.")
                self.set_font("DV", "", 11)
                self.multi_cell(bw - 18, line_h, line)
            else:
                if line.startswith("**") and line.endswith("**"):
                    self.set_font("DV", "B", 11)
                    self.multi_cell(bw - 18, line_h, line[2:-2])
                elif line.startswith("_") and line.endswith("_"):
                    self.set_font("DV", "I", 11)
                    self.multi_cell(bw - 18, line_h, line[1:-1])
                elif line == "---":
                    self.ln(2)
                    self.teal_rule()
                    self.ln(2)
                else:
                    self.set_font("DV", "", 11)
                    if line.startswith("\u2022 "):
                        self.cell(5, line_h, "\u2022")
                        self.multi_cell(bw - 23, line_h, line[2:])
                    else:
                        self.multi_cell(bw - 18, line_h, line)

        self.set_xy(x, y + box_h + 3)
        self.set_text_color(*DARK)
        self.set_fill_color(*WHITE)

    def nutrition_tip(self, text: str):
        self.ln(4)
        x = self.l_margin
        y = self.get_y()
        bw = self.w - self.l_margin - self.r_margin
        n_lines = max(2, len(text) // 80 + 2)
        box_h = n_lines * 7 + 14

        if y + box_h > self.h - self.b_margin:
            self.add_page()
            y = self.get_y()

        self.set_fill_color(245, 255, 250)
        self.set_draw_color(*TEAL)
        self.set_line_width(0.4)
        self.rect(x, y, bw, box_h, style="FD")
        self.set_line_width(0.2)

        self.set_xy(x + 5, y + 4)
        self.set_font("DV", "B", 10)
        self.set_text_color(*TEAL)
        self.cell(0, 6, "NUTRITION DU JOUR")
        self.ln(7)
        self.set_x(x + 5)
        self.set_font("DV", "", 11)
        self.set_text_color(*DARK)
        self.multi_cell(bw - 10, 7, text)
        self.set_xy(x, y + box_h + 3)
        self.set_fill_color(*WHITE)
        self.set_draw_color(*DARK)

    def day_header(self, day_num: str, day_title: str, theme: str):
        self.add_page()
        # teal banner
        bw = self.w - self.l_margin - self.r_margin
        self.set_fill_color(*TEAL)
        self.rect(self.l_margin, self.get_y(), bw, 22, style="F")
        self.set_xy(self.l_margin + 5, self.get_y() + 3)
        self.set_font("DV", "B", 14)
        self.set_text_color(*GOLD)
        self.cell(0, 7, day_num.upper())
        self.ln(8)
        self.set_x(self.l_margin + 5)
        self.set_font("DV", "B", 16)
        self.set_text_color(*WHITE)
        self.cell(0, 7, day_title)
        self.ln(10)
        self.set_font("DV", "I", 11)
        self.set_text_color(*GOLD)
        self.cell(0, 6, f"Thème : {theme}")
        self.ln(10)
        self.set_text_color(*DARK)
        self.gold_line()
        self.ln(5)

    def section_label(self, emoji_text: str):
        self.ln(6)
        self.set_font("DV", "B", 11)
        self.set_text_color(*GOLD)
        self.multi_cell(0, 7, emoji_text.upper())
        self.ln(4)
        self.set_text_color(*DARK)


# ===========================================================================
# DOCUMENT ASSEMBLY
# ===========================================================================
def build_pdf(output_path: str):
    pdf = Protocole()
    pdf._page_num_enabled = False

    # =========================================================================
    # PAGE 1 — COVER
    # =========================================================================
    pdf.add_page()
    pdf.gold_line(y=18, w=pdf.w - pdf.l_margin - pdf.r_margin)
    pdf.ln(20)

    pdf.set_font("DV", "B", 28)
    pdf.set_text_color(*TEAL)
    pdf.multi_cell(0, 13, "Le protocole\nreset mental", align="C")
    pdf.ln(4)

    # Big number / dash accent
    pdf.set_font("DV", "B", 60)
    pdf.set_text_color(*GOLD)
    pdf.cell(0, 24, "7 JOURS", align="C")
    pdf.ln(6)

    pdf.set_font("DV", "I", 13)
    pdf.set_text_color(*DARK)
    pdf.multi_cell(
        0, 8,
        "7 jours pour reprendre le contrôle de ton cerveau\n"
        "— le programme complet étape par étape",
        align="C",
    )
    pdf.ln(10)

    pdf.gold_line()
    pdf.ln(10)

    pdf.set_font("DV", "B", 13)
    pdf.set_text_color(*TEAL)
    pdf.cell(0, 8, "Roland Crettaz", align="C")
    pdf.ln(8)

    pdf.set_font("DV", "", 11)
    pdf.set_text_color(*DARK)
    pdf.cell(0, 7, "Accompagnement neuro-comportemental", align="C")
    pdf.ln(18)

    pdf.gold_line()
    pdf.ln(6)

    pdf.set_font("DV", "B", 12)
    pdf.set_text_color(*GOLD)
    pdf.cell(0, 7, "NEURO-ODYSSÉE", align="C")
    pdf.ln(6)
    pdf.set_font("DV", "", 11)
    pdf.set_text_color(*TEAL)
    pdf.cell(0, 6, "neuro-odyssee.com", align="C")
    pdf.ln(4)

    pdf.set_font("DV", "I", 9)
    pdf.set_text_color(*LIGHT_GREY)
    pdf.cell(0, 6, "Guide premium — usage personnel uniquement", align="C")

    # =========================================================================
    # PAGE 2-3 — INTRODUCTION
    # =========================================================================
    pdf._page_num_enabled = True
    pdf.add_page()
    pdf.h1("Introduction")
    pdf.gold_line()
    pdf.ln(6)

    pdf.body(
        "Ce que tu tiens entre les mains, c'est pas un pamphlet de développement personnel. "
        "C'est un protocole structuré sur 7 jours, conçu à partir de la neuroscience, de la nutrition "
        "et de la psychologie comportementale. Chaque journée cible un système neural spécifique. "
        "À la fin du jour 7, tu auras interrompu d'anciens schémas, établi de nouveaux, et "
        "littéralement modifié ta chimie cérébrale."
    )
    pdf.body(
        "Je l'ai conçu en partant de ma propre reconstruction. Après des années à comprendre "
        "comment mon cerveau fonctionnait — et surtout dysfonctionnait — j'ai assemblé ce que "
        "j'ai appris sur le TDAH, la régulation de la dopamine, l'axe intestin-cerveau et la "
        "neuroplasticité. Ce protocole n'est pas théorique : je l'ai vécu."
    )
    pdf.body(
        "Pendant ma traversée de 1 900 km à pied entre Saint-Maurice et Santiago de Compostela, "
        "j'ai eu sept semaines pour observer mon cerveau sans les distractions habituelles. "
        "Ce que j'ai découvert m'a profondément transformé — et ce protocole en est la synthèse "
        "distillée en sept jours."
    )

    pdf.h2("Comment ça fonctionne")
    pdf.body(
        "Chaque journée a une intention claire. On ne fait pas tout en même temps — on cible "
        "un système, on le travaille, on observe. Le cerveau apprend par contraste et par "
        "répétition. Sept jours conscients font plus de changements durables que des mois de "
        "bonnes intentions floues."
    )
    pdf.bullet("Jour 1 : Prise de conscience — tu cartographies ton terrain mental")
    pdf.bullet("Jour 2 : Jeûne informationnel — tu recentres ton système dopaminergique")
    pdf.bullet("Jour 3 : Connexion corps-cerveau — tu actives la neurogénèse par le mouvement")
    pdf.bullet("Jour 4 : Nettoyage émotionnel — tu traites les charges non résolues")
    pdf.bullet("Jour 5 : Reconstruction des routines — tu installes de nouveaux automatismes")
    pdf.bullet("Jour 6 : Reset social — tu réétalonnnes ton environnement humain")
    pdf.bullet("Jour 7 : Intégration — tu consolides et tu planifies la suite")
    pdf.ln(4)

    pdf.h2("Les trois règles absolues")
    pdf.body(
        "Avant de commencer, tu dois comprendre trois choses fondamentales sur le fonctionnement "
        "de ce protocole :"
    )
    pdf.numbered(1, "Respecte l'ordre. Chaque jour prépare le suivant neurochimiquement. Sauter le jour 2 pour aller directement au jour 5, c'est construire sur des fondations instables.")
    pdf.ln(2)
    pdf.numbered(2, "Ne saute aucun jour. Même si tu te sens bien, même si l'exercice te semble inutile. Le cerveau consolide la nuit — chaque journée est une couche.")
    pdf.ln(2)
    pdf.numbered(3, "Fais chaque exercice même si ça te semble 'trop simple'. Les exercices qui paraissent les plus basiques sont souvent ceux qui révèlent le plus.")
    pdf.ln(4)

    pdf.body(
        "Il n'y a pas de bonne façon de faire ce protocole — il y a juste le faire. Tu vas "
        "probablement résister certains jours. C'est normal. La résistance est le signe que "
        "quelque chose bouge."
    )

    # =========================================================================
    # PAGES 4-5 — BILAN DE DÉPART
    # =========================================================================
    pdf.add_page()
    pdf.h1("Avant de commencer")
    pdf.h2("Le bilan de départ — Jour 0")
    pdf.gold_line()
    pdf.ln(5)

    pdf.body(
        "Avant de démarrer, prends 10 minutes pour répondre honnêtement aux questions suivantes. "
        "Note une valeur de 1 à 5 pour chaque domaine (1 = très mauvais, 5 = excellent). "
        "Tu referas ce bilan exactement au Jour 7 pour mesurer la progression réelle."
    )
    pdf.ln(3)

    questions = [
        ("1. Niveau d'énergie générale", "Au réveil, en milieu de journée, en soirée — sur 5"),
        ("2. Qualité du sommeil", "Facilité à t'endormir, profondeur, sensation au réveil"),
        ("3. Régulation émotionnelle", "Ta capacité à observer tes émotions sans être submergé"),
        ("4. Comportements automatiques", "Téléphone compulsif, grignotage, rumination — fréquence"),
        ("5. Clarté de pensée", "Concentration, décisions, flou mental"),
        ("6. Réponse au stress", "Temps de récupération après un événement stressant"),
        ("7. Qualité nutritionnelle", "Ce que tu manges au quotidien, hydratation"),
        ("8. Activité physique", "Fréquence, intensité, plaisir"),
        ("9. Connexions sociales", "Relations nourrissantes vs relations drainantes"),
        ("10. Sens et direction", "Tu sais pourquoi tu te lèves le matin"),
    ]

    pdf.set_font("DV", "", 10)
    for q, desc in questions:
        y_start = pdf.get_y()
        pdf.set_font("DV", "B", 11)
        pdf.set_text_color(*TEAL)
        pdf.cell(0, 7, q)
        pdf.ln(6)
        pdf.set_font("DV", "I", 10)
        pdf.set_text_color(*DARK)
        pdf.set_x(pdf.l_margin + 5)
        pdf.cell(0, 5, desc)
        pdf.ln(6)
        # Score line
        pdf.set_x(pdf.l_margin + 5)
        pdf.set_font("DV", "", 10)
        pdf.set_text_color(150, 150, 150)
        pdf.cell(30, 6, "Mon score :")
        pdf.set_text_color(*DARK)
        for score in range(1, 6):
            pdf.set_draw_color(*GOLD)
            pdf.rect(pdf.get_x(), pdf.get_y(), 8, 6)
            pdf.set_draw_color(*DARK)
            pdf.set_font("DV", "", 9)
            pdf.cell(8, 6, str(score), align="C")
        pdf.ln(8)
        pdf.teal_rule()
        pdf.ln(3)

    pdf.ln(3)
    pdf.set_font("DV", "B", 12)
    pdf.set_text_color(*TEAL)
    pdf.cell(0, 8, "Score total (/50) : ______")
    pdf.ln(5)
    pdf.set_font("DV", "I", 11)
    pdf.set_text_color(*DARK)
    pdf.body("Note ce score. Tu le referas au Jour 7 — la progression sera ta preuve.")

    pdf.cream_box(
        "Les 3 règles du protocole",
        [
            "Pas de jugement",
            "_Ce que tu observes n'est pas bon ou mauvais — c'est de l'information._",
            "---",
            "Pas de performance",
            "_Il n'y a pas de 'réussir' ce protocole. Il n'y a que le faire._",
            "---",
            "Fais-le même quand tu n'en as pas envie",
            "_Surtout quand tu n'en as pas envie. C'est exactement le moment où ça compte._",
        ],
    )

    # =========================================================================
    # PAGES 6-8 — JOUR 1
    # =========================================================================
    pdf.day_header("Jour 1", "L'inventaire neural", "Prise de conscience et observation")

    pdf.body(
        "Le premier obstacle au changement n'est pas le manque de motivation — c'est le manque "
        "de conscience. On ne peut pas modifier ce qu'on ne voit pas. Aujourd'hui, ton seul travail "
        "est d'observer."
    )

    pdf.section_label("MATIN — L'écriture libre (10 minutes)")
    pdf.body(
        "Prends un cahier ou une feuille. Mets un minuteur sur 10 minutes. Écris tout ce qui "
        "est dans ta tête, sans filtre, sans correction, sans relire. Pas de structure, pas de "
        "cohérence nécessaire. Du flux brut."
    )
    pdf.body(
        "Pourquoi ça marche : L'écriture sans filtre active le cortex préfrontal — la partie "
        "rationnelle de ton cerveau — et réduit simultanément l'activité de l'amygdale, ton "
        "centre de l'alarme émotionnelle. Tu externalises la charge mentale : une fois sur le "
        "papier, ton cerveau peut arrêter de la 'tenir' en mémoire de travail. C'est l'équivalent "
        "d'un redémarrage partiel du système."
    )

    pdf.section_label("JOURNÉE — Le tracking des automatismes (4 heures)")
    pdf.body(
        "Pendant les 4 prochaines heures, note chaque comportement automatique que tu effectues. "
        "Pas de jugement — juste une liste. Voici ce qu'il faut surveiller :"
    )
    pdf.bullet("Chaque fois que tu vérifies ton téléphone sans raison précise")
    pdf.bullet("Chaque grignotage non planifié")
    pdf.bullet("Chaque pensée négative automatique sur toi-même")
    pdf.bullet("Chaque moment où tu passes à une autre tâche avant d'avoir fini la première")
    pdf.bullet("Chaque fois que tu ouvres les réseaux sociaux par réflexe")
    pdf.bullet("Chaque soupir, chaque tension dans les épaules que tu remarques")
    pdf.ln(3)
    pdf.body(
        "La plupart de ces comportements sont pilotés par le noyau accumbens — ton centre de "
        "récompense — qui cherche une mini-dose de dopamine pour échapper à un inconfort. "
        "Observer le comportement sans y céder, c'est déjà créer un espace entre le stimulus "
        "et la réponse. C'est là où la liberté commence."
    )

    pdf.section_label("SOIR — L'analyse et la sélection (20 minutes)")
    pdf.body(
        "Reprends ta liste. Relis-la calmement. Maintenant, entoure les 3 comportements qui "
        "te drainent le plus — ceux qui, après coup, te laissent moins d'énergie, moins de "
        "clarté, ou un vague sentiment de honte. Ces 3-là seront tes cibles pour le reste "
        "de la semaine."
    )
    pdf.italic(
        "Tu n'as pas à tout changer d'un coup. Identifier les trois leviers les plus impactants "
        "et travailler dessus spécifiquement est neurologiquement plus efficace que de vouloir "
        "tout modifier en même temps."
    )

    pdf.cream_box(
        "Fiche Jour 1",
        [
            "** Écriture libre — ce qui était dans ma tête ce matin :**",
            "",
            ".............................................",
            ".............................................",
            ".............................................",
            "---",
            "** Mes 3 comportements automatiques les plus drainants :**",
            "\u2022 1. .............................................",
            "\u2022 2. .............................................",
            "\u2022 3. .............................................",
            "---",
            "** Ce que j'ai remarqué aujourd'hui que je ne voyais pas avant :**",
            ".............................................",
        ],
    )

    pdf.nutrition_tip(
        "Élimine tout le sucre ajouté aujourd'hui. Pas de jus, pas de biscuits, pas de "
        "sucreries, pas de sodas. Pourquoi ? Le sucre raffiné provoque un pic de dopamine suivi "
        "d'un creux. Ce creux abaisse ta ligne de base dopaminergique, ce qui te rend plus "
        "dépendant des comportements automatiques pour obtenir du soulagement. En éliminant le "
        "sucre aujourd'hui, tu prépares ton système à recevoir plus clairement les signaux "
        "des exercices suivants."
    )

    # =========================================================================
    # PAGES 9-11 — JOUR 2
    # =========================================================================
    pdf.day_header("Jour 2", "Le jeûne informationnel", "Contrôle des inputs")

    pdf.body(
        "Ton cerveau est bombardé de stimuli. Chaque notification, chaque titre d'actualité, "
        "chaque scroll est un pic de dopamine artificiel. Le problème : ces pics aplatissent "
        "ta réponse dopaminergique de base, rendant tout le reste — le travail profond, les "
        "relations, la nature — fade et peu stimulant. Aujourd'hui, on remet les compteurs à zéro."
    )

    pdf.section_label("MATIN — Zéro téléphone pendant 60 minutes")
    pdf.body(
        "Dès que tu te lèves, ne touche pas à ton téléphone. Pas de notifications, pas de "
        "messages, pas de réseaux sociaux, pas d'emails. Soixante minutes de calme délibéré."
    )
    pdf.body(
        "La recherche du Dr Andrew Huberman (Université de Stanford) montre que le niveau de "
        "dopamine le matin crée la 'ligne de base' pour le reste de la journée. Si tu le pics "
        "immédiatement avec des notifications, ton cerveau indexe tout le reste comme ennuyeux "
        "par comparaison. Tu passes la journée à chercher ce niveau d'excitation — et tu le "
        "trouves rarement."
    )
    pdf.body(
        "À la place : eau, lumière naturelle si possible, mouvements légers, écriture ou "
        "lecture physique. Laisse le cerveau se réveiller selon son propre rythme."
    )

    pdf.section_label("JOURNÉE — Réduction de 80 % de l'input informationnel")
    pdf.bullet("Aucune actualité (ni TV, ni journaux en ligne, ni podcasts d'info)")
    pdf.bullet("Aucun réseau social")
    pdf.bullet("Emails limités à 2 consultations (midi et 17h uniquement)")
    pdf.bullet("Messagerie instantanée : désactiver les notifications, répondre en 2 blocs")
    pdf.ln(3)
    pdf.body(
        "Le cerveau traite environ 11 millions de bits d'information par seconde — mais la "
        "conscience n'en gère que 50. Tout le reste est traité inconsciemment, en consommant "
        "de l'énergie cognitive. Réduire le bruit informationnel, c'est libérer cette énergie "
        "pour les fonctions exécutives supérieures."
    )

    pdf.section_label("MIDI — La marche silencieuse (20 minutes)")
    pdf.body(
        "Sors marcher 20 minutes. Sans podcast, sans musique, sans appel. Juste toi et "
        "l'environnement. Observe ce que tu ressens. La majorité des gens éprouvent un inconfort "
        "intense les cinq premières minutes — c'est le cerveau qui cherche sa dose de stimulation."
    )
    pdf.body(
        "Cette marche active le Default Mode Network — le réseau par défaut du cerveau. "
        "C'est le système de 'défragmentation' qui travaille quand tu n'es pas activement "
        "concentré. Il consolide les apprentissages, résout des problèmes en arrière-plan, "
        "et génère les intuitions. La plupart des gens ne lui donnent jamais de temps parce "
        "qu'ils comblent chaque vide avec du contenu."
    )

    pdf.section_label("SOIR — Journal de décompression (15 minutes)")
    pdf.body("Réponds par écrit à ces trois questions :")
    pdf.numbered(1, "Qu'est-ce qui a été inconfortable aujourd'hui ? Quand as-tu eu le plus envie de vérifier ton téléphone ?")
    pdf.ln(2)
    pdf.numbered(2, "Qu'as-tu remarqué quand tu n'étais pas en train de consommer du contenu ?")
    pdf.ln(2)
    pdf.numbered(3, "As-tu eu des pensées, idées ou prises de conscience inhabituelles pendant la marche silencieuse ?")
    pdf.ln(3)

    pdf.cream_box(
        "Fiche Jour 2",
        [
            "**Combien de temps j'ai tenu sans téléphone ce matin ?**",
            ".............................................",
            "---",
            "**Ce que j'ai ressenti pendant la marche silencieuse :**",
            ".............................................",
            ".............................................",
            "---",
            "**Moment le plus difficile de la journée (envie de 'consommer') :**",
            ".............................................",
            "---",
            "**Une chose que j'ai remarquée sur moi-même aujourd'hui :**",
            ".............................................",
        ],
    )

    pdf.nutrition_tip(
        "Ajoute des sources d'oméga-3 à ton alimentation : saumon sauvage, sardines, maquereau, "
        "noix, graines de chia ou de lin. Les acides gras oméga-3 (en particulier DHA et EPA) "
        "sont les briques structurelles des membranes des neurones. Ils facilitent la transmission "
        "synaptique et ont un effet anti-inflammatoire direct sur le cerveau. Un cerveau en "
        "inflammation chronique (souvent lié à une alimentation pro-inflammatoire) a un "
        "fonctionnement préfrontal réduit — soit exactement la région dont tu as besoin "
        "pour ce protocole."
    )

    # =========================================================================
    # PAGES 12-14 — JOUR 3
    # =========================================================================
    pdf.day_header("Jour 3", "La reprogrammation motrice", "Connexion corps-cerveau")

    pdf.body(
        "Le cerveau et le corps ne sont pas séparés. Chaque état physique influence l'état mental. "
        "Aujourd'hui, on utilise le corps comme outil de transformation neurale. Ce n'est pas "
        "du sport pour la forme — c'est de la pharmacologie endogène."
    )

    pdf.section_label("MATIN — L'exposition au froid (7 minutes)")
    pdf.body(
        "Douche froide ou visage plongé dans l'eau froide, 7 minutes total. Si tu commences "
        "par une douche chaude, passe au froid les 2 dernières minutes minimum."
    )
    pdf.body(
        "L'exposition au froid déclenche une libération de norépinéphrine (noradrénaline) de "
        "250 à 300 % au-dessus de la ligne de base. Cette molécule efface le brouillard mental, "
        "augmente la concentration et la vigilance, et — surtout — entraîne ta tolérance au "
        "stress. Chaque fois que tu restes sous l'eau froide malgré l'inconfort, tu exerces "
        "littéralement ton cortex préfrontal à prendre le contrôle sur les impulsions du "
        "système limbique. C'est exactement la même compétence que celle qui te permet de "
        "résister à un comportement automatique."
    )

    pdf.section_label("JOURNÉE — Le mouvement intentionnel (30 minutes minimum)")
    pdf.body(
        "Marche rapide, vélo, natation, musculation, yoga — peu importe. L'intensité doit être "
        "suffisante pour que tu ne puisses pas tenir une conversation complète facilement. "
        "30 minutes minimum."
    )
    pdf.body(
        "Le BDNF (Brain-Derived Neurotrophic Factor) est une protéine produite en grande quantité "
        "pendant l'exercice physique. Les neuroscientifiques l'appellent parfois 'Miracle-Gro pour "
        "le cerveau' — c'est littéralement un engrais qui favorise la croissance et la connexion "
        "de nouveaux neurones. L'hippocampe, le siège de la mémoire et de l'apprentissage, "
        "est particulièrement sensible au BDNF. Quand tu bouges, tu facilites la neurogénèse."
    )

    pdf.section_label("APRÈS-MIDI — La respiration en carré (5 minutes)")
    pdf.body(
        "Trouve 5 minutes au calme. Pratique la respiration 4-4-4-4 :"
    )
    pdf.numbered(1, "Inspire lentement en comptant jusqu'à 4")
    pdf.ln(1)
    pdf.numbered(2, "Bloque le souffle, poumons pleins, en comptant jusqu'à 4")
    pdf.ln(1)
    pdf.numbered(3, "Expire lentement en comptant jusqu'à 4")
    pdf.ln(1)
    pdf.numbered(4, "Bloque le souffle, poumons vides, en comptant jusqu'à 4")
    pdf.ln(3)
    pdf.body(
        "Répète 8 à 10 fois. Cette technique active le nerf vague — le 'câble' entre le cerveau "
        "et les organes — et bascule ton système nerveux du mode sympathique (alarme, stress) "
        "au mode parasympathique (repos, récupération, digestion). Utilisable n'importe quand "
        "comme outil d'urgence le reste de ta vie."
    )

    pdf.section_label("SOIR — Le scan corporel (10 minutes)")
    pdf.body(
        "Allonge-toi. Ferme les yeux. Parcours mentalement ton corps de la tête aux pieds, "
        "en accordant 30 secondes à chaque zone : crâne, front, mâchoires, cou, épaules, bras, "
        "mains, poitrine, abdomen, bas du dos, hanches, cuisses, genoux, mollets, pieds. "
        "À chaque zone, pose-toi la question : est-ce qu'il y a de la tension ici ?"
    )
    pdf.body(
        "Ne cherche pas à changer quoi que ce soit. Observe seulement. La simple observation "
        "avec attention focalisée suffit souvent à relâcher une tension que le cerveau maintenait "
        "inconsciemment. C'est une forme de biofeedback interne."
    )

    pdf.cream_box(
        "Fiche Jour 3",
        [
            "**Ressenti après la douche froide (échelle 1-10 d'énergie) :**",
            "Avant : _____ / Après : _____",
            "---",
            "**Activité physique faite aujourd'hui :**",
            "Type : ......................... Durée : ......... minutes",
            "---",
            "**Ce que j'ai remarqué pendant le scan corporel (zones de tension) :**",
            ".............................................",
            ".............................................",
            "---",
            "**Comment je me sens ce soir par rapport à ce matin ?**",
            ".............................................",
        ],
    )

    pdf.nutrition_tip(
        "Hydratation prioritaire aujourd'hui. Bois au minimum 2,5 litres d'eau. Pourquoi c'est "
        "critique : une déshydratation de seulement 2 % entraîne une réduction de 20 % des "
        "performances cognitives. Concentration, mémoire de travail, vitesse de traitement — "
        "tout décline. La plupart des gens sont en déshydratation chronique légère sans le "
        "savoir. Ajoute une pincée de sel de mer non raffiné à un grand verre d'eau le matin "
        "pour améliorer l'absorption cellulaire."
    )

    # =========================================================================
    # PAGES 15-17 — JOUR 4
    # =========================================================================
    pdf.day_header("Jour 4", "Le nettoyage émotionnel", "Traitement des charges stockées")

    pdf.body(
        "Les émotions non traitées ne disparaissent pas. Elles s'enkystent dans le corps et "
        "le système nerveux sous forme de cortisol chronique, de tensions musculaires, de "
        "schémas comportementaux automatiques. Aujourd'hui, on fait de l'hygiène émotionnelle."
    )

    pdf.section_label("MATIN — La lettre non envoyée (30 minutes)")
    pdf.body(
        "Pense à quelqu'un qui t'a blessé — récemment ou dans le passé. Quelqu'un envers qui "
        "tu ressens encore de la colère, de la rancœur, de la tristesse, ou de la "
        "déception non résolue. Écris-lui une lettre complète, en ne te censurant pas. "
        "Dis tout ce que tu n'as jamais pu ou voulu dire."
    )
    pdf.body(
        "Cette lettre n'est pas destinée à être envoyée. Elle est destinée à ton cortex cingulaire "
        "antérieur — la région cérébrale qui traite les conflits émotionnels non résolus. "
        "L'écriture expressive active le traitement sémantique des expériences émotionnelles : "
        "elle transforme une charge affective brute en narrative cohérente, ce qui est "
        "neurochimiquement le processus de résolution."
    )

    pdf.section_label("LA SCIENCE DES ÉMOTIONS STOCKÉES")
    pdf.body(
        "Les émotions non résolues maintiennent une sécrétion chronique de cortisol. Or, "
        "le cortisol chronique a des effets destructeurs mesurables sur la structure cérébrale :"
    )
    pdf.bullet("Atrophie de l'hippocampe : la mémoire à long terme se détériore")
    pdf.bullet("Réduction du volume du cortex préfrontal : la prise de décision s'affaiblit")
    pdf.bullet("Hyperactivité de l'amygdale : la réactivité au stress augmente")
    pdf.bullet("Réduction de la neurogénèse : moins de nouvelles connexions se forment")
    pdf.ln(3)
    pdf.body(
        "Ce n'est pas métaphorique. Ce sont des changements structuraux mesurables par IRM "
        "fonctionnelle. Traiter les émotions stockées, c'est protéger — et potentiellement "
        "restaurer — l'architecture de ton cerveau."
    )

    pdf.section_label("MIDI — LA CORRECTION COGNITIVE (20 MINUTES)")
    pdf.body(
        "Identifie tes 3 pensées négatives récurrentes. Pas les émotions — les pensées. "
        "Les phrases que tu te répètes. Exemple : 'Je suis nul à l'organisation', "
        "'Les autres me voient comme faible', 'Je ne finirai jamais ce que je commence'."
    )
    pdf.body(
        "Pour chacune, écris non pas son contraire (les affirmations positives ne marchent pas "
        "si elles ne sont pas croyables) — mais une CORRECTION BASÉE SUR DES PREUVES. "
        "Cherche dans ta mémoire un contre-exemple réel, concret, spécifique."
    )
    pdf.italic(
        "Exemple : 'Je suis nul à l'organisation' → 'En 2023, j'ai organisé un voyage "
        "de 3 semaines pour 4 personnes sans accroc majeur. Mon organisation peut être "
        "situationnelle et perfectible, mais elle n'est pas nulle.'"
    )
    pdf.body(
        "Cette technique s'appelle la restructuration cognitive — c'est le cœur de la thérapie "
        "cognitivo-comportementale. Elle recâble littéralement les schémas de pensée en créant "
        "de nouvelles voies neurales qui contournent les raccourcis cognitifs négatifs."
    )

    pdf.section_label("SOIR — La gratitude spécifique (10 minutes)")
    pdf.body(
        "Écris 3 choses que tu as BIEN gérées aujourd'hui. Pas les grandes victoires — les "
        "micro-compétences. 'J'ai remarqué que j'étais stressé et je me suis arrêté 2 minutes "
        "au lieu de réagir immédiatement.' 'J'ai mangé un vrai repas au lieu de grignoter.' "
        "'J'ai tenu ma promesse de faire la lettre même si c'était difficile.'"
    )
    pdf.body(
        "Pourquoi cette forme de gratitude spécifique ? Elle cible le cortex cingulaire antérieur "
        "et le striatum ventral — elle génère un signal de récompense qui associe la compétence "
        "à un état positif. C'est ainsi que tu déplaces le focus du déficit vers la capacité."
    )

    pdf.cream_box(
        "Fiche Jour 4",
        [
            "**Mes 3 pensées négatives récurrentes et leur correction :**",
            "\u2022 Pensée 1 : .......................................",
            "  Correction : .......................................",
            "\u2022 Pensée 2 : .......................................",
            "  Correction : .......................................",
            "\u2022 Pensée 3 : .......................................",
            "  Correction : .......................................",
            "---",
            "**3 choses que j'ai bien gérées aujourd'hui :**",
            "\u2022 1. .............................................",
            "\u2022 2. .............................................",
            "\u2022 3. .............................................",
        ],
    )

    pdf.nutrition_tip(
        "Le magnésium aujourd'hui. 60 à 70 % de la population occidentale est déficiente en "
        "magnésium. Or, le magnésium est un cofacteur essentiel pour la production de GABA — "
        "le principal neurotransmetteur inhibiteur du cerveau. Sans GABA suffisant, le cerveau "
        "reste en mode 'alerte' permanent. Sources alimentaires riches : amandes, épinards, "
        "graines de citrouille, cacao brut, légumineuses. Tu peux aussi supplémenter avec du "
        "glycinate de magnésium le soir — c'est la forme la mieux absorbée et la moins "
        "laxative."
    )

    # =========================================================================
    # PAGES 18-20 — JOUR 5
    # =========================================================================
    pdf.day_header("Jour 5", "La reconstruction des routines", "Installation de nouveaux automatismes")

    pdf.body(
        "Les habitudes sont des circuits neuraux qui se sont stabilisés par la répétition. "
        "Elles ne disparaissent jamais vraiment — elles sont juste plus ou moins actives. "
        "Pour en installer de nouvelles, il ne s'agit pas de volonté : il s'agit d'ingénierie "
        "comportementale. Aujourd'hui, tu construis ton architecture."
    )

    pdf.section_label("MATIN — La routine idéale (analyse et design)")
    pdf.body(
        "Avant de te lancer dans la journée, prends 20 minutes pour concevoir ta routine "
        "matinale idéale. Contrainte absolue : maximum 45 minutes, minimum viable. "
        "Si tu ne peux pas la faire un mardi chargé, elle n'est pas bonne."
    )
    pdf.body(
        "Le principe fondateur de James Clear ('Atomic Habits') : chaque nouvelle habitude "
        "doit commencer dans une version qui prend moins de 2 minutes. Non pas parce que "
        "2 minutes suffisent — mais parce que le vrai obstacle à une habitude, c'est de "
        "commencer. Une fois lancé, tu continues naturellement."
    )

    pdf.cream_box(
        "Template : Ma routine matinale idéale",
        [
            "À l'heure que je me lève : _____________",
            "",
            "Les 5 premières minutes : .............",
            "Minutes 5-10 : ........................",
            "Minutes 10-20 : .......................",
            "Minutes 20-30 : .......................",
            "Minutes 30-45 : .......................",
            "---",
            "**Version 2 minutes** (pour les mauvais jours) :",
            ".............................................",
        ],
    )

    pdf.section_label("LA TECHNIQUE DE L'EMPILEMENT D'HABITUDES")
    pdf.body(
        "Le moyen le plus efficace d'installer une nouvelle habitude est de la 'coller' à "
        "une habitude existante. Le format est : 'Après [habitude existante], je ferai "
        "[nouvelle habitude].'"
    )
    pdf.body("Exemples :")
    pdf.bullet("Après avoir versé mon café, je ferai 5 minutes de pleine conscience")
    pdf.bullet("Après m'être brossé les dents le soir, je noterai 3 choses du lendemain")
    pdf.bullet("Après être arrivé à mon bureau, je noterai ma priorité numéro 1 du jour")
    pdf.ln(3)
    pdf.body(
        "Cette technique fonctionne parce qu'elle s'appuie sur un circuit neural déjà bien "
        "établi (l'habitude existante) pour créer le contexte déclencheur de la nouvelle. "
        "Le cerveau n'a pas besoin d'énergie supplémentaire pour 'se souvenir' — la "
        "séquence se déclenche automatiquement."
    )
    pdf.body(
        "Identifie maintenant 3 empilements que tu vas implémenter cette semaine. Sois "
        "spécifique sur le 'après quoi' — vague ne fonctionne pas."
    )

    pdf.section_label("JOURNÉE — La carte de l'énergie")
    pdf.body(
        "Toutes les 2 heures aujourd'hui, note sur une échelle de 1 à 10 ton niveau d'énergie "
        "et de concentration. À la fin de la journée, tu verras ton profil énergétique réel."
    )
    pdf.body(
        "La gestion du temps sans gestion de l'énergie est une illusion. Aligner tes tâches "
        "cognitives les plus exigeantes sur tes pics d'énergie — et les tâches administratives "
        "sur les creux — double l'efficacité sans effort supplémentaire."
    )

    pdf.section_label("SOIR — La préparation totale du lendemain")
    pdf.body(
        "Avant de te coucher, prépare complètement demain. Habits posées. Repas du lendemain "
        "décidé (et préparé si possible). Agenda du lendemain révisé. Top 3 priorités identifiées "
        "et notées. Sac ou espace de travail préparé."
    )
    pdf.body(
        "Chaque décision le matin épuise ta réserve de glucose préfrontal. En éliminant les "
        "décisions matinales, tu arrives au travail avec un cortex préfrontal intact — plutôt "
        "qu'à moitié vide."
    )

    pdf.cream_box(
        "Fiche Jour 5 — Mes 3 empilements d'habitudes",
        [
            "Après [habitude existante 1], je ferai :",
            ".............................................",
            "Après [habitude existante 2], je ferai :",
            ".............................................",
            "Après [habitude existante 3], je ferai :",
            ".............................................",
            "---",
            "**Mon profil énergétique du jour :**",
            "8h : ___ / 10h : ___ / 12h : ___ / 14h : ___ / 16h : ___ / 18h : ___",
            "---",
            "**Ma préparation pour demain est complète :** Oui / Non",
        ],
    )

    # =========================================================================
    # PAGES 21-23 — JOUR 6
    # =========================================================================
    pdf.day_header("Jour 6", "Le reset social", "Environnement et relations")

    pdf.body(
        "Tu es le reflet de ton environnement. Pas métaphoriquement — neurochimiquement. "
        "Tes neurones miroirs imitent les états émotionnels des personnes qui t'entourent. "
        "Ton contenu informationnel module tes valeurs et tes priorités à ton insu. "
        "Aujourd'hui, on audite et on recalibre."
    )

    pdf.section_label("MATIN — L'audit de ton régime informationnel")
    pdf.body(
        "Prends 20 minutes pour passer en revue tes abonnements, tes follows, tes sources "
        "d'information régulières. Pour chacune, pose-toi une seule question : est-ce que "
        "cela me construit ou cela me draine ?"
    )
    pdf.body("Sois honnête. Pas de justification. Juste 'construit' ou 'draine'.")
    pdf.bullet("Compte Instagram, TikTok ou Twitter qui te fait te sentir inférieur ou en colère ? Unfollow.")
    pdf.bullet("Podcast ou newsletter qui te stresse sans t'informer vraiment ? Désabonne.")
    pdf.bullet("Groupe WhatsApp chronophage et à faible valeur ? Mets en sourdine indéfiniment.")
    pdf.ln(3)
    pdf.body(
        "La recherche sur les neurones miroirs (Giacomo Rizzolatti, Université de Parme) a "
        "démontré que le cerveau active les mêmes circuits neuronaux qu'il observe une action "
        "ou qu'il la réalise. Cela s'étend aux états émotionnels : être exposé à de la colère, "
        "de l'anxiété ou du cynisme active ces mêmes états en toi, même passivement."
    )

    pdf.section_label("LA RÈGLE DES 5 PERSONNES")
    pdf.body(
        "Jim Rohn affirmait que tu es la moyenne des 5 personnes que tu côtoies le plus. "
        "La recherche en neurosciences sociales lui donne raison : les réseaux sociaux "
        "humains transmettent les états émotionnels, les comportements et même les croyances "
        "par contagion neurale. Ce n'est pas de la philosophie — c'est mesurable."
    )
    pdf.body(
        "Prends une feuille. Écris tes 5 personnes les plus proches. Pour chacune :"
    )
    pdf.numbered(1, "Quel état émotionnel prédominant est-ce que cette personne transmet ?")
    pdf.ln(1)
    pdf.numbered(2, "Est-ce que je me sens plus capable et énergisé après l'avoir vue ?")
    pdf.ln(1)
    pdf.numbered(3, "Est-ce que cette personne me tire vers le haut ou me maintient dans mes anciens schémas ?")
    pdf.ln(3)

    pdf.section_label("MIDI — Une vraie conversation (1 heure)")
    pdf.body(
        "Appelle ou rencontre en face à face quelqu'un qui compte pour toi. Pas un message, "
        "pas un email — une vraie conversation. Pose des questions réelles. Écoute vraiment. "
        "Partage quelque chose de vrai sur ce que tu traverses en ce moment."
    )
    pdf.body(
        "La connexion sociale profonde libère de l'ocytocine — le neuromodulateur qui atténue "
        "directement l'activité de l'amygdale et réduit le cortisol. C'est l'antidote "
        "physiologique au stress. Et contrairement aux anxiolytiques, il n'y a pas d'effets "
        "secondaires."
    )

    pdf.section_label("SOIR — Une limite posée")
    pdf.body(
        "Identifie une situation ou une relation qui te draine régulièrement. Pas la plus "
        "complexe — commence par la plus évidente. Décide d'une limite spécifique et "
        "concrète que tu vas poser. Pas un changement définitif — juste un ajustement "
        "mesurable. Et notifie-le à la personne concernée si nécessaire."
    )
    pdf.italic(
        "Poser une limite n'est pas rejeter l'autre. C'est protéger l'énergie qui permet "
        "d'être présent, généreux et utile — y compris pour cette personne."
    )

    pdf.cream_box(
        "Fiche Jour 6",
        [
            "**Contenus désabonnés / mis en sourdine aujourd'hui :**",
            ".............................................",
            "---",
            "**Mes 5 personnes proches — bilan énergétique :**",
            "\u2022 Personne 1 : .............. État transmis : ..........",
            "\u2022 Personne 2 : .............. État transmis : ..........",
            "\u2022 Personne 3 : .............. État transmis : ..........",
            "---",
            "**La limite que j'ai décidée de poser :**",
            ".............................................",
            ".............................................",
        ],
    )

    pdf.nutrition_tip(
        "L'axe intestin-cerveau. 90 % de la sérotonine de ton organisme est produite dans "
        "ton intestin, pas dans ton cerveau. Le microbiome intestinal communique directement "
        "avec le cerveau via le nerf vague. Un microbiome pauvre = une sérotonine basse = "
        "humeur instable, anxiété, difficulté à réguler les émotions. Ajoute aujourd'hui des "
        "aliments fermentés : kimchi, choucroute non pasteurisée, kéfir, yaourt au lait entier, "
        "kombucha. Et augmente les fibres prébiotiques (artichaut, poireau, ail, oignon, "
        "banane pas trop mûre) pour nourrir les bonnes bactéries déjà présentes."
    )

    # =========================================================================
    # PAGES 24-26 — JOUR 7
    # =========================================================================
    pdf.day_header("Jour 7", "L'intégration", "Consolidation et projection")

    pdf.body(
        "C'est le jour de la synthèse. Pas de nouvelles techniques, pas de nouveaux exercices. "
        "Aujourd'hui, tu regardes le chemin parcouru, tu identifies ce qui a marché, tu décides "
        "ce que tu emportes. Et tu célèbres — sérieusement."
    )

    pdf.section_label("MATIN — Le bilan de départ revisité")
    pdf.body(
        "Reprends le questionnaire du Jour 0. Réponds aux mêmes 10 questions, avec la même "
        "honnêteté. Compare les scores. La différence que tu observes est une mesure réelle "
        "des changements neuronaux que tu as amorcés cette semaine."
    )
    pdf.body(
        "Note : le changement peut ne pas être spectaculaire sur tous les axes — certains "
        "prennent plus de temps que d'autres. Mais quelque chose aura bougé. Toujours."
    )

    pdf.section_label("RELECTURE DES 6 FICHES")
    pdf.body(
        "Reprends les fiches des six jours précédents. Lis-les lentement. Cherche les patterns :"
    )
    pdf.bullet("Quel jour a été le plus difficile ? Pourquoi, selon toi ?")
    pdf.bullet("Quel exercice a suscité le plus de résistance ? Qu'est-ce que ça révèle ?")
    pdf.bullet("Quelle prise de conscience t'a le plus surpris ?")
    pdf.bullet("Quels comportements automatiques ont déjà commencé à changer ?")
    pdf.ln(3)

    pdf.section_label("LES 3 HABITUDES NON-NÉGOCIABLES")
    pdf.body(
        "De tout ce que tu as expérimenté cette semaine, sélectionne 3 pratiques que tu t'engages "
        "à maintenir les 30 prochains jours. Pas 10, pas 7 — 3. Celles qui ont eu le plus "
        "d'impact, ou celles dont tu ressens le plus que tu en as besoin."
    )
    pdf.body(
        "Le critère de sélection : est-ce que je peux m'engager à faire cela même les jours "
        "où tout va mal ? Si oui — c'est un non-négociable viable."
    )

    pdf.section_label("LE PROTOCOLE MINIMUM VIABLE")
    pdf.body(
        "Définis maintenant la version 'pire des jours' de ton protocole quotidien. "
        "La version que tu feras même quand tu es épuisé, en voyage, ou sous pression. "
        "Elle doit tenir en 10 à 15 minutes maximum."
    )

    pdf.cream_box(
        "Mon protocole minimum (à faire TOUS les jours)",
        [
            "Le matin :",
            "  1. .............................................",
            "  2. .............................................",
            "",
            "Dans la journée :",
            "  1. .............................................",
            "",
            "Le soir :",
            "  1. .............................................",
            "---",
            "**Durée estimée :** ......... minutes",
        ],
    )

    pdf.section_label("LA LETTRE À TOI-MÊME")
    pdf.body(
        "Écris une lettre à toi-même, à lire dans 30 jours exactement. Parle-lui de la "
        "semaine que tu viens de faire. Dis-lui ce que tu espères. Dis-lui ce dont tu as "
        "besoin de te souvenir quand la motivation retombera — parce qu'elle retombera. "
        "Sois spécifique. Sois honnête. Sois généreux envers toi-même."
    )
    pdf.body(
        "Scelle la lettre. Mets une alarme dans ton calendrier pour dans 30 jours. "
        "Ne la rouvre pas avant."
    )

    pdf.section_label("SOIR — La célébration (non-optionnel)")
    pdf.body(
        "Célèbre ce que tu viens de faire. Sérieusement. Pas modestement, pas 'bon ok je l'ai "
        "fait'. Célèbre. Dis-le à quelqu'un. Fais quelque chose de plaisant. Prends le temps "
        "de ressentir la satisfaction."
    )
    pdf.body(
        "Voici pourquoi c'est neurochimiquement obligatoire : la dopamine marque les expériences "
        "comme 'valant la peine d'être répétées'. Sans signal de récompense à la fin d'un effort, "
        "le cerveau n'associe pas l'effort à quelque chose de positif — il l'associe juste à "
        "de la douleur. La célébration n'est pas de la vanité. C'est de la biologie."
    )

    pdf.cream_box(
        "Mes 3 engagements pour les 30 prochains jours",
        [
            "\u2022 Engagement 1 : .......................................",
            "  Quand / Comment : ...................................",
            "---",
            "\u2022 Engagement 2 : .......................................",
            "  Quand / Comment : ...................................",
            "---",
            "\u2022 Engagement 3 : .......................................",
            "  Quand / Comment : ...................................",
            "---",
            "**Score bilan Jour 0 :** _____ / 50",
            "**Score bilan Jour 7 :** _____ / 50",
            "**Progression :** _____ points",
        ],
    )

    # =========================================================================
    # PAGES 27-28 — APRÈS LES 7 JOURS
    # =========================================================================
    pdf.add_page()
    pdf.h1("Après les 7 jours")
    pdf.h2("Ce qui t'attend — et comment traverser")
    pdf.gold_line()
    pdf.ln(5)

    pdf.h3("Les pièges classiques des semaines 2 à 4")
    pdf.body(
        "La majorité des gens qui commencent un changement le maintiennent les premiers jours "
        "par l'enthousiasme du début. Puis vient la résistance. Voici ce à quoi tu peux "
        "t'attendre — et comment ne pas le confondre avec un échec."
    )

    pdf.h3("Le plateau du Jour 14")
    pdf.body(
        "Autour du 14e jour, la motivation initiale s'estompe. C'est parfaitement normal et "
        "prévisible. Le cerveau a passé la phase de nouveauté — qui génère de la dopamine "
        "naturellement — et n'a pas encore solidifié les nouvelles connexions en circuits "
        "automatiques (cela prend 21 à 66 jours selon la complexité de l'habitude)."
    )
    pdf.body(
        "Ce que tu ressentiras : les nouvelles habitudes semblent moins importantes, "
        "l'envie de revenir aux anciens schémas est forte, tu te diras que 'ça ne sert "
        "à rien'. C'est le cerveau qui teste si le nouvel investissement vaut la peine "
        "d'être stabilisé."
    )
    pdf.body("Ce que tu dois faire : continuer exactement comme si tu n'avais rien ressenti. Le plateau n'est pas un signal d'arrêt — c'est un passage obligatoire.")

    pdf.h3("Quand tu glisses (et tu glisseras)")
    pdf.body(
        "Tu vas manquer des jours. Tu vas revenir à d'anciens comportements. Tu vas passer "
        "une soirée à scroller alors que tu t'étais engagé à ne pas le faire. C'est "
        "universellement inévitable — et ça ne signifie rien sur ta capacité à changer."
    )
    pdf.body(
        "La recherche de Phillippa Lally (UCL) sur la formation des habitudes montre que "
        "les glissements occasionnels n'affectent pas significativement le temps de formation "
        "d'une habitude. Ce qui compte, c'est le retour rapide. La règle est simple : "
        "jamais deux fois de suite. Un jour manqué — OK. Deux jours manqués — c'est le "
        "début d'un retour à la case départ."
    )

    pdf.h3("Les signaux que ça fonctionne")
    pdf.body("Voici les indicateurs concrets que les changements s'installent vraiment :")
    pdf.bullet("Ton sommeil s'améliore (moins de réveils nocturnes, endormissement plus rapide)")
    pdf.bullet("Ta réactivité émotionnelle diminue (tu observes avant de réagir plus souvent)")
    pdf.bullet("Ta concentration s'étend (tu peux rester sur une tâche plus longtemps)")
    pdf.bullet("Les comportements automatiques drainants se font moins fréquents sans effort conscient")
    pdf.bullet("Tu ressens moins le 'besoin' de vérifier ton téléphone")
    pdf.bullet("Tu remarques une différence dans ta clarté mentale après tes habitudes matinales")
    pdf.ln(3)
    pdf.body(
        "Ces changements ne se produisent pas linéairement. Ils arrivent par paliers. "
        "Une semaine tu stagnes, la suivante quelque chose clique. Fais confiance au processus "
        "plus qu'aux résultats quotidiens."
    )

    pdf.h3("Pour aller plus loin")
    pdf.body(
        "Ce protocole de 7 jours est une introduction. Une mise en route. Il a interrompu les "
        "automatismes les plus coûteux, établi des bases neurales nouvelles, et donné des "
        "outils concrets. Mais la transformation profonde — celle qui tient sur le long terme — "
        "se construit sur 30, 60, 90 jours."
    )
    pdf.body(
        "Le système complet de 30 jours, avec suivi semaine par semaine, protocoles avancés "
        "de régulation du système nerveux, et plan nutritionnel intégral, est disponible dans "
        "le guide 'Reprendre le contrôle de son cerveau' — disponible en pack Platine "
        "sur neuro-odyssee.com."
    )

    # =========================================================================
    # PAGES 29-30 — CONCLUSION + À PROPOS
    # =========================================================================
    pdf.add_page()
    pdf.h1("Conclusion")
    pdf.gold_line()
    pdf.ln(6)

    pdf.set_font("DV", "B", 14)
    pdf.set_text_color(*TEAL)
    pdf.multi_cell(
        0, 9,
        "Tu viens de faire en 7 jours ce que la plupart des gens ne font jamais :\ntu as pris le contrôle.",
        align="C",
    )
    pdf.ln(6)
    pdf.set_text_color(*DARK)

    pdf.body(
        "Tu as observé tes automatismes sans les subir. Tu as réduit la sur-stimulation "
        "et observé ton cerveau se recentrer. Tu as utilisé ton corps comme levier de "
        "transformation neurale. Tu as traité des charges émotionnelles que tu portais "
        "peut-être depuis des mois ou des années. Tu as construit des architectures "
        "comportementales nouvelles. Tu as audité ton environnement humain et informationnel. "
        "Et tu as intégré le tout dans un protocole qui t'appartient."
    )
    pdf.body(
        "Sept jours. C'est peu, et c'est énorme. Le cerveau adulte est capable de changer "
        "— c'est ce que la neuroplasticité signifie dans les faits. Mais le changement ne "
        "vient pas de la motivation. Il vient des actions répétées, même imparfaites, "
        "même inconfortables, même sans envie."
    )
    pdf.body(
        "Tu as prouvé que tu pouvais le faire. Maintenant, continue. Pas parfaitement — "
        "régulièrement."
    )

    pdf.ln(5)
    pdf.gold_line()
    pdf.ln(6)

    pdf.h2("À propos de Roland Crettaz")
    pdf.body(
        "En 2024, j'ai quitté tout ce que je connaissais pour marcher 1 900 km entre "
        "Saint-Maurice (Suisse) et Santiago de Compostela. Pas pour faire le Camino. "
        "Pour comprendre comment mon cerveau fonctionnait — et apprendre à le piloter "
        "plutôt qu'à le subir."
    )
    pdf.body(
        "Diagnostiqué TDAH à l'âge adulte, j'ai passé des années à me battre contre ma "
        "propre cognition. Ce voyage a été une immersion radicale dans les neurosciences "
        "appliquées : sommeil, nutrition, mouvement, régulation émotionnelle, connexion "
        "sociale — testées sur le terrain, pas dans un bureau."
    )
    pdf.body(
        "Neuro-Odyssée est le projet qui est né de cette traversée : partager, honnêtement "
        "et sans vernis, ce que j'ai appris — pour que d'autres passent moins de temps "
        "à se battre contre leur cerveau et plus de temps à vivre."
    )

    pdf.ln(4)

    pdf.cream_box(
        "Aller plus loin avec Neuro-Odyssée",
        [
            "**neuro-odyssee.com**",
            "",
            "Guide 'Reprendre le contrôle de son cerveau' (pack Platine)",
            "  Le système complet 30 jours avec protocoles avancés",
            "---",
            "Journal de la traversée : toute l'odyssée semaine par semaine",
            "---",
            "Soutenir le projet : chaque contribution permet de continuer à produire",
            "des ressources gratuites et accessibles sur la neuroscience pratique.",
        ],
    )

    pdf.ln(6)
    pdf.set_font("DV", "I", 10)
    pdf.set_text_color(*LIGHT_GREY)
    pdf.multi_cell(
        0, 6,
        "Ce guide est destiné à un usage personnel uniquement. Toute reproduction ou "
        "redistribution est interdite sans autorisation écrite de l'auteur.\n"
        "Roland Crettaz — Neuro-Odyssée — neuro-odyssee.com — 2024/2025",
        align="C",
    )

    pdf.output(output_path)
    print(f"PDF generated: {output_path}")
    print(f"Pages: {pdf.page}")


if __name__ == "__main__":
    out = "/Users/martialnicola/Downloads/neuro-odyssee/pdf/protocole-reset-mental-7-jours.pdf"
    os.makedirs(os.path.dirname(out), exist_ok=True)
    build_pdf(out)
