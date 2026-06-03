"""
PDF generator for "Pourquoi tu n'arrives pas à changer (et quoi faire)"
Author: Roland Crettaz — Neuro-Odyssée
"""

from fpdf import FPDF
from fpdf.enums import XPos, YPos

# ---------------------------------------------------------------------------
# Constants — brand colors
# ---------------------------------------------------------------------------
TEAL       = (30, 107, 94)
GOLD       = (240, 165, 0)
DARK       = (26, 35, 50)
CREAM_BG   = (248, 246, 241)
WHITE      = (255, 255, 255)
LIGHT_GREY = (200, 200, 200)

FONT_DIR   = "/tmp/dejavu-fonts/dejavu-fonts-ttf-2.37/ttf/"

MARGIN_L   = 25
MARGIN_R   = 25
PAGE_W     = 210
USABLE_W   = PAGE_W - MARGIN_L - MARGIN_R   # 160 mm


# ---------------------------------------------------------------------------
# Helper — PDF subclass
# ---------------------------------------------------------------------------
class NeuroPDF(FPDF):

    def __init__(self):
        super().__init__(orientation="P", unit="mm", format="A4")
        self.set_margins(MARGIN_L, 20, MARGIN_R)
        self.set_auto_page_break(auto=True, margin=20)
        self._load_fonts()

    # ------------------------------------------------------------------
    def _load_fonts(self):
        self.add_font("DejaVu",        "",  FONT_DIR + "DejaVuSans.ttf")
        self.add_font("DejaVu",        "B", FONT_DIR + "DejaVuSans-Bold.ttf")
        self.add_font("DejaVu",        "I", FONT_DIR + "DejaVuSans-Oblique.ttf")
        self.add_font("DejaVu",        "BI",FONT_DIR + "DejaVuSans-BoldOblique.ttf")

    # ------------------------------------------------------------------
    # Footer with page number (skip cover page 1)
    # ------------------------------------------------------------------
    def footer(self):
        if self.page_no() == 1:
            return
        self.set_y(-15)
        self.set_font("DejaVu", "", 9)
        self.set_text_color(*LIGHT_GREY)
        self.cell(0, 10, str(self.page_no()), align="C",
                  new_x=XPos.LMARGIN, new_y=YPos.NEXT)

    # ------------------------------------------------------------------
    # Utilities
    # ------------------------------------------------------------------
    def set_body_color(self):
        self.set_text_color(*DARK)

    def gold_separator(self):
        """3 px gold line, 60 mm wide, centered."""
        x = (PAGE_W - 60) / 2
        y = self.get_y() + 3
        self.set_draw_color(*GOLD)
        self.set_line_width(0.9)
        self.line(x, y, x + 60, y)
        self.set_line_width(0.2)
        self.ln(8)

    def section_header(self, text: str, size: int = 18):
        self.set_font("DejaVu", "B", size)
        self.set_text_color(*TEAL)
        self.multi_cell(USABLE_W, 9, text, align="L",
                        new_x=XPos.LMARGIN, new_y=YPos.NEXT)
        self.ln(2)
        self.gold_separator()
        self.set_body_color()

    def body_text(self, text: str, size: int = 11, spacing: float = 7):
        self.set_font("DejaVu", "", size)
        self.set_text_color(*DARK)
        self.multi_cell(USABLE_W, spacing, text, align="J",
                        new_x=XPos.LMARGIN, new_y=YPos.NEXT)
        self.ln(2)

    def bold_text(self, text: str, size: int = 11, spacing: float = 7):
        self.set_font("DejaVu", "B", size)
        self.set_text_color(*DARK)
        self.multi_cell(USABLE_W, spacing, text, align="J",
                        new_x=XPos.LMARGIN, new_y=YPos.NEXT)
        self.ln(1)

    def italic_text(self, text: str, size: int = 11, spacing: float = 7):
        self.set_font("DejaVu", "I", size)
        self.set_text_color(*DARK)
        self.multi_cell(USABLE_W, spacing, text, align="J",
                        new_x=XPos.LMARGIN, new_y=YPos.NEXT)
        self.ln(2)

    def cream_box(self, title: str, content: str):
        """A highlighted cream background box."""
        # measure content height
        self.set_font("DejaVu", "B", 11)
        title_h  = self.get_string_width(title) / USABLE_W  # rough pages
        self.set_font("DejaVu", "", 10.5)
        # draw box — estimate height dynamically
        x = self.get_x()
        y = self.get_y()
        box_w = USABLE_W

        # Fill rectangle
        self.set_fill_color(*CREAM_BG)
        self.set_draw_color(*GOLD)
        self.set_line_width(0.5)
        # We'll draw after computing real height via write trick
        # Save position, render invisibly, measure, then re-render
        # Simpler: just give generous padding
        self.rect(MARGIN_L, y, box_w, 2, "")  # top gold accent bar
        self.set_fill_color(*GOLD)
        self.rect(MARGIN_L, y, box_w, 1.5, "F")
        self.ln(4)

        self.set_fill_color(*CREAM_BG)
        inner_y = self.get_y()
        # render text first to get real height
        self.set_x(MARGIN_L + 4)
        self.set_font("DejaVu", "B", 11)
        self.set_text_color(*TEAL)
        self.multi_cell(USABLE_W - 8, 7, title, align="L",
                        new_x=XPos.LMARGIN, new_y=YPos.NEXT)
        self.ln(1)
        self.set_x(MARGIN_L + 4)
        self.set_font("DejaVu", "", 10.5)
        self.set_text_color(*DARK)
        self.multi_cell(USABLE_W - 8, 6.5, content, align="J",
                        new_x=XPos.LMARGIN, new_y=YPos.NEXT)
        end_y = self.get_y() + 4

        # Draw background rect behind all that text (overlay trick: draw first, re-render)
        # Instead, draw background BEFORE text — we pre-estimate height
        # Strategy: calculate from string
        # For simplicity, fill from inner_y to end_y
        self.set_fill_color(*CREAM_BG)
        self.set_draw_color(*GOLD)
        self.set_line_width(0.3)
        self.rect(MARGIN_L, inner_y - 2, box_w, end_y - inner_y + 2, "FD")

        # Re-render text on top of box
        self.set_xy(MARGIN_L + 4, inner_y)
        self.set_font("DejaVu", "B", 11)
        self.set_text_color(*TEAL)
        self.multi_cell(USABLE_W - 8, 7, title, align="L",
                        new_x=XPos.LMARGIN, new_y=YPos.NEXT)
        self.ln(1)
        self.set_x(MARGIN_L + 4)
        self.set_font("DejaVu", "", 10.5)
        self.set_text_color(*DARK)
        self.multi_cell(USABLE_W - 8, 6.5, content, align="J",
                        new_x=XPos.LMARGIN, new_y=YPos.NEXT)
        self.ln(6)
        self.set_body_color()

    def chapter_label(self, number: str, title: str):
        """Large chapter heading with teal number and bold title."""
        self.set_font("DejaVu", "B", 22)
        self.set_text_color(*GOLD)
        self.cell(USABLE_W, 12, number, align="L",
                  new_x=XPos.LMARGIN, new_y=YPos.NEXT)
        self.set_font("DejaVu", "B", 20)
        self.set_text_color(*TEAL)
        self.multi_cell(USABLE_W, 11, title, align="L",
                        new_x=XPos.LMARGIN, new_y=YPos.NEXT)
        self.ln(3)
        # full-width gold line
        x = MARGIN_L
        y = self.get_y()
        self.set_draw_color(*GOLD)
        self.set_line_width(1.2)
        self.line(x, y, x + USABLE_W, y)
        self.set_line_width(0.2)
        self.ln(8)
        self.set_body_color()


