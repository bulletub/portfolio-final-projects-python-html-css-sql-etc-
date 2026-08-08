import Link from "next/link";
import { createClient } from "@/lib/supabase/server";

export default async function AdminChatsPage() {
  const supabase = await createClient();
  const { data: chats, error } = await supabase
    .from("support_chats")
    .select("*, customer:profiles(name, email)")
    .order("last_message_at", { ascending: false });
  if (error) throw error;

  if (chats.length === 0) {
    return <p>No chats yet.</p>;
  }

  return (
    <table className="cart-table">
      <thead>
        <tr>
          <th>Customer</th>
          <th>Status</th>
          <th>Last message</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        {chats.map((chat) => (
          <tr key={chat.id}>
            <td>
              {chat.customer?.name ?? "—"} ({chat.customer?.email ?? "—"})
            </td>
            <td>{chat.status}</td>
            <td>{new Date(chat.last_message_at).toLocaleString()}</td>
            <td>
              <Link href={`/admin/chats/${chat.id}`}>Open</Link>
            </td>
          </tr>
        ))}
      </tbody>
    </table>
  );
}
