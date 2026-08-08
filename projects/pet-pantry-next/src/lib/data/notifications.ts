import "server-only";
import { createClient } from "@/lib/supabase/server";

export async function getNotifications() {
  const supabase = await createClient();
  const { data, error } = await supabase
    .from("notifications")
    .select("*")
    .order("created_at", { ascending: false })
    .limit(20);
  if (error) throw error;
  return data;
}
