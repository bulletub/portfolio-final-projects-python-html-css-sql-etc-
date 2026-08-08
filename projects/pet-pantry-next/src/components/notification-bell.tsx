"use client";

import { useEffect, useState, useTransition } from "react";
import Link from "next/link";
import { createClient } from "@/lib/supabase/client";
import { markNotificationRead, markAllNotificationsRead } from "@/lib/actions/notifications";

type Notification = {
  id: number;
  message: string;
  type: string;
  is_read: boolean;
  created_at: string;
  chat_id: number | null;
  order_group_id: number | null;
};

export default function NotificationBell({
  initial,
  filter,
  isAdmin,
}: {
  initial: Notification[];
  filter: string;
  isAdmin: boolean;
}) {
  const [notifications, setNotifications] = useState(initial);
  const [open, setOpen] = useState(false);
  const [, startTransition] = useTransition();

  useEffect(() => {
    const supabase = createClient();
    const channel = supabase
      .channel(`notifications-${filter}`)
      .on(
        "postgres_changes",
        { event: "INSERT", schema: "public", table: "notifications", filter },
        (payload) => {
          const row = payload.new as Notification;
          setNotifications((prev) => [row, ...prev].slice(0, 20));
        }
      )
      .subscribe();

    return () => {
      supabase.removeChannel(channel);
    };
  }, [filter]);

  const unreadCount = notifications.filter((n) => !n.is_read).length;

  function handleMarkAll() {
    const ids = notifications.filter((n) => !n.is_read).map((n) => n.id);
    setNotifications((prev) => prev.map((n) => ({ ...n, is_read: true })));
    startTransition(() => markAllNotificationsRead(ids));
  }

  function handleMarkOne(id: number) {
    setNotifications((prev) => prev.map((n) => (n.id === id ? { ...n, is_read: true } : n)));
    startTransition(() => markNotificationRead(id));
  }

  function linkFor(n: Notification) {
    if (n.chat_id) return isAdmin ? `/admin/chats/${n.chat_id}` : null;
    if (n.order_group_id) return isAdmin ? "/admin/orders" : "/orders";
    return null;
  }

  return (
    <div className="relative">
      <button
        type="button"
        aria-label="Notifications"
        onClick={() => setOpen((o) => !o)}
        className="relative flex h-9 w-9 items-center justify-center rounded-full bg-transparent text-neutral-700 hover:bg-neutral-100 hover:text-brand-orange"
      >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.5} className="h-6 w-6 shrink-0">
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"
          />
        </svg>
        {unreadCount > 0 && (
          <span className="absolute -top-0.5 -right-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-brand-orange text-[10px] font-bold text-white">
            {unreadCount}
          </span>
        )}
      </button>
      {open && (
        <div className="absolute right-0 top-full z-50 mt-2 w-72 rounded-lg border border-neutral-200 bg-white p-2 shadow-lg">
          {notifications.length === 0 && <p className="px-2 py-3 text-sm text-neutral-500">No notifications.</p>}
          {unreadCount > 0 && (
            <button
              type="button"
              onClick={handleMarkAll}
              className="mb-1 w-full bg-transparent px-2 py-1 text-left text-xs font-semibold text-brand-orange"
            >
              Mark all read
            </button>
          )}
          <div className="max-h-72 overflow-y-auto">
            {notifications.map((n) => {
              const href = linkFor(n);
              const itemClass = `block rounded-md px-2 py-2 text-sm text-neutral-700 hover:bg-neutral-50 ${
                n.is_read ? "" : "font-semibold"
              }`;
              const body = <p>{n.message}</p>;

              return href ? (
                <Link key={n.id} href={href} className={itemClass} onClick={() => !n.is_read && handleMarkOne(n.id)}>
                  {body}
                </Link>
              ) : (
                <div key={n.id} className={itemClass} onClick={() => !n.is_read && handleMarkOne(n.id)}>
                  {body}
                </div>
              );
            })}
          </div>
        </div>
      )}
    </div>
  );
}
