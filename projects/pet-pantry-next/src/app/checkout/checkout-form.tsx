"use client";

import { useActionState } from "react";
import { placeOrder } from "@/lib/actions/checkout";

type Address = { id: number; full_name: string; address: string; is_default: boolean };

export default function CheckoutForm({
  addresses,
  cartIds,
}: {
  addresses: Address[];
  cartIds: number[];
}) {
  const [state, action, pending] = useActionState(placeOrder, undefined);
  const defaultAddress = addresses.find((a) => a.is_default) ?? addresses[0];

  return (
    <form action={action}>
      <input type="hidden" name="cart_ids" value={cartIds.join(",")} />

      <h2>Choose address</h2>
      {addresses.map((address) => (
        <label className="address-option" key={address.id}>
          <input
            type="radio"
            name="address_id"
            value={address.id}
            defaultChecked={address.id === defaultAddress?.id}
          />{" "}
          {address.full_name} — {address.address}
        </label>
      ))}

      <h2>Payment method</h2>
      <label className="address-option">
        <input type="radio" name="payment_method" value="bank_transfer" defaultChecked /> Bank
        transfer (manually verified by admin)
      </label>
      <label className="address-option">
        <input type="radio" name="payment_method" value="cod" /> Cash on delivery
      </label>

      {state?.error && <p role="alert">{state.error}</p>}

      <button type="submit" disabled={pending || addresses.length === 0}>
        {pending ? "Placing order…" : "Place order"}
      </button>
    </form>
  );
}
