"use client";

import { useTransition } from "react";
import { setReviewStatus } from "@/lib/actions/admin/reviews";

type Review = {
  id: number;
  rating: number;
  review_text: string | null;
  image_path: string | null;
  status: string;
  created_at: string;
  product: { name: string } | null;
  reviewer: { name: string | null; email: string | null } | null;
};

export default function ReviewModerationRow({ review }: { review: Review }) {
  const [pending, startTransition] = useTransition();

  return (
    <div className="order-card">
      <p>
        {review.product?.name ?? "Unknown product"} — <span className="status-badge">{review.status}</span>
      </p>
      <p>
        {"★".repeat(review.rating)}
        {"☆".repeat(5 - review.rating)} by {review.reviewer?.name ?? "—"} ({review.reviewer?.email ?? "—"})
      </p>
      {review.review_text && <p>{review.review_text}</p>}
      {review.image_path && (
        <img src={review.image_path} alt="" style={{ width: 120, borderRadius: 8 }} />
      )}
      {review.status === "pending" && (
        <p>
          <button
            type="button"
            disabled={pending}
            onClick={() => startTransition(() => setReviewStatus(review.id, "approved"))}
          >
            Approve
          </button>{" "}
          <button
            type="button"
            disabled={pending}
            onClick={() => startTransition(() => setReviewStatus(review.id, "rejected"))}
          >
            Reject
          </button>
        </p>
      )}
    </div>
  );
}
