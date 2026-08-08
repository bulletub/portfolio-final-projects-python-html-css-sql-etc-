import Link from "next/link";
import { getCartItems } from "@/lib/data/cart";
import CartItemRow from "./cart-item-row";

export default async function CartPage() {
  const items = await getCartItems();
  const total = items.reduce((sum, item) => sum + item.quantity * (item.product?.price ?? 0), 0);

  if (items.length === 0) {
    return (
      <main>
        <h1>Your cart</h1>
        <p>
          Your cart is empty. <Link href="/shop">Continue shopping</Link>.
        </p>
      </main>
    );
  }

  return (
    <main>
      <h1>Your cart</h1>
      <table className="cart-table">
        <thead>
          <tr>
            <th>Product</th>
            <th>Price</th>
            <th>Quantity</th>
            <th>Subtotal</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          {items.map((item) => (
            <CartItemRow key={item.id} item={item} />
          ))}
        </tbody>
      </table>
      <p>
        <strong>Total: ₱{total.toFixed(2)}</strong>
      </p>
      <Link href="/checkout" className="btn">
        Proceed to checkout
      </Link>
    </main>
  );
}