# ---------------------------------------------------------------------------
# Build each page / section
# ---------------------------------------------------------------------------

def build_cover(pdf: NeuroPDF):
    pdf.add_page()

    # Top gold line
    pdf.set_draw_color(*GOLD)
    pdf.set_line_width(1.5)
    pdf.line(MARGIN_L, 18, PAGE_W - MARGIN_R, 18)
    pdf.set_line_width(0.2)

    pdf.ln(28)

    # Main title
    pdf.set_font("DejaVu", "B", 26)
    pdf.set_text_color(*TEAL)
    pdf.multi_cell(USABLE_W, 13,
                   "Pourquoi tu n'arrives pas\nà changer\n(et quoi faire)",
                   align="C", new_x=XPos.LMARGIN, new_y=YPos.NEXT)

    pdf.ln(10)

    # Gold separator
    pdf.gold_separator()
    pdf.ln(8)

    # Subtitle
    pdf.set_font("DejaVu", "I", 13)
    pdf.set_text_color(*DARK)
    pdf.multi_cell(USABLE_W, 8,
                   "Le guide pour comprendre pourquoi ton cerveau résiste\n"
                   "— et comment débloquer le premier pas",
                   align="C", new_x=XPos.LMARGIN, new_y=YPos.NEXT)

    pdf.ln(20)

    # Author
    pdf.set_font("DejaVu", "B", 14)
    pdf.set_text_color(*DARK)
    pdf.cell(USABLE_W, 9, "Roland Crettaz", align="C",
             new_x=XPos.LMARGIN, new_y=YPos.NEXT)

    pdf.ln(4)

    # Brand name
    pdf.set_font("DejaVu", "B", 16)
    pdf.set_text_color(*TEAL)
    pdf.cell(USABLE_W, 9, "NEURO-ODYSSÉE", align="C",
             new_x=XPos.LMARGIN, new_y=YPos.NEXT)

    pdf.ln(3)

    # Website
    pdf.set_font("DejaVu", "I", 11)
    pdf.set_text_color(*GOLD)
    pdf.cell(USABLE_W, 7, "neuro-odyssee.com", align="C",
             new_x=XPos.LMARGIN, new_y=YPos.NEXT)

    # Bottom gold line
    pdf.set_draw_color(*GOLD)
    pdf.set_line_width(1.5)
    pdf.line(MARGIN_L, 277, PAGE_W - MARGIN_R, 277)
    pdf.set_line_width(0.2)


