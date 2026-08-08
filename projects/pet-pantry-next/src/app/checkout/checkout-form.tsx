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
    <form action={action} className="flex flex-col gap-6">
      <input type="hidden" name="cart_ids" value={cartIds.join(",")} />

      <div className="rounded-xl border border-neutral-100 bg-white p-5 shadow-sm">
        <h2 className="mb-3 text-sm font-bold tracking-wide text-neutral-500 uppercase">Shipping Address</h2>
        <div className="flex flex-col gap-2">
          {addresses.map((address) => (
            <label
              key={address.id}
              className="flex items-center gap-2 rounded-lg border border-neutral-200 px-3 py-2 text-sm has-checked:border-brand-orange has-checked:bg-orange-50"
            >
              <input
                type="radio"
                name="address_id"
                value={address.id}
                defaultChecked={address.id === defaultAddress?.id}
              />
              {address.full_name} — {address.address}
            </label>
          ))}
        </div>
      </div>

      <div className="rounded-xl border border-neutral-100 bg-white p-5 shadow-sm">
        <h2 className="mb-3 text-sm font-bold tracking-wide text-neutral-500 uppercase">Payment Method</h2>
        <div className="flex flex-col gap-2">
          <label className="flex items-center gap-2 rounded-lg border border-neutral-200 px-3 py-2 text-sm has-checked:border-brand-orange has-checked:bg-orange-50">
            <input type="radio" name="payment_method" value="bank_transfer" defaultChecked />
            🏦 Bank transfer (manually verified by admin)
          </label>
          <label className="flex items-center gap-2 rounded-lg border border-neutral-200 px-3 py-2 text-sm has-checked:border-brand-orange has-checked:bg-orange-50">
            <input type="radio" name="payment_method" value="cod" />
            💵 Cash on delivery
          </label>
        </div>
      </div>

      <div className="rounded-xl border-2 border-orange-200 bg-gradient-to-r from-orange-50 to-yellow-50 p-5">
        <h2 className="mb-3 text-sm font-bold text-neutral-800">🎁 Have a Promo Code?</h2>
        <div className="flex gap-2">
          <input
            name="promo_code"
            value={promoCode}
            onChange={(e) => {
              setPromoCode(e.target.value.toUpperCase());
              setPromoPreview(undefined);
            }}
            placeholder="Enter code"
            className="flex-1 rounded-lg border border-orange-300 bg-white px-3 py-2 text-sm uppercase"
          />
          <button
            type="button"
            disabled={previewPending}
            onClick={handleApplyPromo}
            className="rounded-lg bg-brand-orange px-4 py-2 text-sm font-semibold text-white hover:bg-brand-orange-dark disabled:opacity-60"
          >
            {previewPending ? "Checking…" : "Apply"}
          </button>
        </div>
        {promoPreview && "error" in promoPreview && (
          <p className="mt-2 text-sm text-red-600">{promoPreview.error}</p>
        )}
        {promoPreview && "discountAmount" in promoPreview && (
          <p className="mt-2 text-sm font-semibold text-green-600">
            Discount applied: −₱{promoPreview.discountAmount.toFixed(2)}
          </p>
        )}
      </div>

      {state?.error && <p className="text-sm text-red-600">{state.error}</p>}

      <button
        type="submit"
        disabled={pending || addresses.length === 0}
        className="rounded-lg bg-brand-orange px-6 py-3 text-sm font-semibold text-white hover:bg-brand-orange-dark disabled:opacity-60"
      >
        {pending ? "Placing order…" : "Place order"}
      </button>
    </form>
  );
}
