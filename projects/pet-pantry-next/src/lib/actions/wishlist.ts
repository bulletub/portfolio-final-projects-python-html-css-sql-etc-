"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { createClient } from "@/lib/supabase/server";
import { getSessionUser } from "@/lib/data/session";

async function requireUser() {
  const user = await getSessionUser();
  if (!user) redirect("/login?next=/wishlist");
  return user;
}

export async function toggleWishlist(productId: number) {
  const user = await requireUser();
  const supabase = await createClient();

  const { data: existing, error: existingError } = await supabase
    .from("wishlist")
    .select("id")
    .eq("user_id", user.id)
    .eq("product_id", productId)
    .maybeSingle();
  if (existingError) throw existingError;

  if (existing) {
    const { error } = await supabase.from("wishlist").delete().eq("id", existing.id);
    if (error) throw error;
  } else {
    const { error } = await supabase
      .from("wishlist")
      .insert({ user_id: user.id, product_id: productId });
    if (error) throw error;
  }

  revalidatePath("/wishlist");
  revalidatePath(`/products/${productId}`);
  revalidatePath("/shop");
}

export async function removeFromWishlist(wishlistId: number) {
  await requireUser();
  const supabase = await createClient();
  const { error } = await supabase.from("wishlist").delete().eq("id", wishlistId);
  if (error) throw error;

  revalidatePath("/wishlist");
}
