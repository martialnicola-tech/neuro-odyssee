import SwiftUI

enum Bone {
    static let light = Color(red: 0.93, green: 0.89, blue: 0.80)
    static let mid   = Color(red: 0.78, green: 0.72, blue: 0.60)
    static let dark  = Color(red: 0.45, green: 0.39, blue: 0.30)

    static var gradient: LinearGradient {
        LinearGradient(colors: [light, mid, dark],
                       startPoint: .topLeading, endPoint: .bottomTrailing)
    }
}

/// Couleurs de lueur des orbites (toucher l'écran pour changer).
enum EyeGlow: CaseIterable {
    case red, green, blue, none

    var color: Color {
        switch self {
        case .red: return .red
        case .green: return .green
        case .blue: return .cyan
        case .none: return .clear
        }
    }
    var next: EyeGlow {
        let all = EyeGlow.allCases
        return all[(all.firstIndex(of: self)! + 1) % all.count]
    }
}
