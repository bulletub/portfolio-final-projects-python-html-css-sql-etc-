import Link from "next/link";
import { getCartItems } from "@/lib/data/cart";
import { getAddresses } from "@/lib/data/addresses";
import { addAddress } from "@/lib/actions/checkout";
import CheckoutForm from "./checkout-form";

export default async function CheckoutPage() {
  const [items, addresses] = await Promise.all([getCartItems(), getAddresses()]);

  if (items.length === 0) {
    return (
      <main className="mx-auto max-w-3xl px-4 py-16 text-center">
        <h1 className="text-2xl font-bold text-neutral-900">Checkout</h1>
        <p className="mt-3 text-neutral-500">
          Your cart is empty.{" "}
          <Link href="/shop" className="font-semibold text-brand-orange">
            Continue shopping
          </Link>
          .
        </p>
      </main>
    );
  }

  const total = items.reduce((sum, item) => sum + item.quantity * (item.product?.price ?? 0), 0);
  const cartIds = items.map((item) => item.id);

  return (
    <main className="mx-auto max-w-3xl px-4 py-10">
      <h1 className="mb-6 text-2xl font-bold text-neutral-900">Checkout</h1>

      <div className="rounded-xl border border-neutral-100 bg-white p-5 shadow-sm">
        <h2 className="mb-3 text-sm font-bold tracking-wide text-neutral-500 uppercase">Order Summary</h2>
        <ul className="flex flex-col gap-2 text-sm text-neutral-700">
          {items.map((item) => (
            <li key={item.id} className="flex justify-between">
              <span>
                {item.product?.name} × {item.quantity}
              </span>
              <span>₱{(item.quantity * (item.product?.price ?? 0)).toFixed(2)}</span>
            </li>
          ))}
        </ul>
        <p className="mt-3 border-t border-neutral-100 pt-3 text-right font-bold text-neutral-900">
          Total: ₱{total.toFixed(2)}
        </p>
      </div>

      <div className="mt-6 rounded-xl border border-neutral-100 bg-white p-5 shadow-sm">
        <h2 className="mb-3 text-sm font-bold tracking-wide text-neutral-500 uppercase">Add New Address</h2>
        {addresses.length === 0 && <p className="mb-3 text-sm text-neutral-500">No saved addresses yet.</p>}
        <form action={addAddress} className="flex flex-col gap-3">
          <div>
            <label htmlFor="full_name" className="mb-1 block text-xs font-semibold text-neutral-500">
              Full name
            </label>
            <input
              id="full_name"
              name="full_name"
              required
              className="w-full rounded-lg border border-neutral-200 px-3 py-2 text-sm"
            />
          </div>
          <div>
            <label htmlFor="address" className="mb-1 block text-xs font-semibold text-neutral-500">
              Address
            </label>
            <input
              id="address"
              name="address"
              required
              className="w-full rounded-lg border border-neutral-200 px-3 py-2 text-sm"
            />
          </div>
          <label className="flex items-center gap-2 text-sm text-neutral-600">
            <input type="checkbox" name="is_default" /> Set as default
          </label>
          <button
            type="submit"
            className="w-fit rounded-full bg-black px-5 py-2 text-sm font-semibold text-white hover:bg-neutral-800"
          >
            Save address
          </button>
        </form>
      </div>

      <div className="mt-6">
        <CheckoutForm addresses={addresses} cartIds={cartIds} subtotal={total} />
      </div>
    </main>
  );
}
