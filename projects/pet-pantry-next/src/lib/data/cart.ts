import "server-only";
import { createClient } from "@/lib/supabase/server";

export async function getCartItems() {
  const supabase = await createClient();
  const { data, error } = await supabase
    .from("cart")
    .select("id, quantity, product:products(id, name, price, stock, image_path)")
    .order("created_at", { ascending: true });

  if (error) throw error;
  return data;
}
