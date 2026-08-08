"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { createClient } from "@/lib/supabase/server";
import { getSessionUser } from "@/lib/data/session";

export type ReviewFormState = { error: string } | undefined;

export async function submitReview(
  productId: number,
  _prevState: ReviewFormState,
  formData: FormData
): Promise<ReviewFormState> {
  const user = await getSessionUser();
  if (!user) redirect(`/login?next=/products/${productId}`);

  const rating = Number(formData.get("rating"));
  const reviewText = String(formData.get("review_text") ?? "").trim();

  if (!Number.isFinite(rating) || rating < 1 || rating > 5) {
    return { error: "Please select a rating between 1 and 5." };
  }

  const supabase = await createClient();

  const { data: qualifyingOrder, error: orderError } = await supabase
    .from("orders")
    .select("order_group_id, order_groups!inner(user_id, status)")
    .eq("product_id", productId)
    .eq("order_groups.user_id", user.id)
    .eq("order_groups.status", "completed")
    .limit(1)
    .maybeSingle();
  if (orderError) throw orderError;
  if (!qualifyingOrder) {
    return { error: "You can only review products from a completed order." };
  }

  let image_path: string | null = null;
  const file = formData.get("image");
  if (file instanceof File && file.size > 0) {
    const path = `${Date.now()}-${file.name.replace(/[^a-zA-Z0-9._-]/g, "_")}`;
    const { error: uploadError } = await supabase.storage
      .from("review-images")
      .upload(path, file);
    if (uploadError) return { error: uploadError.message };
    image_path = supabase.storage.from("review-images").getPublicUrl(path).data.publicUrl;
  }

  const { error } = await supabase.from("product_reviews").insert({
    product_id: productId,
    user_id: user.id,
    order_group_id: qualifyingOrder.order_group_id,
    rating,
    review_text: reviewText || null,
    image_path,
  });
  if (error) return { error: error.message };

  revalidatePath(`/products/${productId}`);
  revalidatePath("/admin/reviews");
}
