"use server";

import { redirect } from "next/navigation";
import { createClient } from "@/lib/supabase/server";
import { getSessionUser } from "@/lib/data/session";

async function requireUser() {
  const user = await getSessionUser();
  if (!user) redirect("/login");
  return user;
}

export async function markNotificationRead(id: number) {
  await requireUser();
  const supabase = await createClient();
  const { error } = await supabase.from("notifications").update({ is_read: true }).eq("id", id);
  if (error) throw error;
}

export async function markAllNotificationsRead(ids: number[]) {
  await requireUser();
  if (ids.length === 0) return;
  const supabase = await createClient();
  const { error } = await supabase.from("notifications").update({ is_read: true }).in("id", ids);
  if (error) throw error;
}
