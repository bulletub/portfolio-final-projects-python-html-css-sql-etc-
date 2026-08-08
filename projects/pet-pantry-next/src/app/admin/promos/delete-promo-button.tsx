"use client";

import { useState, useTransition } from "react";
import { deletePromo } from "@/lib/actions/admin/promos";

export default function DeletePromoButton({ promoId }: { promoId: number }) {
  const [pending, startTransition] = useTransition();
  const [error, setError] = useState<string | null>(null);

  return (
    <>
      <button
        type="button"
        disabled={pending}
        onClick={() => {
          if (!confirm("Delete this promo?")) return;
          setError(null);
          startTransition(async () => {
            try {
              await deletePromo(promoId);
            } catch (err) {
              setError(err instanceof Error ? err.message : "Failed to delete promo.");
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
