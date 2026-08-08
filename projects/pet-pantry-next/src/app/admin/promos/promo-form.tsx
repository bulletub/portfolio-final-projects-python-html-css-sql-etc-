"use client";

import { useActionState } from "react";
import type { PromoFormState } from "@/lib/actions/admin/promos";

type PromoFormAction = (state: PromoFormState, formData: FormData) => Promise<PromoFormState>;

type ExistingPromo = {
  code: string;
  title: string;
  description: string | null;
  discount_type: string;
  discount_value: number;
  min_purchase: number;
  max_discount: number | null;
  usage_limit: number | null;
  start_date: string | null;
  end_date: string | null;
  active: boolean;
};

export default function PromoForm({
  action,
  promo,
}: {
  action: PromoFormAction;
  promo?: ExistingPromo;
}) {
  const [state, formAction, pending] = useActionState(action, undefined);

  return (
    <form action={formAction} className="admin-form">
      <div>
        <label htmlFor="code">Code</label>
        <input id="code" name="code" defaultValue={promo?.code} required />
      </div>
      <div>
        <label htmlFor="title">Title</label>
        <input id="title" name="title" defaultValue={promo?.title} required />
      </div>
      <div>
        <label htmlFor="description">Description</label>
        <textarea id="description" name="description" defaultValue={promo?.description ?? ""} />
      </div>
      <div>
        <label htmlFor="discount_type">Discount type</label>
        <select id="discount_type" name="discount_type" defaultValue={promo?.discount_type ?? "percent"}>
          <option value="percent">Percent off</option>
          <option value="fixed">Fixed amount off</option>
        </select>
      </div>
      <div>
        <label htmlFor="discount_value">Discount value</label>
        <input
          id="discount_value"
          name="discount_value"
          type="number"
          step="0.01"
          min="0"
          defaultValue={promo?.discount_value}
          required
        />
      </div>
      <div>
        <label htmlFor="min_purchase">Minimum purchase</label>
        <input
          id="min_purchase"
          name="min_purchase"
          type="number"
          step="0.01"
          min="0"
          defaultValue={promo?.min_purchase ?? 0}
        />
      </div>
      <div>
        <label htmlFor="max_discount">Max discount (percent codes only, optional)</label>
        <input
          id="max_discount"
          name="max_discount"
          type="number"
          step="0.01"
          min="0"
          defaultValue={promo?.max_discount ?? ""}
        />
      </div>
      <div>
        <label htmlFor="usage_limit">Usage limit (optional)</label>
        <input
          id="usage_limit"
          name="usage_limit"
          type="number"
          min="0"
          defaultValue={promo?.usage_limit ?? ""}
        />
      </div>
      <div>
        <label htmlFor="start_date">Start date (optional)</label>
        <input id="start_date" name="start_date" type="date" defaultValue={promo?.start_date ?? ""} />
      </div>
      <div>
        <label htmlFor="end_date">End date (optional)</label>
        <input id="end_date" name="end_date" type="date" defaultValue={promo?.end_date ?? ""} />
      </div>
      <div>
        <label>
          <input type="checkbox" name="active" defaultChecked={promo?.active ?? true} /> Active
        </label>
      </div>

      {state?.error && <p role="alert">{state.error}</p>}

      <button type="submit" disabled={pending}>
        {pending ? "Saving…" : "Save promo"}
      </button>
    </form>
  );
}
