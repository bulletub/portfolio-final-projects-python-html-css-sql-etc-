"use client";

import { useState, useTransition } from "react";
import { useRouter } from "next/navigation";
import { addToCart } from "@/lib/actions/cart";

export default function AddToCartButton({
  productId,
  stock,
}: {
  productId: number;
  stock: number;
}) {
  const [quantity, setQuantity] = useState(1);
  const [pending, startTransition] = useTransition();
  const [added, setAdded] = useState(false);
  const router = useRouter();

  if (stock <= 0) {
    return <p className="font-semibold text-red-500">Out of stock</p>;
  }

  return (
    <div className="flex items-center gap-3">
      <div className="flex items-center rounded-lg border border-neutral-300">
        <button
          type="button"
          aria-label="Decrease quantity"
          onClick={() => setQuantity((q) => Math.max(1, q - 1))}
          className="bg-transparent px-3 py-2 text-neutral-600"
        >
          −
        </button>
        <input
          type="number"
          min={1}
          max={stock}
          value={quantity}
          onChange={(e) => setQuantity(Number(e.target.value))}
          className="w-12 border-x border-neutral-300 py-2 text-center text-sm"
        />
        <button
          type="button"
          aria-label="Increase quantity"
          onClick={() => setQuantity((q) => Math.min(stock, q + 1))}
          className="bg-transparent px-3 py-2 text-neutral-600"
        >
          +
        </button>
      </div>
      <button
        type="button"
        disabled={pending}
        onClick={() =>
          startTransition(async () => {
            await addToCart(productId, quantity);
            setAdded(true);
            router.refresh();
          })
        }
        className="rounded-lg bg-brand-orange px-6 py-3 text-sm font-semibold text-white hover:bg-brand-orange-dark disabled:opacity-60"
      >
        {pending ? "Adding…" : added ? "Added!" : "Add to Cart"}
      </button>
    </div>
  );
}
