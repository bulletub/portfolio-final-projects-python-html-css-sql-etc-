import Link from "next/link";
import { getOrderGroups } from "@/lib/data/orders";

const STATUS_STYLES: Record<string, string> = {
  pending: "bg-brand-orange",
  shipping: "bg-blue-500",
  completed: "bg-green-600",
  cancelled: "bg-red-500",
};

export default async function OrdersPage() {
  const orderGroups = await getOrderGroups();

  if (orderGroups.length === 0) {
    return (
      <main className="mx-auto max-w-3xl px-4 py-16 text-center">
        <h1 className="text-2xl font-bold text-neutral-900">Your orders</h1>
        <p className="mt-3 text-neutral-500">
          No orders yet.{" "}
          <Link href="/shop" className="font-semibold text-brand-orange">
            Start shopping
          </Link>
          .
        </p>
      </main>
    );
  }

  return (
    <main className="mx-auto max-w-3xl px-4 py-10">
      <h1 className="mb-6 text-2xl font-bold text-neutral-900">Your orders</h1>
      <div className="flex flex-col gap-5">
        {orderGroups.map((group) => {
          const total =
            group.orders.reduce((sum, o) => sum + o.quantity * o.price, 0) +
            group.shipping_fee -
            group.discount_amount;
          return (
            <div key={group.id} className="rounded-xl border border-neutral-100 bg-white shadow-sm">
              <div className="flex items-center justify-between rounded-t-xl bg-neutral-50 px-4 py-3">
                <div>
                  <p className="font-bold text-neutral-800">Order #{group.id}</p>
                  <p className="text-xs text-neutral-500">{new Date(group.created_at).toLocaleString()}</p>
                </div>
                <span
                  className={`rounded-md px-3 py-1 text-xs font-semibold text-white capitalize ${
                    STATUS_STYLES[group.status] ?? "bg-neutral-400"
                  }`}
                >
                  {group.status}
                </span>
              </div>
              <div className="flex flex-col gap-2 px-4 py-4">
                {group.orders.map((o) => (
                  <p key={o.id} className="text-sm text-neutral-700">
                    {o.product?.name} × {o.quantity} — ₱{(o.quantity * o.price).toFixed(2)}
                  </p>
                ))}
              </div>
              <div className="rounded-b-xl bg-neutral-50 px-4 py-4 text-sm text-neutral-600">
                <p>Shipping to: {group.address}</p>
                <p>Payment: {group.payment_method}</p>
                {group.promo_code && (
                  <p className="text-green-600">
                    Promo: {group.promo_code} (−₱{group.discount_amount.toFixed(2)})
                  </p>
                )}
                <p className="mt-2 text-right font-bold text-neutral-900">Total: ₱{total.toFixed(2)}</p>
              </div>
            </div>
          );
        })}
      </div>
    </main>
  );
}
