import { notFound } from "next/navigation";
import { createClient } from "@/lib/supabase/server";
import Conversation from "./conversation";

export default async function AdminChatPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  const chatId = Number(id);
  const supabase = await createClient();

  const { data: chat, error: chatError } = await supabase
    .from("support_chats")
    .select("id, status, customer:profiles(name, email)")
    .eq("id", chatId)
    .maybeSingle();
  if (chatError) throw chatError;
  if (!chat) notFound();

  const { data: messages, error: messagesError } = await supabase
    .from("support_messages")
    .select("id, sender_type, message, created_at")
    .eq("chat_id", chatId)
    .order("created_at", { ascending: true });
  if (messagesError) throw messagesError;

  return <Conversation chat={chat} initialMessages={messages} />;
}