def build_introduction(pdf: NeuroPDF):
    pdf.add_page()
    pdf.chapter_label("", "Introduction")

    pdf.body_text(
        "Il y a quelques années, j'étais l'exemple parfait de quelqu'un qui "
        "ne change pas. Pas parce que je manquais de volonté. Pas parce que "
        "je n'essayais pas. Mais parce que je ne comprenais rien à ce qui se "
        "passait vraiment dans mon cerveau."
    )
    pdf.body_text(
        "J'ai lutté pendant des années contre l'alcool. J'ai dépendu de "
        "médicaments que je n'aurais pas dû prendre sur la durée. J'ai fumé. "
        "J'ai recommencé. J'ai recommencé encore. Et chaque fois que j'échouais, "
        "je me répétais la même phrase toxique : \"Je suis comme ça. Je ne "
        "changerai jamais.\""
    )
    pdf.body_text(
        "Ce que je ne savais pas alors, c'est que mon cerveau n'était pas "
        "défaillant. Il faisait exactement ce pour quoi il est programmé : "
        "économiser de l'énergie, éviter l'inconnu, répéter ce qu'il connaît. "
        "Pendant des décennies, j'ai vécu avec un TDAH non diagnostiqué qui "
        "rendait chaque tentative de changement dix fois plus difficile. Pas "
        "impossible — mais épuisante."
    )
    pdf.body_text(
        "Quand j'ai finalement commencé à me former en neurosciences et en "
        "nutrition, quelque chose s'est ouvert. Pas une révélation soudaine — "
        "une compréhension progressive. Comme si quelqu'un m'allumait une "
        "lampe dans une pièce que j'avais toujours traversée dans le noir."
    )

    pdf.add_page()

    pdf.body_text(
        "Ce guide, je l'ai écrit parce que j'aurais voulu l'avoir il y a "
        "vingt ans. Pas pour te donner des conseils de motivation à deux balles. "
        "Pas pour te dire de \"te lever tôt\" ou de \"croire en toi\". Mais "
        "pour t'expliquer les mécanismes concrets, biologiques, neurologiques, "
        "qui font que tu bloques. Et pour te donner un outil simple — mais "
        "vraiment efficace — pour commencer à bouger."
    )
    pdf.body_text(
        "En lisant ces pages, tu vas comprendre :"
    )
    pdf.bold_text(
        "— Pourquoi ton cerveau résiste au changement (ce n'est pas de la "
        "paresse, c'est de la biologie)."
    )
    pdf.bold_text(
        "— Les 5 vraies raisons pour lesquelles tu te retrouves coincé, encore "
        "et encore, au même endroit."
    )
    pdf.bold_text(
        "— Un exercice concret — la technique P.O.C. — que tu peux commencer "
        "aujourd'hui, maintenant, sans rien acheter, sans rien préparer."
    )
    pdf.ln(4)
    pdf.body_text(
        "Je ne suis pas là pour te promettre une transformation en 30 jours. "
        "Je suis là pour t'aider à faire un premier pas. Parce que c'est "
        "toujours le premier pas qui coûte le plus cher — et c'est exactement "
        "celui-là qu'on va travailler ensemble."
    )
    pdf.italic_text(
        "Roland Crettaz\n"
        "En marche vers Compostelle — 1 900 km de St-Maurice à Santiago"
    )


