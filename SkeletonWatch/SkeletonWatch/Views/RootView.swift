import SwiftUI
import WatchKit

/// Carrousel vertical des montres (tourner la Digital Crown).
struct RootView: View {
    @StateObject private var store = Store()
    @AppStorage("selectedWatch") private var selected = Catalog.all[0].id

    var body: some View {
        TabView(selection: $selected) {
            ForEach(Catalog.all) { model in
                WatchPage(model: model, locked: model.isPremium && !store.unlocked)
                    .tag(model.id)
            }
        }
        .tabViewStyle(.verticalPage)
        .environmentObject(store)
        .onChange(of: selected) { _, _ in WKInterfaceDevice.current().play(.click) }
    }
}

private struct WatchPage: View {
    let model: WatchModel
    let locked: Bool
    @EnvironmentObject private var store: Store
    @State private var showStore = false

    var body: some View {
        ZStack {
            Color.black.ignoresSafeArea()
            switch model.kind {
            case .analog(let style): AnalogWatchFace(style: style).padding(2)
            case .skeleton: WatchFaceView()
            }
            if locked {
                Color.black.opacity(0.55).ignoresSafeArea()
                VStack(spacing: 6) {
                    Image(systemName: "lock.fill").font(.title3)
                    Text(model.name).font(.headline)
                    Button(store.price.map { "Débloquer \($0)" } ?? "Débloquer") { showStore = true }
                        .buttonStyle(.borderedProminent).tint(.orange)
                }
            }
        }
        .sheet(isPresented: $showStore) { UnlockView() }
    }
}

private struct UnlockView: View {
    @EnvironmentObject private var store: Store
    @Environment(\.dismiss) private var dismiss

    var body: some View {
        ScrollView {
            VStack(spacing: 8) {
                Text("Collection complète").font(.headline)
                Text("Héritage, Méridien, Squelette et les futures montres.")
                    .font(.footnote).multilineTextAlignment(.center)
                Button(store.price ?? "Acheter") {
                    Task { await store.buy(); if store.unlocked { dismiss() } }
                }
                .buttonStyle(.borderedProminent).tint(.orange).disabled(store.busy)
                Button("Restaurer l'achat") { Task { await store.restore(); if store.unlocked { dismiss() } } }
                    .font(.footnote)
            }
        }
    }
}
