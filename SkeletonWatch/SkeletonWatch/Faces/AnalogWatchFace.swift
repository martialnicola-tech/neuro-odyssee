import SwiftUI

/// Rendu d'un cadran analogique avec Canvas.
struct AnalogWatchFace: View {
    let style: DialStyle
    @Environment(\.isLuminanceReduced) private var dimmed

    var body: some View {
        TimelineView(.animation) { ctx in
            Canvas { gc, size in
                draw(&gc, size: size, date: ctx.date)
            }
        }
        .aspectRatio(1, contentMode: .fit)
    }

    // MARK: - Temps

    private struct Parts {
        var hour: Double, minute: Double, second: Double, gmt24: Double, day: Int
    }

    private func parts(_ date: Date) -> Parts {
        let cal = Calendar.current
        let c = cal.dateComponents([.hour, .minute, .second, .nanosecond, .day], from: date)
        let sec = Double(c.second ?? 0) + Double(c.nanosecond ?? 0) / 1e9
        let min = Double(c.minute ?? 0) + sec / 60
        let hour = Double((c.hour ?? 0) % 12) + min / 60
        var utc = Calendar(identifier: .gregorian)
        utc.timeZone = TimeZone(identifier: "UTC")!
        let u = utc.dateComponents([.hour, .minute], from: date)
        let gmt = Double(u.hour ?? 0) + Double(u.minute ?? 0) / 60
        return Parts(hour: hour, minute: min, second: sec, gmt24: gmt, day: c.day ?? 1)
    }

    private func moonPhase(_ date: Date) -> Double {
        let ref = Date(timeIntervalSince1970: 947_182_440)   // nouvelle lune 6 janv. 2000
        let syn = 29.530588
        let d = date.timeIntervalSince(ref) / 86_400
        var p = d.truncatingRemainder(dividingBy: syn) / syn
        if p < 0 { p += 1 }
        return p
    }

    // MARK: - Dessin

    private func draw(_ gc: inout GraphicsContext, size: CGSize, date: Date) {
        let s = min(size.width, size.height)
        let c = CGPoint(x: size.width / 2, y: size.height / 2)
        let R = s / 2
        let hasBezel = style.bezel != nil
        let rd = hasBezel ? R * 0.80 : R * 0.96          // rayon du cadran
        let t = parts(date)

        // Lunette
        if let bezel = style.bezel {
            gc.fill(Path(ellipseIn: CGRect(x: c.x - R, y: c.y - R, width: 2 * R, height: 2 * R)),
                    with: .color(bezel))
            if style.bezelTicks {
                for i in 0..<60 {
                    let major = i % 5 == 0
                    tick(&gc, c: c, angle: Double(i) * 6, from: R * 0.90, to: R * (major ? 0.97 : 0.94),
                         width: major ? 1.6 : 0.8, color: .white.opacity(0.9))
                }
                // triangle lumineux à 12 h
                var tri = Path()
                tri.move(to: CGPoint(x: c.x, y: c.y - R * 0.99))
                tri.addLine(to: CGPoint(x: c.x - R * 0.05, y: c.y - R * 0.90))
                tri.addLine(to: CGPoint(x: c.x + R * 0.05, y: c.y - R * 0.90))
                tri.closeSubpath()
                gc.fill(tri, with: .color(style.lume == .clear ? .white : style.lume))
            }
        }

        // Cadran
        let dialRect = CGRect(x: c.x - rd, y: c.y - rd, width: 2 * rd, height: 2 * rd)
        gc.fill(Path(ellipseIn: dialRect),
                with: .linearGradient(Gradient(colors: [style.dialTop, style.dialBottom]),
                                      startPoint: CGPoint(x: c.x, y: c.y - rd),
                                      endPoint: CGPoint(x: c.x, y: c.y + rd)))
        // Guillochage discret
        for k in 1...3 {
            let r = rd * (0.30 + 0.22 * CGFloat(k))
            gc.stroke(Path(ellipseIn: CGRect(x: c.x - r, y: c.y - r, width: 2 * r, height: 2 * r)),
                      with: .color(style.metal.opacity(0.06)), lineWidth: 0.6)
        }
        gc.stroke(Path(ellipseIn: dialRect), with: .color(style.metal.opacity(0.5)), lineWidth: 1)

        // Chemin de fer (minutes)
        for i in 0..<60 where i % 5 != 0 {
            tick(&gc, c: c, angle: Double(i) * 6, from: rd * 0.94, to: rd * 0.98,
                 width: 0.6, color: style.textColor.opacity(0.7))
        }

        // Index
        var skip: Set<Int> = []
        switch style.complication {
        case .chrono: skip = [3, 6, 9]
        case .date: skip = [3]
        case .moonPhase: skip = [6]
        default: break
        }
        for h in 1...12 where !skip.contains(h % 12 == 0 ? 12 : h) {
            drawIndex(&gc, c: c, hour: h, rd: rd)
        }

        // Complications
        switch style.complication {
        case .chrono: drawChrono(&gc, c: c, rd: rd, t: t)
        case .date: drawDate(&gc, c: c, rd: rd, day: t.day)
        case .moonPhase: drawMoon(&gc, c: c, rd: rd, phase: moonPhase(date))
        case .gmt, .none: break
        }

        // GMT : aiguille 24 h
        if style.complication == .gmt {
            hand(gc, c: c, angle: t.gmt24 / 24 * 360, length: rd * 0.70, width: rd * 0.035,
                 tail: rd * 0.10, kind: .baton, color: style.accent, lume: nil)
            var tip = Path()
            tip.move(to: CGPoint(x: 0, y: -rd * 0.78))
            tip.addLine(to: CGPoint(x: -rd * 0.05, y: -rd * 0.68))
            tip.addLine(to: CGPoint(x: rd * 0.05, y: -rd * 0.68))
            tip.closeSubpath()
            var g = gc; g.translateBy(x: c.x, y: c.y); g.rotate(by: .degrees(t.gmt24 / 24 * 360))
            g.fill(tip, with: .color(style.accent))
            drawDate(&gc, c: c, rd: rd, day: t.day)
        }

        // Aiguilles
        let lume: Color? = style.lume == .clear ? nil : style.lume
        hand(gc, c: c, angle: t.hour / 12 * 360, length: rd * 0.50, width: rd * 0.10,
             tail: rd * 0.08, kind: style.hands, color: style.metal, lume: lume)
        hand(gc, c: c, angle: t.minute / 60 * 360, length: rd * 0.80, width: rd * 0.075,
             tail: rd * 0.10, kind: style.hands, color: style.metal, lume: lume)
        if !dimmed {
            hand(gc, c: c, angle: t.second / 60 * 360, length: rd * 0.86, width: rd * 0.014,
                 tail: rd * 0.20, kind: .baton, color: style.accent, lume: nil)
        }
        let cap = rd * 0.04
        gc.fill(Path(ellipseIn: CGRect(x: c.x - cap, y: c.y - cap, width: 2 * cap, height: 2 * cap)),
                with: .color(style.accent))
    }

