import SwiftUI
import WatchKit

/// Faux cadran « squelette » : crâne dont la mâchoire claque à chaque
/// seconde, cage thoracique en fond, heure en police serif.
struct WatchFaceView: View {
    @Environment(\.isLuminanceReduced) private var dimmed
    @State private var glow: EyeGlow = .red

    var body: some View {
        TimelineView(.periodic(from: .now, by: 1)) { context in
            let date = context.date
            let second = Calendar.current.component(.second, from: date)
            let jawOpen: CGFloat = (second % 2 == 0 && !dimmed) ? 1 : 0

            ZStack {
                Color.black.ignoresSafeArea()

                RibCage()
                    .stroke(Bone.gradient, style: StrokeStyle(lineWidth: 3, lineCap: .round))
                    .opacity(dimmed ? 0.18 : 0.35)
                    .padding(.horizontal, 6)

                VStack(spacing: 2) {
                    SkullView(jawOpen: jawOpen,
                              glow: glow.color,
                              glowStrength: dimmed ? 0.3 : 1)
                        .animation(.easeInOut(duration: 0.18), value: jawOpen)
                        .frame(maxHeight: .infinity)

                    Text(date, format: .dateTime.hour().minute())
                        .font(.system(size: 40, weight: .heavy, design: .serif))
                        .foregroundStyle(Bone.gradient)
                        .shadow(color: .black, radius: 2)
                        .minimumScaleFactor(0.6)

                    Text(date, format: .dateTime.weekday(.abbreviated).day().month(.abbreviated))
                        .font(.system(size: 13, weight: .semibold, design: .serif))
                        .foregroundStyle(Bone.mid)
                        .textCase(.uppercase)
                }
                .padding(.top, 4)
            }
        }
        .contentShape(Rectangle())
        .onTapGesture {
            glow = glow.next
            WKInterfaceDevice.current().play(.click)
        }
    }
}

#Preview {
    WatchFaceView()
}