def build_chapter1(pdf: NeuroPDF):
    pdf.add_page()
    pdf.chapter_label("Chapitre 1", "Le mécanisme simple du cerveau")

    pdf.section_header("Cerveau reptilien contre cortex préfrontal", 14)
    pdf.body_text(
        "Ton cerveau n'est pas une machine uniforme. C'est plutôt une "
        "superposition de trois cerveaux évolutifs qui coexistent — et qui "
        "ne s'entendent pas toujours bien."
    )
    pdf.body_text(
        "Le cerveau reptilien — le tronc cérébral et le cervelet — est le plus "
        "ancien. Il gère ta survie : rythme cardiaque, respiration, réflexes, "
        "réponse au danger. Il ne réfléchit pas. Il agit. Quand tu sursautes "
        "en entendant un bruit fort, c'est lui."
    )
    pdf.body_text(
        "Le système limbique — le cerveau émotionnel — intègre tes émotions, "
        "ta mémoire, tes motivations. C'est lui qui décide si quelque chose "
        "est \"dangereux\" ou \"plaisant\" avant même que tu aies conscientisé "
        "la situation."
    )
    pdf.body_text(
        "Le cortex préfrontal — la partie derrière ton front — est le plus "
        "récent. C'est lui qui te permet de planifier, de raisonner, de te "
        "projeter dans l'avenir, de résister aux impulsions. C'est ton moi "
        "conscient. Le problème ? Il est lent, énergivore — et il perd "
        "systématiquement face aux deux autres quand tu es fatigué, stressé "
        "ou affamé."
    )

    pdf.section_header("Comment les habitudes se forment", 14)
    pdf.body_text(
        "Chaque fois que tu répètes un comportement, les neurones impliqués "
        "se connectent plus solidement. \"Les neurones qui s'activent ensemble "
        "se connectent ensemble\" — c'est la plasticité synaptique, découverte "
        "par Donald Hebb en 1949."
    )
    pdf.body_text(
        "Avec la répétition, une substance appelée myéline vient enrober les "
        "connexions neuronales les plus utilisées. La myéline fonctionne comme "
        "une gaine isolante sur un câble électrique : elle accélère la "
        "transmission du signal jusqu'à 100 fois. Résultat ? Le comportement "
        "habituellement répété devient fluide, rapide, presque automatique. "
        "C'est pourquoi un pianiste n'a plus besoin de penser à chaque note "
        "après des années de pratique."
    )
    pdf.body_text(
        "Le revers de la médaille : les mauvaises habitudes bénéficient du "
        "même mécanisme. Chaque cigarette, chaque scroll compulsif, chaque "
        "verre de trop renforce exactement les mêmes autoroutes neuronales. "
        "Tu ne choisis plus vraiment — tu suis un chemin qui a été creusé "
        "profondément dans ton cerveau par des années de répétition."
    )

    pdf.add_page()

    pdf.section_header("La boucle dopaminergique", 14)
    pdf.body_text(
        "La dopamine est souvent présentée comme \"l'hormone du plaisir\". "
        "C'est inexact — et cette inexactitude explique beaucoup de choses. "
        "La dopamine est l'hormone de l'anticipation du plaisir. Elle est "
        "libérée AVANT la récompense, pas pendant."
    )
    pdf.body_text(
        "La boucle fonctionne ainsi :"
    )
    pdf.bold_text("1. Déclencheur (trigger) — Un stimulus active le circuit.")
    pdf.bold_text("2. Anticipation — La dopamine monte. Tu ressens un manque, une envie.")
    pdf.bold_text("3. Action — Tu agis pour obtenir la récompense.")
    pdf.bold_text("4. Récompense — Soulagement, plaisir bref. La dopamine redescend.")
    pdf.bold_text("5. Répétition — Le cerveau encode : \"fais ça encore\".")
    pdf.ln(3)
    pdf.body_text(
        "Cette boucle est exactement ce qui se passe quand tu scrolles sur "
        "ton téléphone. Le déclencheur ? L'ennui, ou une notification. "
        "L'anticipation ? Peut-être qu'il y a quelque chose d'intéressant. "
        "L'action ? Tu scrolles. La récompense ? Parfois une vidéo drôle, "
        "parfois rien — et ce caractère aléatoire rend la boucle encore plus "
        "addictive (c'est le même principe que les machines à sous)."
    )
    pdf.body_text(
        "Le sucre, l'alcool, le tabac, les jeux vidéos, les réseaux sociaux — "
        "tous exploitent ce même circuit. Ton cerveau n'est pas faible face "
        "à eux. Il est parfaitement rationnel : il poursuit ce qui a déjà "
        "fourni de la dopamine."
    )

    pdf.section_header("Pourquoi ton cerveau préfère le connu — même si c'est douloureux", 14)
    pdf.body_text(
        "Le cerveau est avant tout un organe d'économie d'énergie. Il consomme "
        "20 % de l'énergie de ton corps pour seulement 2 % de ta masse. Pour "
        "survivre, il a appris à optimiser : automatiser le maximum, tester le "
        "minimum."
    )
    pdf.body_text(
        "La nouveauté coûte cher. Apprendre un nouveau comportement demande "
        "de l'attention, de la concentration, de la mémoire de travail — toutes "
        "des ressources cognitives limitées. Le connu, même s'il est douloureux, "
        "est prévisible. Et le prévisible, pour le cerveau, c'est sûr."
    )
    pdf.body_text(
        "C'est pourquoi les gens restent dans des relations toxiques. C'est "
        "pourquoi tu continues de reporter ce projet important. Ce n'est pas "
        "de la bêtise. C'est ton cerveau qui dit : \"Je connais cette douleur. "
        "Je ne connais pas l'autre côté. Je reste ici.\""
    )

    pdf.add_page()

    pdf.section_header("La volonté est une ressource limitée", 14)
    pdf.body_text(
        "Roy Baumeister a popularisé le concept de 'ego depletion' : la "
        "volonté fonctionne comme un muscle. Elle se fatigue. Et elle "
        "dépend du glucose disponible dans le sang."
    )
    pdf.body_text(
        "Des expériences ont montré que des juges rendaient des décisions "
        "plus indulgentes le matin et après les pauses repas — et des "
        "décisions plus sévères, plus automatiques, en fin de journée. "
        "Pas parce qu'ils étaient moins compétents. Parce que leur cortex "
        "préfrontal était épuisé."
    )
    pdf.body_text(
        "C'est pourquoi tu tiens bon toute la journée et tu craques le soir. "
        "Ce n'est pas un manque de caractère. C'est de la biologie. Ton "
        "préfrontal a utilisé toutes ses ressources pour gérer tes emails, "
        "tes décisions, tes interactions sociales. Il ne lui reste plus rien "
        "pour résister à l'automatisme du soir."
    )

    pdf.cream_box(
        "Ce que ton cerveau fait vraiment quand tu 'procrastines'",
        "La procrastination n'est pas de la paresse. C'est ton cerveau qui "
        "choisit consciemment — ou plutôt inconsciemment — le chemin de "
        "moindre résistance énergétique.\n\n"
        "Quand tu dois démarrer une tâche nouvelle ou difficile, ton cortex "
        "préfrontal doit s'activer, planifier, mobiliser des ressources. "
        "Ton cerveau limbique, lui, repère immédiatement qu'il y a une "
        "alternative moins coûteuse : scroller, grignoter, regarder une "
        "vidéo.\n\n"
        "Ce n'est pas un problème de motivation. C'est un problème d'énergie "
        "d'activation — exactement comme en chimie, une réaction ne démarre "
        "pas sans apport initial d'énergie. La solution n'est pas de te "
        "forcer. C'est de réduire le coût d'entrée. On en parle au chapitre 3."
    )


