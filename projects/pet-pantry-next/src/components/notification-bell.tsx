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
    <div className="notification-bell">
      <button type="button" onClick={() => setOpen((o) => !o)}>
        Notifications{unreadCount > 0 ? ` (${unreadCount})` : ""}
      </button>
      {open && (
        <div className="notification-dropdown">
          {notifications.length === 0 && <p>No notifications.</p>}
          {unreadCount > 0 && (
            <button type="button" onClick={handleMarkAll}>
              Mark all read
            </button>
          )}
          {notifications.map((n) => {
            const href = linkFor(n);
            const className = `notification-item${n.is_read ? "" : " unread"}`;
            const body = <p>{n.message}</p>;

            return href ? (
              <Link
                key={n.id}
                href={href}
                className={className}
                onClick={() => !n.is_read && handleMarkOne(n.id)}
              >
                {body}
              </Link>
            ) : (
              <div
                key={n.id}
                className={className}
                onClick={() => !n.is_read && handleMarkOne(n.id)}
              >
                {body}
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
}
