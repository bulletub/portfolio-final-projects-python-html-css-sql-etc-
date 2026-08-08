"use client";

import { useState, useTransition } from "react";
import { deleteProduct } from "@/lib/actions/admin/products";

export default function DeleteProductButton({ productId }: { productId: number }) {
  const [pending, startTransition] = useTransition();
  const [error, setError] = useState<string | null>(null);

  return (
    <>
      <button
        type="button"
        disabled={pending}
        onClick={() => {
          if (!confirm("Delete this product?")) return;
          setError(null);
          startTransition(async () => {
            try {
              await deleteProduct(productId);
            } catch (err) {
              setError(err instanceof Error ? err.message : "Failed to delete product.");
            }
          });
        }}
      >
        Delete
      </button>
      {error && <span role="alert"> {error}</span>}
    </>
  );
}
