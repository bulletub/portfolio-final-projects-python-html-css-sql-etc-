import Link from "next/link";
import { getCartItems } from "@/lib/data/cart";
import { getAddresses } from "@/lib/data/addresses";
import { addAddress } from "@/lib/actions/checkout";
import CheckoutForm from "./checkout-form";

export default async function CheckoutPage() {
  const [items, addresses] = await Promise.all([getCartItems(), getAddresses()]);

  if (items.length === 0) {
    return (
      <main>
        <h1>Checkout</h1>
        <p>
          Your cart is empty. <Link href="/shop">Continue shopping</Link>.
        </p>
      </main>
    );
  }

  const total = items.reduce((sum, item) => sum + item.quantity * (item.product?.price ?? 0), 0);
  const cartIds = items.map((item) => item.id);

  return (
    <main>
      <h1>Checkout</h1>

      <h2>Order summary</h2>
      <ul>
        {items.map((item) => (
          <li key={item.id}>
            {item.product?.name} × {item.quantity} — ₱
            {(item.quantity * (item.product?.price ?? 0)).toFixed(2)}
          </li>
        ))}
      </ul>
      <p>
        <strong>Total: ₱{total.toFixed(2)}</strong>
      </p>

      <h2>Shipping address</h2>
      {addresses.length === 0 && <p>No saved addresses yet — add one below.</p>}

      <form action={addAddress}>
        <label htmlFor="full_name">Full name</label>
        <input id="full_name" name="full_name" required />
        <label htmlFor="address">Address</label>
        <input id="address" name="address" required />
        <label>
          <input type="checkbox" name="is_default" /> Set as default
        </label>
        <button type="submit">Save address</button>
      </form>

      <CheckoutForm addresses={addresses} cartIds={cartIds} subtotal={total} />
    </main>
  );
}
