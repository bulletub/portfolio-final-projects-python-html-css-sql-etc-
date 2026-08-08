import "server-only";
import { createClient } from "@/lib/supabase/server";
import { getSessionUser } from "./session";

export async function getApprovedReviews(productId: number) {
  const supabase = await createClient();
  const { data, error } = await supabase
    .from("product_reviews")
    .select("id, rating, review_text, image_path, created_at, reviewer:profiles(name)")
    .eq("product_id", productId)
    .eq("status", "approved")
    .order("created_at", { ascending: false });

  if (error) throw error;
  return data;
}

export async function getReviewEligibility(productId: number) {
  const user = await getSessionUser();
  if (!user) return { canReview: false, alreadyReviewed: false };

  const supabase = await createClient();

  const { data: existingReview, error: existingError } = await supabase
    .from("product_reviews")
    .select("id")
    .eq("product_id", productId)
    .eq("user_id", user.id)
    .maybeSingle();
  if (existingError) throw existingError;

  if (existingReview) {
    return { canReview: false, alreadyReviewed: true };
  }

  const { data: qualifyingOrder, error: orderError } = await supabase
    .from("orders")
    .select("order_group_id, order_groups!inner(user_id, status)")
    .eq("product_id", productId)
    .eq("order_groups.user_id", user.id)
    .eq("order_groups.status", "completed")
    .limit(1)
    .maybeSingle();
  if (orderError) throw orderError;

  return { canReview: qualifyingOrder !== null, alreadyReviewed: false };
}