    // MARK: - Éléments

    private func tick(_ gc: inout GraphicsContext, c: CGPoint, angle: Double,
                      from r1: CGFloat, to r2: CGFloat, width: CGFloat, color: Color) {
        let a = (angle - 90) * .pi / 180
        var p = Path()
        p.move(to: CGPoint(x: c.x + cos(a) * r1, y: c.y + sin(a) * r1))
        p.addLine(to: CGPoint(x: c.x + cos(a) * r2, y: c.y + sin(a) * r2))
        gc.stroke(p, with: .color(color), lineWidth: width)
    }

    private func drawIndex(_ gc: inout GraphicsContext, c: CGPoint, hour: Int, rd: CGFloat) {
        let angle = Double(hour) * 30
        let a = (angle - 90) * .pi / 180
        let lume = style.lume == .clear ? style.metal : style.lume
        switch style.index {
        case .baton:
            tick(&gc, c: c, angle: angle, from: rd * 0.74, to: rd * 0.90, width: rd * 0.06, color: style.metal)
            tick(&gc, c: c, angle: angle, from: rd * 0.76, to: rd * 0.88, width: rd * 0.03, color: lume)
        case .dots:
            let r = hour % 3 == 0 ? rd * 0.07 : rd * 0.05
            let p = CGPoint(x: c.x + cos(a) * rd * 0.82, y: c.y + sin(a) * rd * 0.82)
            gc.fill(Path(ellipseIn: CGRect(x: p.x - r, y: p.y - r, width: 2 * r, height: 2 * r)),
                    with: .color(style.metal))
            gc.fill(Path(ellipseIn: CGRect(x: p.x - r * 0.6, y: p.y - r * 0.6, width: 1.2 * r, height: 1.2 * r)),
                    with: .color(lume))
        case .roman, .arabic:
            let roman = ["I","II","III","IIII","V","VI","VII","VIII","IX","X","XI","XII"]
            let label = style.index == .roman ? roman[hour - 1] : "\(hour)"
            let design: Font.Design = style.index == .roman ? .serif : .default
            let text = Text(label)
                .font(.system(size: rd * (style.index == .roman ? 0.15 : 0.17), weight: .semibold, design: design))
                .foregroundColor(style.index == .roman ? style.metal : style.metal)
            let resolved = gc.resolve(text)
            gc.draw(resolved, at: CGPoint(x: c.x + cos(a) * rd * 0.76, y: c.y + sin(a) * rd * 0.76),
                    anchor: .center)
        }
    }