def build_chapter2(pdf: NeuroPDF):
    pdf.add_page()
    pdf.chapter_label("Chapitre 2", "Pourquoi tu bloques")

    pdf.body_text(
        "Maintenant que tu comprends comment fonctionne ton cerveau, "
        "examinons les 5 raisons concrètes pour lesquelles le changement "
        "te résiste. Chacune a une explication neurologique — et une solution."
    )

    # --- Raison 1 ---
    pdf.section_header("Raison 1 — Tu confonds motivation et énergie d'activation", 14)
    pdf.body_text(
        "Voilà ce qu'on nous apprend : \"Si tu es motivé, tu agiras.\" "
        "C'est complètement à l'envers."
    )
    pdf.body_text(
        "La recherche en psychologie comportementale — et notamment les "
        "travaux de Piers Steel sur la procrastination — montre que la "
        "motivation ne précède pas l'action. Elle la SUIT. Le cerveau ne "
        "libère de la dopamine anticipatoire que lorsqu'il a déjà des "
        "preuves que l'action est réalisable et rewarding. Et ces preuves, "
        "il les obtient en... agissant."
    )
    pdf.body_text(
        "En chimie, une réaction n'a pas lieu spontanément : elle a besoin "
        "d'une énergie d'activation initiale — un apport extérieur qui "
        "démarre le processus. Ton cerveau fonctionne pareil. Tu n'as pas "
        "besoin d'être motivé pour commencer. Tu as besoin de commencer "
        "pour devenir motivé."
    )
    pdf.body_text(
        "Exemple concret : tu veux recommencer à faire du sport. Tu attends "
        "d'avoir envie. L'envie ne vient pas. Les semaines passent. Mais "
        "si tu mets tes chaussures de sport et que tu sors marcher 10 "
        "minutes — sans te mettre de pression — ton cerveau enregistre : "
        "\"Ça, c'est faisable. Ça ne fait pas mal.\" La prochaine fois, "
        "la résistance sera un tout petit peu moindre."
    )
    pdf.bold_text(
        "La règle : commence ridiculement petit. L'objectif n'est pas "
        "la performance — c'est de baisser l'énergie d'activation."
    )

    pdf.add_page()

    # --- Raison 2 ---
    pdf.section_header("Raison 2 — Tu combats le symptôme, pas la racine", 14)
    pdf.body_text(
        "La plupart des tentatives de changement échouent parce qu'elles "
        "ciblent le mauvais niveau. Tu essaies d'arrêter de fumer mais "
        "tu ne t'occupes pas de l'anxiété qui déclenche l'envie. Tu veux "
        "manger moins de sucre mais tu n'identifies pas l'ennui ou la "
        "tristesse que le sucre vient compenser. Tu veux arrêter de "
        "procrastiner mais tu ne vois pas la peur de l'échec qui se "
        "cache derrière."
    )
    pdf.body_text(
        "Le comportement problématique est rarement le problème. C'est "
        "une solution — souvent imparfaite, souvent coûteuse — à un besoin "
        "non satisfait. Charles Duhigg, dans 'Le Pouvoir des habitudes', "
        "décrit ça comme la 'récompense cachée' : chaque habitude répond "
        "à un besoin réel. La cigarette répond au besoin de pause, de "
        "sociabilité, de régulation du stress. Si tu enlèves la cigarette "
        "sans adresser ces besoins, ils vont trouver une autre sortie."
    )
    pdf.body_text(
        "Ce que tu dois faire : remonter la chaîne. Avant le comportement "
        "que tu veux changer, qu'est-ce qui se passe ? Quelle émotion ? "
        "Quelle situation ? Quel besoin non satisfait ? C'est là que se "
        "trouve la vraie intervention."
    )
    pdf.bold_text(
        "La règle : cartographie ton déclencheur avant de changer le comportement."
    )

    # --- Raison 3 ---
    pdf.section_header("Raison 3 — Le piège de l'identité", 14)
    pdf.body_text(
        "\"Je suis quelqu'un qui mange mal.\"\n"
        "\"Je n'ai jamais été sportif.\"\n"
        "\"Je suis comme ça, je ne change pas.\""
    )
    pdf.body_text(
        "Ces phrases semblent être des constats. En réalité, ce sont des "
        "programmes. Le cerveau a une tendance puissante à la cohérence "
        "identitaire : il construit littéralement des voies neuronales qui "
        "confirment l'image que tu as de toi-même. Quand tu te dis \"je "
        "ne suis pas quelqu'un de discipliné\", ton cerveau va sélectionner "
        "préférentiellement les preuves qui confirment cette croyance — "
        "et ignorer les contre-exemples."
    )
    pdf.body_text(
        "Ce biais de confirmation identitaire est documenté depuis des "
        "décennies en psychologie sociale. Il est renforcé par la sérotonine "
        ": être cohérent avec son identité perçue procure une récompense "
        "biochimique subtile. Changer d'identité, même pour le mieux, "
        "active l'amygdale comme une menace."
    )
    pdf.body_text(
        "James Clear, dans 'Atomic Habits', propose un renversement élégant : "
        "ne te fixe pas des objectifs de résultats (perdre 10 kg, arrêter "
        "de fumer). Fixe-toi des objectifs d'identité. Pas \"je veux "
        "courir un marathon\" — mais \"je suis quelqu'un qui prend soin "
        "de son corps\". Chaque petite action qui confirme cette nouvelle "
        "identité remodèle les voies neuronales de l'estime de soi."
    )
    pdf.bold_text(
        "La règle : commence par te demander qui tu veux être, pas ce que "
        "tu veux faire."
    )

    pdf.add_page()

    # --- Raison 4 ---
    pdf.section_header("Raison 4 — La fatigue décisionnelle", 14)
    pdf.body_text(
        "Chaque décision que tu prends dans la journée consomme des "
        "ressources cognitives. Ce n'est pas une métaphore — c'est "
        "mesurable. Après une série de décisions difficiles, ton cortex "
        "préfrontal passe en mode économie : il commence à prendre des "
        "décisions plus simples, plus automatiques, plus impulsives."
    )
    pdf.body_text(
        "Barack Obama portait des costumes identiques. Steve Jobs aussi "
        "(son col roulé noir et son jean). Ce n'était pas du narcissisme "
        "ou de l'excentricité. C'était de l'hygiène cognitive : éliminer "
        "les micro-décisions pour préserver le budget cognitif pour ce "
        "qui compte vraiment."
    )
    pdf.body_text(
        "En pratique : si tu essaies de changer un comportement le soir, "
        "après une longue journée de travail, après avoir pris des "
        "dizaines de décisions, tu joues avec un cerveau affaibli. "
        "C'est le même cerveau qui va trouver 17 bonnes raisons de "
        "reporter au lendemain, de craquer sur le paquet de chips, "
        "de scroller encore 20 minutes."
    )
    pdf.bold_text(
        "La règle : programme tes changements importants le matin, "
        "ou après une pause. Préserve ton budget cognitif."
    )

    # --- Raison 5 ---
    pdf.section_header("Raison 5 — La zone de confort est un mécanisme de survie", 14)
    pdf.body_text(
        "L'expression 'zone de confort' suggère quelque chose de douillet, "
        "de paresseux, de choisi. La réalité est plus complexe. Ta zone "
        "de confort est gérée par ton amygdale — la structure en amande "
        "au centre de ton cerveau émotionnel, chargée de détecter les "
        "menaces."
    )
    pdf.body_text(
        "L'amygdale ne distingue pas entre un prédateur et une prise de "
        "parole en public. Elle ne fait pas la différence entre un danger "
        "physique réel et l'idée de changer de métier. Pour elle, tout "
        "ce qui est nouveau et inconnu est potentiellement dangereux. "
        "Elle déclenche le même signal d'alarme : anxiété, tension, "
        "envie de reculer."
    )
    pdf.body_text(
        "Quand tu ressens cette résistance — cette boule dans l'estomac "
        "à l'idée de faire quelque chose de nouveau — tu n'es pas faible. "
        "Ton cerveau fait exactement ce pour quoi il a été conçu par des "
        "millions d'années d'évolution : protéger ta survie en te gardant "
        "dans le territoire connu."
    )
    pdf.body_text(
        "La bonne nouvelle : l'amygdale peut être recalibrée. À chaque "
        "expérience positive dans un territoire nouveau, elle révise "
        "son évaluation. Le danger perçu diminue. La zone de confort "
        "s'agrandit — progressivement, mais réellement."
    )
    pdf.bold_text(
        "La règle : ne combats pas la peur. Dilue-la en petites expositions "
        "progressives. L'amygdale apprend par l'expérience, pas par la raison."
    )

    pdf.ln(4)
    pdf.cream_box(
        "Le test des 3 questions",
        "Face à une résistance, avant de reculer, pose-toi ces trois questions "
        "honnêtes :\n\n"
        "1. Est-ce que j'évite ça parce que c'est vraiment dangereux — "
        "ou parce que c'est simplement inconnu ?\n\n"
        "2. Dans le pire des scénarios réalistes (pas le pire scénario "
        "catastrophique), qu'est-ce qui se passe vraiment ?\n\n"
        "3. Si je reste dans cette situation encore un an sans rien changer, "
        "qu'est-ce que ça me coûte — en énergie, en santé, en opportunités ?\n\n"
        "La troisième question est souvent la plus difficile — et la plus utile. "
        "Le coût de l'immobilisme est systématiquement sous-évalué par notre cerveau, "
        "qui perçoit le statu quo comme gratuit. Il ne l'est jamais."
    )


