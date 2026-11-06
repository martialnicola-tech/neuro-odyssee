import SwiftUI

enum IndexKind { case baton, roman, arabic, dots }
enum HandKind { case baton, sword, dauphine }
enum Complication { case none, chrono, date, moonPhase, gmt }

/// Description complète d'un cadran analogique.
struct DialStyle {
    var dialTop: Color
    var dialBottom: Color
    var metal: Color            // index, aiguilles
    var lume: Color             // matière lumineuse
    var accent: Color           // trotteuse
    var textColor: Color
    var bezel: Color? = nil     // lunette colorée (nil = pas de lunette)
    var bezelTicks: Bool = false
    var index: IndexKind
    var hands: HandKind
    var complication: Complication
}

/// Un modèle de montre proposé dans l'app.
struct WatchModel: Identifiable {
    enum Kind { case analog(DialStyle), skeleton }
    let id: String
    let name: String
    let tagline: String
    let isPremium: Bool
    let kind: Kind
}

/// Catalogue. Tous les cadrans sont des créations originales,
/// inspirées de genres horlogers (pas de copie de marque).
enum Catalog {
    static let all: [WatchModel] = [
        WatchModel(id: "aviateur", name: "Aviateur", tagline: "Chronographe", isPremium: false,
            kind: .analog(DialStyle(
                dialTop: Color(red: 0.07, green: 0.12, blue: 0.25), dialBottom: Color(red: 0.02, green: 0.04, blue: 0.10),
                metal: Color(red: 0.88, green: 0.88, blue: 0.90), lume: Color(red: 0.85, green: 0.95, blue: 0.80),
                accent: Color(red: 1.0, green: 0.55, blue: 0.1), textColor: .white,
                bezel: Color(red: 0.75, green: 0.75, blue: 0.78), bezelTicks: true,
                index: .baton, hands: .sword, complication: .chrono))),
        WatchModel(id: "abysse", name: "Abysse", tagline: "Plongeuse 300 m", isPremium: false,
            kind: .analog(DialStyle(
                dialTop: Color(red: 0.03, green: 0.03, blue: 0.04), dialBottom: .black,
                metal: Color(red: 0.92, green: 0.92, blue: 0.92), lume: Color(red: 0.55, green: 1.0, blue: 0.85),
                accent: Color(red: 0.95, green: 0.2, blue: 0.15), textColor: .white,
                bezel: Color(red: 0.05, green: 0.2, blue: 0.5), bezelTicks: true,
                index: .dots, hands: .baton, complication: .date))),
        WatchModel(id: "heritage", name: "Héritage", tagline: "Classique, phase de lune", isPremium: true,
            kind: .analog(DialStyle(
                dialTop: Color(red: 0.96, green: 0.93, blue: 0.85), dialBottom: Color(red: 0.86, green: 0.80, blue: 0.68),
                metal: Color(red: 0.12, green: 0.18, blue: 0.40), lume: .clear,
                accent: Color(red: 0.12, green: 0.18, blue: 0.40), textColor: Color(red: 0.12, green: 0.1, blue: 0.1),
                bezel: nil, bezelTicks: false,
                index: .roman, hands: .dauphine, complication: .moonPhase))),
        WatchModel(id: "meridien", name: "Méridien", tagline: "GMT deux fuseaux", isPremium: true,
            kind: .analog(DialStyle(
                dialTop: Color(red: 0.02, green: 0.18, blue: 0.10), dialBottom: Color(red: 0.0, green: 0.06, blue: 0.03),
                metal: Color(red: 0.95, green: 0.85, blue: 0.55), lume: Color(red: 0.95, green: 0.95, blue: 0.7),
                accent: Color(red: 1.0, green: 0.8, blue: 0.2), textColor: Color(red: 0.95, green: 0.85, blue: 0.55),
                bezel: Color(red: 0.08, green: 0.08, blue: 0.08), bezelTicks: true,
                index: .arabic, hands: .sword, complication: .gmt))),
        WatchModel(id: "squelette", name: "Squelette", tagline: "Mâchoire claquante", isPremium: true,
            kind: .skeleton),
    ]
}
