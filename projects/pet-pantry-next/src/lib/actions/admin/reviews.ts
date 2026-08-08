"use server";

import { revalidatePath } from "next/cache";
import { createClient } from "@/lib/supabase/server";
import { requireAdmin } from "@/lib/data/session";

export async function setReviewStatus(reviewId: number, status: "approved" | "rejected") {
  await requireAdmin();
  const supabase = await createClient();

  const { error } = await supabase
    .from("product_reviews")
    .update({ status })
    .eq("id", reviewId);
  if (error) throw error;

  revalidatePath("/admin/reviews");
}
