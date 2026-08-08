import { createClient } from "@/lib/supabase/server";
import ReviewModerationRow from "./review-moderation-row";

export default async function AdminReviewsPage() {
  const supabase = await createClient();
  const { data: reviews, error } = await supabase
    .from("product_reviews")
    .select("id, rating, review_text, image_path, status, created_at, product:products(name), reviewer:profiles(name, email)")
    .order("status", { ascending: true })
    .order("created_at", { ascending: false });
  if (error) throw error;

  if (reviews.length === 0) {
    return <p>No reviews yet.</p>;
  }

  return (
    <div>
      {reviews.map((review) => (
        <ReviewModerationRow key={review.id} review={review} />
      ))}
    </div>
  );
}
