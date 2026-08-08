import Link from "next/link";
import { getCartItems } from "@/lib/data/cart";
import CartItemRow from "./cart-item-row";

export default async function CartPage() {
  const items = await getCartItems();
  const total = items.reduce((sum, item) => sum + item.quantity * (item.product?.price ?? 0), 0);

  if (items.length === 0) {
    return (
      <main className="mx-auto max-w-3xl px-4 py-16 text-center">
        <h1 className="text-2xl font-bold text-neutral-900">Your cart</h1>
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

  return (
    <main className="mx-auto max-w-3xl px-4 py-10">
      <h1 className="mb-6 text-2xl font-bold text-neutral-900">Your cart</h1>
      <div className="rounded-xl border border-neutral-100 bg-white p-5 shadow-sm">
        <div className="grid grid-cols-[50px_2fr_1fr_1fr_1fr_60px] gap-2 border-b-2 border-neutral-200 pb-2 text-xs font-semibold text-neutral-500">
          <span></span>
          <span>Product</span>
          <span>Price</span>
          <span>Quantity</span>
          <span>Total</span>
          <span></span>
        </div>
        {items.map((item) => (
          <CartItemRow key={item.id} item={item} />
        ))}
      </div>

      <div className="mt-4 flex flex-col gap-3 rounded-xl bg-neutral-50 p-5 sm:flex-row sm:items-center sm:justify-between">
        <p className="text-lg font-bold text-neutral-900">Total: ₱{total.toFixed(2)}</p>
        <Link
          href="/checkout"
          className="rounded-lg bg-brand-orange px-6 py-3 text-center text-sm font-semibold text-white hover:bg-brand-orange-dark"
        >
          Checkout
        </Link>
      </div>
    </main>
  );
}
