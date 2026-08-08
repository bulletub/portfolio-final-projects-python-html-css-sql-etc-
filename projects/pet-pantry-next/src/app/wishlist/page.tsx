import Link from "next/link";
import { getWishlistItems } from "@/lib/data/wishlist";
import WishlistRemoveButton from "./wishlist-remove-button";

export default async function WishlistPage() {
  const items = await getWishlistItems();

  if (items.length === 0) {
    return (
      <main className="mx-auto max-w-3xl px-4 py-16 text-center">
        <h1 className="text-2xl font-bold text-neutral-900">Your wishlist</h1>
        <p className="mt-3 text-neutral-500">
          Nothing saved yet.{" "}
          <Link href="/shop" className="font-semibold text-brand-orange">
            Browse products
          </Link>
          .
        </p>
      </main>
    );
  }

  return (
    <main className="mx-auto max-w-7xl px-4 py-10">
      <h1 className="mb-6 text-2xl font-bold text-neutral-900">Your wishlist</h1>
      <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        {items.map((item) =>
          item.product ? (
            <div
              key={item.id}
              className="flex flex-col rounded-xl border border-neutral-100 bg-white p-3 text-center shadow-sm transition-shadow hover:shadow-lg"
            >
              <Link href={`/products/${item.product.id}`} className="flex flex-1 flex-col">
                <div className="flex h-36 items-center justify-center">
                  <img
                    src={item.product.image_path ?? "/products/placeholder.svg"}
                    alt={item.product.name}
                    className="max-h-36 max-w-full object-contain"
                  />
                </div>
                <p className="mt-2 min-h-[2.5rem] text-sm font-semibold text-neutral-800">
                  {item.product.name}
                </p>
                <p className="text-lg font-semibold text-brand-orange">₱{item.product.price.toFixed(2)}</p>
              </Link>
              <div className="mt-3">
                <WishlistRemoveButton wishlistId={item.id} />
              </div>
            </div>
          ) : null
        )}
      </div>
    </main>
  );
}
