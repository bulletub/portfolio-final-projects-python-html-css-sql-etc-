import Link from "next/link";
import { getOrderGroups } from "@/lib/data/orders";

export default async function OrdersPage() {
  const orderGroups = await getOrderGroups();

  if (orderGroups.length === 0) {
    return (
      <main>
        <h1>Your orders</h1>
        <p>
          No orders yet. <Link href="/shop">Start shopping</Link>.
        </p>
      </main>
    );
  }

  return (
    <main>
      <h1>Your orders</h1>
      {orderGroups.map((group) => {
        const total = group.orders.reduce((sum, o) => sum + o.quantity * o.price, 0) + group.shipping_fee - group.discount_amount;
        return (
          <div className="order-card" key={group.id}>
            <p>
              Order #{group.id} — <span className="status-badge">{group.status}</span>
            </p>
            <p>{new Date(group.created_at).toLocaleString()}</p>
            <ul>
              {group.orders.map((o) => (
                <li key={o.id}>
                  {o.product?.name} × {o.quantity} — ₱{(o.quantity * o.price).toFixed(2)}
                </li>
              ))}
            </ul>
            <p>Shipping to: {group.address}</p>
            <p>Payment: {group.payment_method}</p>
            {group.promo_code && (
              <p>
                Promo: {group.promo_code} (−₱{group.discount_amount.toFixed(2)})
              </p>
            )}
            <p>
              <strong>Total: ₱{total.toFixed(2)}</strong>
            </p>
          </div>
        );
      })}
    </main>
  );
}
