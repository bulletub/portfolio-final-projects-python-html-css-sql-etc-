"use client";

import { useState, useTransition } from "react";
import { toggleWishlist } from "@/lib/actions/wishlist";

export default function WishlistToggleButton({
  productId,
  initialInWishlist,
}: {
  productId: number;
  initialInWishlist: boolean;
}) {
  const [inWishlist, setInWishlist] = useState(initialInWishlist);
  const [pending, startTransition] = useTransition();

  return (
    <button
      type="button"
      disabled={pending}
      onClick={() => {
        setInWishlist((v) => !v);
        startTransition(() => toggleWishlist(productId));
      }}
      className={`rounded-lg border px-4 py-3 text-sm font-semibold transition-colors disabled:opacity-60 ${
        inWishlist
          ? "border-brand-orange bg-orange-50 text-brand-orange"
          : "border-neutral-300 bg-white text-neutral-600 hover:border-brand-orange hover:text-brand-orange"
      }`}
    >
      {inWishlist ? "♥ Saved to wishlist" : "♡ Save to wishlist"}
    </button>
  );
}