def build_chapter3(pdf: NeuroPDF):
    pdf.add_page()
    pdf.chapter_label("Chapitre 3", "L'exercice qui change tout")

    pdf.body_text(
        "Tout ce qu'on a vu jusqu'ici est essentiel pour comprendre. Mais "
        "comprendre ne suffit pas. Il faut un outil. Concret, utilisable "
        "maintenant, sans matériel, sans préparation. Voilà la technique "
        "P.O.C. : Pause — Observe — Choisis."
    )
    pdf.body_text(
        "Je l'ai découverte au carrefour de plusieurs pratiques : la "
        "pleine conscience, les thérapies cognitives de troisième vague, "
        "et la neuroscience de la régulation émotionnelle. Elle est simple "
        "en apparence. Mais sa simplicité est précisément sa force : elle "
        "peut s'appliquer partout, tout de suite, dans n'importe quel "
        "contexte."
    )

    pdf.section_header("P — PAUSE", 15)
    pdf.body_text(
        "Quand tu sens qu'une envie arrive — l'envie de fumer, de scroller, "
        "de manger quelque chose que tu voulais éviter, d'éviter une tâche "
        "importante, de réagir impulsivement à un message — arrête-toi "
        "physiquement."
    )
    pdf.body_text(
        "Pas métaphoriquement. Physiquement. Pose ce que tu as dans les "
        "mains. Assieds-toi si tu es debout. Et prends trois respirations "
        "lentes : inspire 4 secondes, expire 6 secondes."
    )
    pdf.body_text(
        "Pourquoi ça marche neurologiquement : la respiration lente et "
        "contrôlée active le système nerveux parasympathique — le frein "
        "physiologique de l'organisme. Elle réduit l'activation de "
        "l'amygdale. Elle augmente le flux sanguin vers le cortex "
        "préfrontal. En littéralement 15 à 30 secondes, tu donnes à ton "
        "cerveau conscient le temps de revenir en ligne."
    )
    pdf.body_text(
        "L'impulsion dure en moyenne entre 90 secondes et 3 minutes. Si "
        "tu ne l'alimentes pas, elle passe. La Pause crée exactement cet "
        "espace entre le déclencheur et la réaction."
    )

    pdf.add_page()

    pdf.section_header("O — OBSERVE", 15)
    pdf.body_text(
        "Maintenant que tu as fait une pause, observe ce qui se passe "
        "en toi. Sans jugement. Sans vouloir changer quoi que ce soit "
        "dans l'immédiat. Juste observer."
    )
    pdf.body_text(
        "Nomme ce que tu ressens : \"Je remarque que je me sens anxieux.\" "
        "\"Je remarque une tension dans ma poitrine.\" \"Je remarque une "
        "envie forte de faire autre chose.\""
    )
    pdf.body_text(
        "Ce simple acte de nommer l'émotion a un effet neurologique "
        "documenté. Une étude de Matthew Lieberman et al. à l'UCLA "
        "(2007, journal Psychological Science) a montré que mettre "
        "des mots sur une émotion réduit l'activation de l'amygdale "
        "de près de 50 %. Pas en la supprimant — mais en la transférant "
        "partiellement vers le cortex préfrontal, où elle peut être "
        "traitée de façon rationnelle."
    )
    pdf.body_text(
        "Cette technique est appelée 'affect labeling' en neuroscience. "
        "Elle est utilisée dans les thérapies EMDR, les protocoles "
        "de stress post-traumatique, et les formations en régulation "
        "émotionnelle. Et tu peux la faire en une phrase, en silence, "
        "n'importe où."
    )
    pdf.body_text(
        "Observe aussi le contexte : où es-tu ? Quelle heure est-il ? "
        "Qu'est-ce qui a déclenché l'envie ? Fatigué ? Stressé ? "
        "Ennuyé ? L'observation régulière te permettra d'identifier "
        "tes schémas — les mêmes contextes qui déclenchent les mêmes "
        "automatismes — et de les anticiper."
    )

    pdf.section_header("C — CHOISIS", 15)
    pdf.body_text(
        "Tu as fait une pause. Tu as observé. Maintenant, et seulement "
        "maintenant, tu choisis."
    )
    pdf.body_text(
        "Attention : choisir ne signifie pas obligatoirement faire le "
        "\"bon\" choix. Il se peut que tu choisisses quand même le "
        "comportement ancien. Et c'est acceptable — à une condition : "
        "que ce soit un choix conscient, pas un automatisme."
    )
    pdf.body_text(
        "La différence est énorme, neurologiquement. Quand tu agis par "
        "automatisme, les voies neuronales de l'habitude se renforcent. "
        "Quand tu agis après une pause consciente — même en prenant la "
        "même décision — tu commences à créer un espace entre le "
        "stimulus et la réponse. Et cet espace est la liberté."
    )
    pdf.body_text(
        "Viktor Frankl, psychiatre et survivant des camps, l'a formulé "
        "ainsi : \"Entre le stimulus et la réponse, il y a un espace. "
        "Dans cet espace se trouve notre pouvoir de choisir notre "
        "réponse. Dans notre réponse se trouvent notre croissance "
        "et notre liberté.\""
    )
    pdf.body_text(
        "Avec le temps — et la pratique régulière du P.O.C. — tes choix "
        "commenceront à diverger de plus en plus des anciens automatismes. "
        "Pas parce que tu te forces. Mais parce que ton cerveau commence "
        "à construire de nouvelles voies neuronales, renforcées à chaque "
        "fois que tu fais une pause avant de réagir."
    )

    pdf.add_page()

    pdf.section_header("Quand et comment l'utiliser", 14)
    pdf.body_text(
        "Le P.O.C. est particulièrement efficace dans trois contextes :"
    )
    pdf.bold_text(
        "Le matin, au réveil. Avant de toucher ton téléphone, avant de "
        "vérifier tes messages, prends 2 minutes. Pause (respiration). "
        "Observe (comment tu te sens physiquement, émotionnellement). "
        "Choisis (comment tu veux aborder cette journée). Ce rituel "
        "matinal calibre ton cortex préfrontal pour la journée."
    )
    pdf.ln(2)
    pdf.bold_text(
        "Au moment du déclencheur. Dès que tu identifies un schéma "
        "déclencheur — une émotion désagréable, une situation stressante, "
        "l'approche d'une tâche difficile — applique le P.O.C. "
        "immédiatement. Plus tu l'appliques tôt dans la chaîne, plus "
        "il est efficace."
    )
    pdf.ln(2)
    pdf.bold_text(
        "Le soir, avant de dormir. Un scan de la journée. Quels "
        "automatismes as-tu repérés ? Où as-tu réussi à faire une pause ? "
        "Où as-tu laissé l'automatisme prendre le dessus ? Pas pour "
        "te juger — pour apprendre."
    )
    pdf.ln(4)

    pdf.body_text(
        "Il ne s'agit pas de faire ça parfaitement. Il s'agit de commencer. "
        "Une fois. Puis une autre. Puis une autre. Le cerveau apprend "
        "par répétition — et maintenant tu utilises ce principe à ton avantage."
    )

    pdf.cream_box(
        "Journal de bord — 7 jours",
        "Reproduis ce tableau dans un carnet ou sur ton téléphone. "
        "Remplis-le chaque soir pendant 7 jours :\n\n"
        "Jour 1\n"
        "  Situation déclencheur : ___________________________\n"
        "  Réaction habituelle : ___________________________\n"
        "  Ce que j'ai choisi : ___________________________\n"
        "  Comment je me sens après : ___________________________\n\n"
        "Jour 2  [répète la structure]\n"
        "Jour 3  [répète la structure]\n"
        "...\n\n"
        "Après 7 jours, relis ton journal. Tu verras apparaître des "
        "schémas — les mêmes contextes, les mêmes émotions, les mêmes "
        "automatismes. C'est ton cerveau qui te montre ses propres "
        "routes. C'est la carte dont tu as besoin pour les modifier."
    )


