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
    <div className="grid grid-cols-[50px_2fr_1fr_1fr_1fr_60px] items-center gap-2 border-b border-neutral-100 py-3 text-sm">
      <img
        src={item.product.image_path ?? "/products/placeholder.svg"}
        alt={item.product.name}
        className="h-14 w-14 rounded-lg object-cover"
      />
      <span className="font-medium text-neutral-800">{item.product.name}</span>
      <span className="text-neutral-600">₱{item.product.price.toFixed(2)}</span>
      <input
        type="number"
        min={1}
        max={item.product.stock}
        defaultValue={item.quantity}
        disabled={pending}
        onBlur={(e) => startTransition(() => updateCartItemQuantity(item.id, Number(e.target.value)))}
        className="w-16 rounded-lg border border-neutral-200 px-2 py-1 text-center"
      />
      <span className="font-semibold text-neutral-800">₱{(item.quantity * item.product.price).toFixed(2)}</span>
      <button
        type="button"
        disabled={pending}
        onClick={() => startTransition(() => removeCartItem(item.id))}
        className="justify-self-end bg-transparent text-xs font-semibold text-orange-600 underline hover:text-orange-700"
      >
        Remove
      </button>
    </div>
  );
}