    private func drawDate(_ gc: inout GraphicsContext, c: CGPoint, rd: CGFloat, day: Int) {
        let rect = CGRect(x: c.x + rd * 0.46, y: c.y - rd * 0.09, width: rd * 0.30, height: rd * 0.18)
        gc.fill(Path(roundedRect: rect, cornerRadius: 2), with: .color(.white))
        gc.stroke(Path(roundedRect: rect, cornerRadius: 2), with: .color(style.metal), lineWidth: 1)
        let t = Text("\(day)").font(.system(size: rd * 0.15, weight: .semibold)).foregroundColor(.black)
        gc.draw(gc.resolve(t), at: CGPoint(x: rect.midX, y: rect.midY), anchor: .center)
    }

    private func drawMoon(_ gc: inout GraphicsContext, c: CGPoint, rd: CGFloat, phase: Double) {
        let center = CGPoint(x: c.x, y: c.y + rd * 0.42)
        let r = rd * 0.20
        let aperture = Path(ellipseIn: CGRect(x: center.x - r, y: center.y - r, width: 2 * r, height: 2 * r))
        gc.fill(aperture, with: .color(Color(red: 0.05, green: 0.08, blue: 0.25)))
        var inner = gc
        inner.clip(to: aperture)
        let spacing = r * 1.7
        let base = (CGFloat(phase) - 0.5) * spacing
        for k in -1...1 {
            let x = center.x + base + CGFloat(k) * spacing
            let mr = r * 0.62
            inner.fill(Path(ellipseIn: CGRect(x: x - mr, y: center.y - mr, width: 2 * mr, height: 2 * mr)),
                       with: .color(Color(red: 0.95, green: 0.85, blue: 0.55)))
        }
        gc.stroke(aperture, with: .color(style.metal), lineWidth: 1)
    }

    private func drawChrono(_ gc: inout GraphicsContext, c: CGPoint, rd: CGFloat, t: Parts) {
        let centers: [(CGPoint, Double)] = [
            (CGPoint(x: c.x - rd * 0.40, y: c.y), t.second / 60 * 360),            // petite seconde
            (CGPoint(x: c.x + rd * 0.40, y: c.y), (t.minute.truncatingRemainder(dividingBy: 30)) / 30 * 360),
            (CGPoint(x: c.x, y: c.y + rd * 0.42), t.hour / 12 * 360),
        ]
        let r = rd * 0.24
        for (p, angle) in centers {
            let rect = CGRect(x: p.x - r, y: p.y - r, width: 2 * r, height: 2 * r)
            gc.fill(Path(ellipseIn: rect), with: .color(.black.opacity(0.35)))
            gc.stroke(Path(ellipseIn: rect), with: .color(style.metal.opacity(0.8)), lineWidth: 1)
            for i in 0..<12 {
                tick(&gc, c: p, angle: Double(i) * 30, from: r * 0.80, to: r * 0.95,
                     width: 0.8, color: style.textColor.opacity(0.8))
            }
            hand(gc, c: p, angle: angle, length: r * 0.85, width: r * 0.10, tail: r * 0.15,
                 kind: .baton, color: style.accent, lume: nil)
        }
    }

    private func hand(_ gc: GraphicsContext, c: CGPoint, angle: Double, length: CGFloat, width: CGFloat,
                      tail: CGFloat, kind: HandKind, color: Color, lume: Color?) {
        var g = gc
        g.translateBy(x: c.x, y: c.y)
        g.rotate(by: .degrees(angle))
        var body = Path()
        switch kind {
        case .baton:
            body = Path(roundedRect: CGRect(x: -width / 2, y: -length, width: width, height: length + tail),
                        cornerRadius: width * 0.2)
        case .sword:
            body.move(to: CGPoint(x: 0, y: -length))
            body.addLine(to: CGPoint(x: width / 2, y: -length * 0.2))
            body.addLine(to: CGPoint(x: width * 0.3, y: tail))
            body.addLine(to: CGPoint(x: -width * 0.3, y: tail))
            body.addLine(to: CGPoint(x: -width / 2, y: -length * 0.2))
            body.closeSubpath()
        case .dauphine:
            body.move(to: CGPoint(x: 0, y: -length))
            body.addLine(to: CGPoint(x: width / 2, y: -length * 0.3))
            body.addLine(to: CGPoint(x: 0, y: tail))
            body.addLine(to: CGPoint(x: -width / 2, y: -length * 0.3))
            body.closeSubpath()
        }
        g.fill(body, with: .color(color))
        g.stroke(body, with: .color(.black.opacity(0.35)), lineWidth: 0.5)
        if let lume {
            let r = CGRect(x: -width * 0.18, y: -length * 0.85, width: width * 0.36, height: length * 0.60)
            g.fill(Path(roundedRect: r, cornerRadius: width * 0.1), with: .color(lume))
        }
    }
}
