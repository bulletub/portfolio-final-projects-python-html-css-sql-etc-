"use client";

import { useEffect, useRef, useState, useTransition } from "react";
import { createClient } from "@/lib/supabase/client";
import { sendMessage } from "@/lib/actions/chat";
import { closeChat } from "@/lib/actions/admin/chat";

type Message = { id: number; sender_type: string; message: string; created_at: string };
type Chat = {
  id: number;
  status: string;
  customer: { name: string | null; email: string | null } | null;
};

export default function Conversation({
  chat,
  initialMessages,
}: {
  chat: Chat;
  initialMessages: Message[];
}) {
  const [messages, setMessages] = useState<Message[]>(initialMessages);
  const [text, setText] = useState("");
  const [pending, startTransition] = useTransition();
  const bottomRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const supabase = createClient();
    const channel = supabase
      .channel(`admin-chat-${chat.id}`)
      .on(
        "postgres_changes",
        {
          event: "INSERT",
          schema: "public",
          table: "support_messages",
          filter: `chat_id=eq.${chat.id}`,
        },
        (payload) => {
          const row = payload.new as Message;
          setMessages((prev) => (prev.some((m) => m.id === row.id) ? prev : [...prev, row]));
        }
      )
      .subscribe();

    return () => {
      supabase.removeChannel(channel);
    };
  }, [chat.id]);

  useEffect(() => {
    bottomRef.current?.scrollIntoView({ behavior: "smooth" });
  }, [messages]);

  function handleSend() {
    const trimmed = text.trim();
    if (!trimmed) return;
    setText("");
    startTransition(() => sendMessage(chat.id, trimmed));
  }

  return (
    <div>
      <p>
        Chat with {chat.customer?.name ?? "—"} ({chat.customer?.email ?? "—"}) —{" "}
        <span className="status-badge">{chat.status}</span>
      </p>
      {chat.status === "open" && (
        <button type="button" disabled={pending} onClick={() => startTransition(() => closeChat(chat.id))}>
          Close chat
        </button>
      )}
      <div className="conversation-messages">
        {messages.map((m) => (
          <div
            key={m.id}
            className={`chat-bubble-row ${m.sender_type === "admin" ? "from-self" : "from-other"}`}
          >
            <span className="chat-bubble">{m.message}</span>
          </div>
        ))}
        <div ref={bottomRef} />
      </div>
      <div className="conversation-input-row">
        <input
          value={text}
          onChange={(e) => setText(e.target.value)}
          onKeyDown={(e) => e.key === "Enter" && handleSend()}
          placeholder="Reply…"
          disabled={pending}
        />
        <button type="button" onClick={handleSend} disabled={pending}>
          Send
        </button>
      </div>
    </div>
  );
}
