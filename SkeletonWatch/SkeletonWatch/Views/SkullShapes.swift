import SwiftUI

/// Toutes les formes sont dessinées dans un carré de 100 x 100,
/// puis mises à l'échelle du rect fourni.
private func scaled(_ rect: CGRect) -> (CGFloat, CGFloat) -> CGPoint {
    let s = min(rect.width, rect.height) / 100
    return { x, y in CGPoint(x: rect.minX + x * s, y: rect.minY + y * s) }
}

/// Crâne + maxillaire supérieur.
struct Cranium: Shape {
    func path(in rect: CGRect) -> Path {
        let p = scaled(rect)
        var path = Path()
        path.move(to: p(18, 52))
        path.addCurve(to: p(50, 2), control1: p(14, 20), control2: p(32, 2))
        path.addCurve(to: p(82, 52), control1: p(68, 2), control2: p(86, 20))
        path.addCurve(to: p(74, 68), control1: p(84, 58), control2: p(80, 64))
        path.addLine(to: p(68, 76))
        path.addLine(to: p(32, 76))
        path.addLine(to: p(26, 68))
        path.addCurve(to: p(18, 52), control1: p(20, 64), control2: p(16, 58))
        path.closeSubpath()
        return path
    }
}

/// Orbites (à découper dans le crâne).
struct EyeSockets: Shape {
    func path(in rect: CGRect) -> Path {
        let p = scaled(rect)
        var path = Path()
        for (cx, angle) in [(33.0, 10.0), (67.0, -10.0)] {
            var e = Path(ellipseIn: CGRect(x: -11, y: -9, width: 22, height: 18))
            e = e.applying(CGAffineTransform(rotationAngle: angle * .pi / 180))
            let c = p(cx, 47)
            path.addPath(e, transform: CGAffineTransform(translationX: c.x, y: c.y)
                .scaledBy(x: rect.width / 100, y: rect.height / 100))
        }
        return path
    }
}

/// Cavité nasale (cœur inversé).
struct NasalCavity: Shape {
    func path(in rect: CGRect) -> Path {
        let p = scaled(rect)
        var path = Path()
        path.move(to: p(50, 57))
        path.addQuadCurve(to: p(43, 71), control: p(44, 62))
        path.addQuadCurve(to: p(50, 68), control: p(47, 67))
        path.addQuadCurve(to: p(57, 71), control: p(53, 67))
        path.addQuadCurve(to: p(50, 57), control: p(56, 62))
        path.closeSubpath()
        return path
    }
}

/// Dents du haut (accrochées au bas du crâne).
struct UpperTeeth: Shape {
    func path(in rect: CGRect) -> Path {
        let p = scaled(rect)
        var path = Path()
        let n = 8
        for i in 0..<n {
            let x = 31.0 + Double(i) * 4.75
            let r = CGRect(origin: p(x, 74), size: CGSize(width: 4.2 * rect.width / 100,
                                                          height: 8 * rect.height / 100))
            path.addRoundedRect(in: r, cornerSize: CGSize(width: 1.2, height: 1.2))
        }
        return path
    }
}

/// Mandibule mobile (dents du bas incluses).
struct Mandible: Shape {
    func path(in rect: CGRect) -> Path {
        let p = scaled(rect)
        var path = Path()
        path.move(to: p(27, 78))
        path.addLine(to: p(73, 78))
        path.addCurve(to: p(66, 98), control1: p(74, 86), control2: p(72, 94))
        path.addLine(to: p(34, 98))
        path.addCurve(to: p(27, 78), control1: p(28, 94), control2: p(26, 86))
        path.closeSubpath()
        // dents du bas
        for i in 0..<8 {
            let x = 31.0 + Double(i) * 4.75
            let r = CGRect(origin: p(x, 78), size: CGSize(width: 4.2 * rect.width / 100,
                                                          height: 6 * rect.height / 100))
            path.addRoundedRect(in: r, cornerSize: CGSize(width: 1, height: 1))
        }
        return path
    }
}

/// Fissures du crâne, pour le réalisme.
struct Cracks: Shape {
    func path(in rect: CGRect) -> Path {
        let p = scaled(rect)
        var path = Path()
        path.move(to: p(58, 5))
        path.addLine(to: p(55, 14)); path.addLine(to: p(60, 22)); path.addLine(to: p(57, 30))
        path.move(to: p(55, 14)); path.addLine(to: p(48, 19))
        path.move(to: p(24, 40)); path.addLine(to: p(27, 33)); path.addLine(to: p(25, 27))
        return path
    }
}

/// Cage thoracique (sternum + 5 paires de côtes).
struct RibCage: Shape {
    func path(in rect: CGRect) -> Path {
        let p = scaled(rect)
        var path = Path()
        path.move(to: p(50, 4)); path.addLine(to: p(50, 92))
        for i in 0..<5 {
            let y = 10.0 + Double(i) * 16
            let reach = 40.0 - Double(abs(i - 2)) * 5   // côtes du milieu plus larges
            for side in [-1.0, 1.0] {
                path.move(to: p(50, y))
                path.addQuadCurve(to: p(50 + side * reach, y + 14),
                                  control: p(50 + side * reach * 0.9, y - 8))
            }
        }
        return path
    }
}
