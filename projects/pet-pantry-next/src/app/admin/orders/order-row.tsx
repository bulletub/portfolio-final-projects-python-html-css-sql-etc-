"use client";

import { useTransition } from "react";
import { updateOrderStatus, setPaymentStatus } from "@/lib/actions/admin/orders";

type OrderGroup = {
  id: number;
  status: string;
  address: string;
  payment_method: string;
  payment_code: string | null;
  promo_code: string | null;
  discount_amount: number;
  shipping_fee: number;
  createdAtDisplay: string;
  customer: { name: string | null; email: string | null } | null;
  orders: { id: number; quantity: number; price: number; product: { name: string } | null }[];
};

export default function OrderRow({ group }: { group: OrderGroup }) {
  const [pending, startTransition] = useTransition();
  const total =
    group.orders.reduce((sum, o) => sum + o.quantity * o.price, 0) +
    group.shipping_fee -
    group.discount_amount;

  return (
    <div className="order-card">
      <p>
        Order #{group.id} — <span className="status-badge">{group.status}</span>
      </p>
      <p>{group.createdAtDisplay}</p>
      <p>
        Customer: {group.customer?.name ?? "—"} ({group.customer?.email ?? "—"})
      </p>
      <ul>
        {group.orders.map((o) => (
          <li key={o.id}>
            {o.product?.name ?? "Unknown product"} × {o.quantity} — ₱{(o.quantity * o.price).toFixed(2)}
          </li>
        ))}
      </ul>
      <p>Shipping to: {group.address}</p>
      <p>
        Payment: {group.payment_method}
        {group.payment_code ? ` (${group.payment_code})` : ""}
      </p>
      {group.promo_code && (
        <p>
          Promo: {group.promo_code} (−₱{group.discount_amount.toFixed(2)})
        </p>
      )}
      <p>
        <strong>Total: ₱{total.toFixed(2)}</strong>
      </p>

      <label>
        Status:{" "}
        <select
          defaultValue={group.status}
          disabled={pending}
          onChange={(e) =>
            startTransition(() => updateOrderStatus(group.id, e.target.value))
          }
        >
          <option value="pending">Pending</option>
          <option value="shipping">Shipping</option>
          <option value="completed">Completed</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </label>

      {group.payment_method === "bank_transfer" && group.payment_code === "pending_verification" && (
        <p>
          <button
            type="button"
            disabled={pending}
            onClick={() => startTransition(() => setPaymentStatus(group.id, "verified"))}
          >
            Verify payment
          </button>{" "}
          <button
            type="button"
            disabled={pending}
            onClick={() => startTransition(() => setPaymentStatus(group.id, "failed"))}
          >
            Reject payment
          </button>
        </p>
      )}
    </div>
  );
}
