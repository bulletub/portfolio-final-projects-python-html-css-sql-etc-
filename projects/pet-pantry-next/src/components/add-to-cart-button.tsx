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
    return <p>Out of stock</p>;
  }

  return (
    <div>
      <input
        type="number"
        min={1}
        max={stock}
        value={quantity}
        onChange={(e) => setQuantity(Number(e.target.value))}
      />
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
      >
        {pending ? "Adding…" : added ? "Added!" : "Add to cart"}
      </button>
    </div>
  );
}
