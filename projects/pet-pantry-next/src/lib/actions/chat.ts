"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { createClient } from "@/lib/supabase/server";
import { getSessionProfile } from "@/lib/data/session";

async function requireProfile() {
  const profile = await getSessionProfile();
  if (!profile) redirect("/login");
  return profile;
}

export async function getOrCreateChat() {
  const profile = await requireProfile();
  const supabase = await createClient();

  let chatId: number;

  const { data: existing, error: existingError } = await supabase
    .from("support_chats")
    .select("id")
    .eq("user_id", profile.id)
    .eq("status", "open")
    .maybeSingle();
  if (existingError) throw existingError;

  if (existing) {
    chatId = existing.id;
  } else {
    const { data: created, error: createError } = await supabase
      .from("support_chats")
      .insert({ user_id: profile.id })
      .select("id")
      .single();
    if (createError) throw createError;
    chatId = created.id;
  }

  const { data: messages, error: messagesError } = await supabase
    .from("support_messages")
    .select("id, sender_type, message, created_at")
    .eq("chat_id", chatId)
    .order("created_at", { ascending: true });
  if (messagesError) throw messagesError;

  return { chatId, messages };
}

export async function sendMessage(chatId: number, message: string) {
  const profile = await requireProfile();
  const trimmed = message.trim();
  if (!trimmed) return;

  const supabase = await createClient();
  const isAdmin = profile.account_type === "admin";

  const { error: messageError } = await supabase.from("support_messages").insert({
    chat_id: chatId,
    sender_type: isAdmin ? "admin" : "customer",
    sender_id: profile.id,
    message: trimmed,
  });
  if (messageError) throw messageError;

  await supabase
    .from("support_chats")
    .update({ last_message_at: new Date().toISOString() })
    .eq("id", chatId);

  if (isAdmin) {
    const { data: chat } = await supabase
      .from("support_chats")
      .select("user_id")
      .eq("id", chatId)
      .single();
    if (chat) {
      await supabase.from("notifications").insert({
        audience: "customer",
        user_id: chat.user_id,
        type: "chat_message",
        message: "Support replied to your chat.",
        chat_id: chatId,
      });
    }
    revalidatePath(`/admin/chats/${chatId}`);
  } else {
    await supabase.from("notifications").insert({
      audience: "admin",
      type: "chat_message",
      message: `New message from ${profile.name ?? "a customer"}.`,
      chat_id: chatId,
    });
  }

  revalidatePath("/admin/chats");
}
