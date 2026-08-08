"use client";

import { useActionState } from "react";
import { submitReview } from "@/lib/actions/reviews";

export default function ReviewForm({ productId }: { productId: number }) {
  const action = submitReview.bind(null, productId);
  const [state, formAction, pending] = useActionState(action, undefined);

  return (
    <form action={formAction} className="admin-form">
      <div>
        <label htmlFor="rating">Rating</label>
        <select id="rating" name="rating" defaultValue="5" required>
          {[5, 4, 3, 2, 1].map((n) => (
            <option key={n} value={n}>
              {n} star{n === 1 ? "" : "s"}
            </option>
          ))}
        </select>
      </div>
      <div>
        <label htmlFor="review_text">Review</label>
        <textarea id="review_text" name="review_text" />
      </div>
      <div>
        <label htmlFor="image">Photo (optional)</label>
        <input id="image" name="image" type="file" accept="image/*" />
      </div>
      {state?.error && <p role="alert">{state.error}</p>}
      <button type="submit" disabled={pending}>
        {pending ? "Submitting…" : "Submit review"}
      </button>
    </form>
  );
}
