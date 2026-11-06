import SwiftUI
import StoreKit

/// Achat unique « Collection complète » (StoreKit 2).
/// Créez le produit dans App Store Connect avec cet identifiant (type : non consommable).
@MainActor
final class Store: ObservableObject {
    static let productID = "com.example.skeletonwatch.premium"

    @Published var unlocked = false
    @Published var price: String?
    @Published var busy = false

    private var product: Product?

    init() {
        Task { await load() }
    }

    func load() async {
        product = try? await Product.products(for: [Self.productID]).first
        price = product?.displayPrice
        await refreshEntitlements()
    }

    func refreshEntitlements() async {
        for await result in Transaction.currentEntitlements {
            if case .verified(let t) = result, t.productID == Self.productID, t.revocationDate == nil {
                unlocked = true
            }
        }
    }

    func buy() async {
        guard let product else { return }
        busy = true
        defer { busy = false }
        do {
            let result = try await product.purchase()
            if case .success(let verification) = result, case .verified(let t) = verification {
                await t.finish()
                unlocked = true
            }
        } catch {
            // Achat annulé ou échoué : rien à faire.
        }
    }

    func restore() async {
        try? await AppStore.sync()
        await refreshEntitlements()
    }
}
