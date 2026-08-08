import { createClient } from "@/lib/supabase/server";
import OrderRow from "./order-row";

export default async function AdminOrdersPage() {
  const supabase = await createClient();
  const { data: orderGroups, error } = await supabase
    .from("order_groups")
    .select(
      "*, customer:profiles(name, email), orders(id, quantity, price, product:products(name))"
    )
    .order("created_at", { ascending: false });
  if (error) throw error;

  if (orderGroups.length === 0) {
    return <p>No orders yet.</p>;
  }

  return (
    <div>
      {orderGroups.map((group) => (
        <OrderRow
          key={group.id}
          group={{ ...group, createdAtDisplay: new Date(group.created_at).toLocaleString() }}
        />
      ))}
    </div>
  );
}
