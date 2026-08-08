"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { createClient } from "@/lib/supabase/server";
import { requireAdmin } from "@/lib/data/session";

export type PromoFormState = { error: string } | undefined;

function readPromoFields(formData: FormData) {
  return {
    code: String(formData.get("code") ?? "").trim().toUpperCase(),
    title: String(formData.get("title") ?? "").trim(),
    description: String(formData.get("description") ?? "").trim() || null,
    discount_type: String(formData.get("discount_type") ?? "percent"),
    discount_value: Number(formData.get("discount_value")),
    min_purchase: Number(formData.get("min_purchase") ?? 0),
    max_discount: formData.get("max_discount") ? Number(formData.get("max_discount")) : null,
    usage_limit: formData.get("usage_limit") ? Number(formData.get("usage_limit")) : null,
    start_date: String(formData.get("start_date") ?? "") || null,
    end_date: String(formData.get("end_date") ?? "") || null,
    active: formData.get("active") === "on",
  };
}

export async function createPromo(
  _prevState: PromoFormState,
  formData: FormData
): Promise<PromoFormState> {
  await requireAdmin();
  const fields = readPromoFields(formData);

  if (!fields.code || !fields.title || !Number.isFinite(fields.discount_value)) {
    return { error: "Code, title, and discount value are required." };
  }

  const supabase = await createClient();
  const { error } = await supabase.from("promos").insert(fields);
  if (error) return { error: error.message };

  revalidatePath("/admin/promos");
  redirect("/admin/promos");
}

export async function updatePromo(
  promoId: number,
  _prevState: PromoFormState,
  formData: FormData
): Promise<PromoFormState> {
  await requireAdmin();
  const fields = readPromoFields(formData);

  if (!fields.code || !fields.title || !Number.isFinite(fields.discount_value)) {
    return { error: "Code, title, and discount value are required." };
  }

  const supabase = await createClient();
  const { error } = await supabase.from("promos").update(fields).eq("id", promoId);
  if (error) return { error: error.message };

  revalidatePath("/admin/promos");
  redirect("/admin/promos");
}

export async function deletePromo(promoId: number) {
  await requireAdmin();
  const supabase = await createClient();
  const { error } = await supabase.from("promos").delete().eq("id", promoId);
  if (error) throw error;

  revalidatePath("/admin/promos");
}
