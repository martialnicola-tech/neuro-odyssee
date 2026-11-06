import SwiftUI

struct SkullView: View {
    var jawOpen: CGFloat        // 0...1
    var glow: Color
    var glowStrength: Double    // 0...1

    var body: some View {
        GeometryReader { geo in
            let s = min(geo.size.width, geo.size.height)
            ZStack {
                // Mandibule derrière le crâne, s'ouvre vers le bas
                Mandible()
                    .fill(Bone.gradient)
                    .overlay(Mandible().stroke(Bone.dark, lineWidth: 0.8))
                    .offset(y: jawOpen * s * 0.07)

                // Crâne avec orbites et nez découpés (even-odd)
                Group {
                    CutoutSkull()
                        .fill(Bone.gradient, style: FillStyle(eoFill: true))
                    CutoutSkull()
                        .stroke(Bone.dark, lineWidth: 0.8)
                    Cracks().stroke(Bone.dark.opacity(0.7), lineWidth: 0.7)
                    UpperTeeth().fill(Bone.light)
                    UpperTeeth().stroke(Bone.dark, lineWidth: 0.5)
                }

                // Lueur dans les orbites
                EyeSockets()
                    .fill(RadialGradient(colors: [glow, glow.opacity(0)],
                                         center: .center, startRadius: 0, endRadius: s * 0.12))
                    .opacity(glowStrength)
                    .blendMode(.screen)
            }
            .frame(width: s, height: s)
            .position(x: geo.size.width / 2, y: geo.size.height / 2)
        }
        .aspectRatio(1, contentMode: .fit)
    }
}

/// Crâne + trous (orbites, nez) pour un remplissage even-odd.
private struct CutoutSkull: Shape {
    func path(in rect: CGRect) -> Path {
        var p = Cranium().path(in: rect)
        p.addPath(EyeSockets().path(in: rect))
        p.addPath(NasalCavity().path(in: rect))
        return p
    }
}
