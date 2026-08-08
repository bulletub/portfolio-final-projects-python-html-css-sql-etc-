"use client";

import { useActionState } from "react";
import { submitReview } from "@/lib/actions/reviews";

export default function ReviewForm({ productId }: { productId: number }) {
  const action = submitReview.bind(null, productId);
  const [state, formAction, pending] = useActionState(action, undefined);

  return (
    <form action={formAction} className="flex max-w-md flex-col gap-3">
      <div>
        <label htmlFor="rating" className="mb-1 block text-xs font-semibold text-neutral-500">
          Rating
        </label>
        <select
          id="rating"
          name="rating"
          defaultValue="5"
          required
          className="w-full rounded-lg border border-neutral-200 px-3 py-2 text-sm"
        >
          {[5, 4, 3, 2, 1].map((n) => (
            <option key={n} value={n}>
              {n} star{n === 1 ? "" : "s"}
            </option>
          ))}
        </select>
      </div>
      <div>
        <label htmlFor="review_text" className="mb-1 block text-xs font-semibold text-neutral-500">
          Review
        </label>
        <textarea
          id="review_text"
          name="review_text"
          className="w-full rounded-lg border border-neutral-200 px-3 py-2 text-sm"
        />
      </div>
      <div>
        <label htmlFor="image" className="mb-1 block text-xs font-semibold text-neutral-500">
          Photo (optional)
        </label>
        <input id="image" name="image" type="file" accept="image/*" className="text-sm" />
      </div>
      {state?.error && <p className="text-sm text-red-500">{state.error}</p>}
      <button
        type="submit"
        disabled={pending}
        className="w-fit rounded-full bg-brand-orange px-6 py-2 text-sm font-semibold text-white hover:bg-brand-orange-dark disabled:opacity-60"
      >
        {pending ? "Submitting…" : "Submit review"}
      </button>
    </form>
  );
}
