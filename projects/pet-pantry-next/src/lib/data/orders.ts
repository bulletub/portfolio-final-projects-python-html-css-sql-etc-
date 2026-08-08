import "server-only";
import { createClient } from "@/lib/supabase/server";

export async function getOrderGroups() {
  const supabase = await createClient();
  const { data, error } = await supabase
    .from("order_groups")
    .select("*, orders(id, quantity, price, product:products(id, name, image_path))")
    .order("created_at", { ascending: false });

  if (error) throw error;
  return data;
}
