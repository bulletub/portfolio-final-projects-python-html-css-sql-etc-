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
    return <p>No reviews yet.</p>;
  }

  return (
    <div>
      {reviews.map((review) => (
        <div key={review.id} className="order-card">
          <p>
            {"★".repeat(review.rating)}
            {"☆".repeat(5 - review.rating)} — {review.reviewer?.name ?? "Anonymous"}
          </p>
          {review.review_text && <p>{review.review_text}</p>}
          {review.image_path && (
            <img src={review.image_path} alt="" style={{ width: 120, borderRadius: 8 }} />
          )}
        </div>
      ))}
    </div>
  );
}
