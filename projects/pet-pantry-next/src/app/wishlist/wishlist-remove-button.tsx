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
      className="w-full rounded-full border border-neutral-300 bg-white px-3 py-1.5 text-xs font-semibold text-neutral-600 hover:border-red-400 hover:text-red-500 disabled:opacity-60"
    >
      Remove
    </button>
  );
}