def build_conclusion(pdf: NeuroPDF):
    pdf.add_page()
    pdf.chapter_label("", "Conclusion")

    pdf.body_text(
        "Tu arrives au bout de ce guide. Et si tu en es là, c'est déjà "
        "un signal : quelque chose en toi cherche à bouger. Cette partie "
        "de toi mérite d'être entendue."
    )

    pdf.body_text(
        "Voilà les trois choses les plus importantes que j'espère que "
        "tu emportes avec toi :"
    )
    pdf.ln(3)

    pdf.bold_text(
        "1. Ton cerveau n'est pas ton ennemi. Il fait exactement ce pour "
        "quoi il a été programmé — survivre, économiser, répéter. Comprendre "
        "ses mécanismes est la première étape pour ne plus en être l'esclave."
    )
    pdf.ln(2)
    pdf.bold_text(
        "2. Tu ne bloques pas par manque de volonté. Tu bloques parce que "
        "tu combats peut-être le mauvais problème, au mauvais moment, avec "
        "les mauvaises stratégies. Changer d'approche change tout."
    )
    pdf.ln(2)
    pdf.bold_text(
        "3. Le premier pas est toujours le plus difficile — et tu viens "
        "de le faire en lisant ce guide jusqu'ici."
    )
    pdf.ln(6)

    pdf.gold_separator()
    pdf.ln(4)

    pdf.body_text(
        "Le changement ne se fait pas dans les grandes décisions. Il se "
        "fait dans les petits moments — la pause avant de réagir, le "
        "choix conscient plutôt que l'automatisme, la question posée "
        "avec honnêteté. Encore et encore. Jusqu'à ce que le nouveau "
        "chemin soit plus tracé que l'ancien."
    )

    pdf.body_text(
        "Ce n'est pas de la magie. C'est de la neuroplasticité."
    )

    pdf.ln(4)
    pdf.italic_text(
        "Dans mon prochain guide — 'Comment casser un automatisme en 24h' "
        "(disponible dans le pack Argent) — je t'emmène encore plus loin : "
        "un protocole en 5 étapes pour identifier, interrompre et "
        "remplacer un automatisme précis en moins d'une journée. "
        "Parce que comprendre, c'est bien. Agir, c'est mieux."
    )

    pdf.add_page()
    pdf.section_header("À propos de Roland Crettaz", 14)
    pdf.body_text(
        "Roland Crettaz est un coach de vie spécialisé en neurosciences "
        "comportementales et en nutrition. Après des années de lutte "
        "personnelle contre les addictions et un TDAH non diagnostiqué "
        "pendant trop longtemps, il a entrepris une reconstruction "
        "profonde — appuyée sur la science, pas sur la volonté seule."
    )
    pdf.body_text(
        "En 2024-2025, il a entrepris un projet fou et sensé à la fois : "
        "marcher 1 900 km de St-Maurice (Suisse) jusqu'à Santiago de "
        "Compostelle. Un chemin de reconstruction physique et intérieure, "
        "documenté en temps réel sur son site."
    )
    pdf.body_text(
        "Ses guides, podcasts et accompagnements individuels s'adressent "
        "à tous ceux qui se sont dit un jour : \"Je sais ce que je "
        "devrais faire — mais je n'y arrive pas.\" Son approche : "
        "ni jugement, ni injonction positive. Juste la biologie, "
        "l'honnêteté, et des outils concrets."
    )
    pdf.ln(6)

    pdf.set_font("DejaVu", "B", 13)
    pdf.set_text_color(*TEAL)
    pdf.cell(USABLE_W, 8, "neuro-odyssee.com", align="C",
             new_x=XPos.LMARGIN, new_y=YPos.NEXT)
    pdf.ln(4)

    pdf.gold_separator()
    pdf.ln(4)

    pdf.set_font("DejaVu", "I", 11)
    pdf.set_text_color(*DARK)
    pdf.multi_cell(USABLE_W, 7,
                   "Soutiens le projet — chaque euro finance des kilomètres "
                   "vers Compostelle\n"
                   "1 900 km de St-Maurice à Santiago — un chemin de reconstruction",
                   align="C", new_x=XPos.LMARGIN, new_y=YPos.NEXT)


# ---------------------------------------------------------------------------
# Main
# ---------------------------------------------------------------------------
def main():
    pdf = NeuroPDF()
    pdf.set_title("Pourquoi tu n'arrives pas à changer (et quoi faire)")
    pdf.set_author("Roland Crettaz — Neuro-Odyssée")
    pdf.set_subject("Neurosciences, changement, habitudes")
    pdf.set_keywords("TDAH, dopamine, habitudes, changement, neurosciences, "
                     "motivation, procrastination")

    build_cover(pdf)
    build_introduction(pdf)
    build_chapter1(pdf)
    build_chapter2(pdf)
    build_chapter3(pdf)
    build_conclusion(pdf)

    output_path = (
        "/Users/martialnicola/Downloads/neuro-odyssee/pdf/"
        "pourquoi-tu-narrive-pas-a-changer.pdf"
    )
    pdf.output(output_path)
    print(f"PDF generated: {output_path}")
    print(f"Total pages: {pdf.page}")


if __name__ == "__main__":
    main()
