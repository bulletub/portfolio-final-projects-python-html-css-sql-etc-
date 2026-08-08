import "server-only";
import { createClient } from "@/lib/supabase/server";

export async function getActivePromotions() {
  const supabase = await createClient();
  const { data, error } = await supabase.rpc("list_active_promos");
  if (error) throw error;
  return data;
}
