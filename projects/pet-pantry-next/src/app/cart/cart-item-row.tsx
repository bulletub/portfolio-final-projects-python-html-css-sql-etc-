"use client";

import { useTransition } from "react";
import { updateCartItemQuantity, removeCartItem } from "@/lib/actions/cart";

type CartItem = {
  id: number;
  quantity: number;
  product: { id: number; name: string; price: number; stock: number; image_path: string | null } | null;
};

export default function CartItemRow({ item }: { item: CartItem }) {
  const [pending, startTransition] = useTransition();

  if (!item.product) return null;

  return (
    <tr>
      <td>{item.product.name}</td>
      <td>₱{item.product.price.toFixed(2)}</td>
      <td>
        <input
          type="number"
          min={1}
          max={item.product.stock}
          defaultValue={item.quantity}
          disabled={pending}
          onBlur={(e) =>
            startTransition(() => updateCartItemQuantity(item.id, Number(e.target.value)))
          }
        />
      </td>
      <td>₱{(item.quantity * item.product.price).toFixed(2)}</td>
      <td>
        <button
          type="button"
          disabled={pending}
          onClick={() => startTransition(() => removeCartItem(item.id))}
        >
          Remove
        </button>
      </td>
    </tr>
  );
}
