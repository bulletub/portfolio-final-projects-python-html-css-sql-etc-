"use client";

import { useState, useTransition } from "react";
import { useActionState } from "react";
import { placeOrder, previewPromo, type PromoPreviewState } from "@/lib/actions/checkout";

type Address = { id: number; full_name: string; address: string; is_default: boolean };

export default function CheckoutForm({
  addresses,
  cartIds,
  subtotal,
}: {
  addresses: Address[];
  cartIds: number[];
  subtotal: number;
}) {
  const [state, action, pending] = useActionState(placeOrder, undefined);
  const defaultAddress = addresses.find((a) => a.is_default) ?? addresses[0];

  const [promoCode, setPromoCode] = useState("");
  const [promoPreview, setPromoPreview] = useState<PromoPreviewState>(undefined);
  const [previewPending, startPreviewTransition] = useTransition();

  function handleApplyPromo() {
    if (!promoCode.trim()) return;
    startPreviewTransition(async () => {
      const result = await previewPromo(promoCode, subtotal);
      setPromoPreview(result);
    });
  }

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

      <h2>Promo code</h2>
      <div style={{ display: "flex", gap: "0.5rem", maxWidth: 400 }}>
        <input
          name="promo_code"
          value={promoCode}
          onChange={(e) => {
            setPromoCode(e.target.value);
            setPromoPreview(undefined);
          }}
          placeholder="Enter code"
        />
        <button type="button" disabled={previewPending} onClick={handleApplyPromo}>
          {previewPending ? "Checking…" : "Apply"}
        </button>
      </div>
      {promoPreview && "error" in promoPreview && <p role="alert">{promoPreview.error}</p>}
      {promoPreview && "discountAmount" in promoPreview && (
        <p>Discount: −₱{promoPreview.discountAmount.toFixed(2)}</p>
      )}

      {state?.error && <p role="alert">{state.error}</p>}

      <button type="submit" disabled={pending || addresses.length === 0}>
        {pending ? "Placing order…" : "Place order"}
      </button>
    </form>
  );
}
