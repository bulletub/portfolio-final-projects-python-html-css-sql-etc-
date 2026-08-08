import "server-only";
import { createClient } from "@/lib/supabase/server";
import { getSessionUser } from "./session";

export async function getWishlistItems() {
  const supabase = await createClient();
  const { data, error } = await supabase
    .from("wishlist")
    .select("id, product:products(id, name, price, stock, image_path)")
    .order("created_at", { ascending: false });

  if (error) throw error;
  return data;
}

export async function isInWishlist(productId: number) {
  const user = await getSessionUser();
  if (!user) return false;

  const supabase = await createClient();
  const { data, error } = await supabase
    .from("wishlist")
    .select("id")
    .eq("product_id", productId)
    .maybeSingle();

  if (error) throw error;
  return data !== null;
}

export async function getWishlistCount() {
  const supabase = await createClient();
  const { count, error } = await supabase
    .from("wishlist")
    .select("*", { count: "exact", head: true });

  if (error) throw error;
  return count ?? 0;
}
