import Link from "next/link";
import type { Tables } from "@/lib/supabase/types";
import { addToCart } from "@/lib/actions/cart";

export default function ProductGrid({ products }: { products: Tables<"products">[] }) {
  if (products.length === 0) {
    return <p className="text-neutral-500">No products found.</p>;
  }

  return (
    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
      {products.map((product) => (
        <div
          key={product.id}
          className="flex flex-col rounded-xl border border-neutral-100 bg-white p-3 text-center shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-lg"
        >
          <Link href={`/products/${product.id}`} className="flex flex-1 flex-col">
            <div className="flex h-36 items-center justify-center">
              <img
                src={product.image_path ?? "/products/placeholder.svg"}
                alt={product.name}
                className="max-h-36 max-w-full object-contain"
              />
            </div>
            <p className="mt-2 min-h-[2.5rem] text-sm font-semibold text-neutral-800">{product.name}</p>
            <p className="text-lg font-semibold text-brand-orange">₱{product.price.toFixed(2)}</p>
            <p className="text-xs text-neutral-500">
              {product.stock > 0 ? `${product.stock} in stock` : "Out of stock"}
            </p>
          </Link>
          <form action={addToCart.bind(null, product.id, 1)} className="mt-3">
            <button
              type="submit"
              disabled={product.stock <= 0}
              className="w-full rounded-full bg-brand-orange px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-orange-dark disabled:cursor-not-allowed disabled:opacity-50"
            >
              Add to cart
            </button>
          </form>
        </div>
      ))}
    </div>
  );
}
