"use client";

import { useRef } from "react";
import Link from "next/link";
import type { Tables } from "@/lib/supabase/types";

export default function ProductRow({ products }: { products: Tables<"products">[] }) {
  const scrollerRef = useRef<HTMLDivElement>(null);

  function scroll(direction: 1 | -1) {
    scrollerRef.current?.scrollBy({ left: direction * 320, behavior: "smooth" });
  }

  if (products.length === 0) {
    return <p className="text-center text-neutral-500">No products yet.</p>;
  }

  return (
    <div className="relative">
      <button
        type="button"
        aria-label="Previous"
        onClick={() => scroll(-1)}
        className="absolute top-1/2 -left-4 z-10 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-brand-orange text-white shadow hover:bg-brand-orange-dark"
      >
        ‹
      </button>
      <div ref={scrollerRef} className="flex snap-x gap-4 overflow-x-auto scroll-smooth px-2 py-2">
        {products.map((product) => (
          <Link
            key={product.id}
            href={`/products/${product.id}`}
            className="w-1/2 flex-shrink-0 snap-start rounded-lg border border-neutral-100 p-4 text-center shadow-sm transition-shadow hover:shadow-lg md:w-1/4"
          >
            <div className="flex h-48 items-center justify-center">
              <img
                src={product.image_path ?? "/products/placeholder.svg"}
                alt={product.name}
                className="max-h-48 max-w-full object-contain"
              />
            </div>
            <p className="mt-2 text-xs font-semibold text-neutral-700">{product.name}</p>
            <p className="text-lg font-semibold text-brand-orange">₱{product.price.toFixed(2)}</p>
          </Link>
        ))}
      </div>
      <button
        type="button"
        aria-label="Next"
        onClick={() => scroll(1)}
        className="absolute top-1/2 -right-4 z-10 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-brand-orange text-white shadow hover:bg-brand-orange-dark"
      >
        ›
      </button>
    </div>
  );
}
