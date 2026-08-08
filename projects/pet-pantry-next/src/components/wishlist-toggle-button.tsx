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
    >
      {inWishlist ? "♥ Saved to wishlist" : "♡ Save to wishlist"}
    </button>
  );
}
