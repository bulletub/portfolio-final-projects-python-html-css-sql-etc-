"use client";

import { useTransition } from "react";
import { removeFromWishlist } from "@/lib/actions/wishlist";

export default function WishlistRemoveButton({ wishlistId }: { wishlistId: number }) {
  const [pending, startTransition] = useTransition();

  return (
    <button
      type="button"
      disabled={pending}
      onClick={() => startTransition(() => removeFromWishlist(wishlistId))}
    >
      Remove
    </button>
  );
}
