import Link from "next/link";
import { getActivePromotions } from "@/lib/data/promotions";
import CopyCodeButton from "@/components/copy-code-button";

export default async function PromotionsPage() {
  const promos = await getActivePromotions();

  return (
    <main className="mx-auto max-w-6xl px-4 py-10">
      <div className="mb-10 text-center">
        <h1 className="font-display text-3xl text-neutral-900">
          🎁 <span className="text-brand-orange">Promotions</span> &amp; Discounts
        </h1>
        <p className="mt-2 text-sm text-neutral-500">Save more on your next order with these active codes.</p>
      </div>

      {promos.length === 0 ? (
        <div className="py-16 text-center">
          <p className="text-4xl">🎁</p>
          <p className="mt-4 text-lg font-semibold text-neutral-700">No Active Promotions</p>
          <Link
            href="/shop"
            className="mt-4 inline-block rounded-full bg-brand-orange px-6 py-2 text-sm font-semibold text-white hover:bg-brand-orange-dark"
          >
            Continue Shopping
          </Link>
        </div>
      ) : (
        <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
          {promos.map((promo) => (
            <div
              key={promo.id}
              className="relative rounded-xl border-2 border-orange-200 bg-gradient-to-br from-orange-50 to-white p-6 shadow-sm transition-shadow hover:shadow-xl"
            >
              <span className="absolute top-4 right-4 rounded-full bg-brand-orange px-3 py-1 text-xs font-bold text-white shadow-lg">
                {promo.discount_type === "percent" ? `${promo.discount_value}% OFF` : `₱${promo.discount_value} OFF`}
              </span>
              <h2 className="pr-20 text-lg font-bold text-neutral-900">{promo.title}</h2>
              {promo.description && <p className="mt-1 text-sm text-neutral-600">{promo.description}</p>}

              <div className="mt-4 flex items-center justify-between rounded-lg border-2 border-dashed border-orange-400 px-3 py-2">
                <span className="font-mono text-lg font-bold text-brand-orange">{promo.code}</span>
                <CopyCodeButton code={promo.code} />
              </div>

              <ul className="mt-4 flex flex-col gap-1 text-xs text-neutral-500">
                {promo.min_purchase > 0 && (
                  <li>• Minimum purchase of ₱{promo.min_purchase.toFixed(2)}</li>
                )}
                {promo.max_discount && <li>• Maximum discount of ₱{promo.max_discount.toFixed(2)}</li>}
                {promo.end_date && <li>• Valid until {new Date(promo.end_date).toLocaleDateString()}</li>}
              </ul>

              <Link
                href="/shop"
                className="mt-5 block rounded-full bg-brand-orange py-2 text-center text-sm font-semibold text-white hover:bg-brand-orange-dark"
              >
                Shop Now
              </Link>
            </div>
          ))}
        </div>
      )}
    </main>
  );
}
