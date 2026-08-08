import "server-only";
import { createClient } from "@/lib/supabase/server";

export async function getAddresses() {
  const supabase = await createClient();
  const { data, error } = await supabase
    .from("user_addresses")
    .select("*")
    .order("is_default", { ascending: false })
    .order("created_at", { ascending: false });

  if (error) throw error;
  return data;
}
