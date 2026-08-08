type Review = {
  id: number;
  rating: number;
  review_text: string | null;
  image_path: string | null;
  created_at: string;
  reviewer: { name: string | null } | null;
};

export default function ReviewList({ reviews }: { reviews: Review[] }) {
  if (reviews.length === 0) {
    return <p className="text-sm text-neutral-500">No reviews yet.</p>;
  }

  const average = reviews.reduce((sum, r) => sum + r.rating, 0) / reviews.length;

  return (
    <div>
      <p className="mb-4 text-sm text-neutral-600">
        <span className="font-semibold text-brand-orange">{average.toFixed(1)} / 5</span> · {reviews.length}{" "}
        review{reviews.length === 1 ? "" : "s"}
      </p>
      <div className="flex flex-col gap-4">
        {reviews.map((review) => (
          <div key={review.id} className="flex gap-3 border-b border-neutral-100 pb-4">
            {review.image_path && (
              <img src={review.image_path} alt="" className="h-16 w-16 flex-shrink-0 rounded-lg object-cover" />
            )}
            <div>
              <p className="text-brand-gold text-sm">
                {"★".repeat(review.rating)}
                <span className="text-neutral-300">{"★".repeat(5 - review.rating)}</span>
              </p>
              {review.review_text && <p className="mt-1 text-sm text-neutral-700">{review.review_text}</p>}
              <p className="mt-1 text-xs text-neutral-400">{review.reviewer?.name ?? "Anonymous"}</p>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
