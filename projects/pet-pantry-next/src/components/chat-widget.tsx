"use client";

import { useEffect, useRef, useState, useTransition } from "react";
import { createClient } from "@/lib/supabase/client";
import { getOrCreateChat, sendMessage } from "@/lib/actions/chat";

type Message = { id: number; sender_type: string; message: string; created_at: string };

export default function ChatWidget() {
  const [open, setOpen] = useState(false);
  const [chatId, setChatId] = useState<number | null>(null);
  const [messages, setMessages] = useState<Message[]>([]);
  const [text, setText] = useState("");
  const [pending, startTransition] = useTransition();
  const bottomRef = useRef<HTMLDivElement>(null);
  const loading = open && chatId === null;

  useEffect(() => {
    if (!open || chatId !== null) return;
    getOrCreateChat().then((result) => {
      setChatId(result.chatId);
      setMessages(result.messages);
    });
  }, [open, chatId]);

  useEffect(() => {
    if (chatId === null) return;
    const supabase = createClient();
    const channel = supabase
      .channel(`chat-widget-${chatId}`)
      .on(
        "postgres_changes",
        {
          event: "INSERT",
          schema: "public",
          table: "support_messages",
          filter: `chat_id=eq.${chatId}`,
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
  }, [chatId]);

  useEffect(() => {
    bottomRef.current?.scrollIntoView({ behavior: "smooth" });
  }, [messages]);

  function handleSend() {
    const trimmed = text.trim();
    if (!trimmed || chatId === null) return;
    setText("");
    startTransition(() => sendMessage(chatId, trimmed));
  }

  return (
    <div className="chat-widget">
      {open ? (
        <div className="chat-panel">
          <div className="chat-panel-header">
            <strong>Support chat</strong>
            <button type="button" onClick={() => setOpen(false)}>
              ×
            </button>
          </div>
          <div className="chat-messages">
            {loading && <p>Loading…</p>}
            {messages.map((m) => (
              <div
                key={m.id}
                className={`chat-bubble-row ${m.sender_type === "customer" ? "from-self" : "from-other"}`}
              >
                <span className="chat-bubble">{m.message}</span>
              </div>
            ))}
            <div ref={bottomRef} />
          </div>
          <div className="chat-input-row">
            <input
              value={text}
              onChange={(e) => setText(e.target.value)}
              onKeyDown={(e) => e.key === "Enter" && handleSend()}
              placeholder="Type a message…"
              disabled={pending}
            />
            <button type="button" onClick={handleSend} disabled={pending}>
              Send
            </button>
          </div>
        </div>
      ) : (
        <button type="button" onClick={() => setOpen(true)}>
          Chat with us
        </button>
      )}
    </div>
  );
}
