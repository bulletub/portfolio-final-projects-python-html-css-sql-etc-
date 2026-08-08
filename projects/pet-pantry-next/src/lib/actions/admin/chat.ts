"use server";

import { revalidatePath } from "next/cache";
import { createClient } from "@/lib/supabase/server";
import { requireAdmin } from "@/lib/data/session";

export async function closeChat(chatId: number) {
  await requireAdmin();
  const supabase = await createClient();

  const { error } = await supabase
    .from("support_chats")
    .update({ status: "closed" })
    .eq("id", chatId);
  if (error) throw error;

  revalidatePath("/admin/chats");
  revalidatePath(`/admin/chats/${chatId}`);
}
