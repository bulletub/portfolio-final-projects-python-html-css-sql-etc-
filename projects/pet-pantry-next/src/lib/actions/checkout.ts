"use server";

import { redirect } from "next/navigation";
import { revalidatePath } from "next/cache";
import { createClient } from "@/lib/supabase/server";
import { getSessionUser } from "@/lib/data/session";

export type CheckoutFormState = { error: string } | undefined;
export type PromoPreviewState =
  | { error: string }
  | { discountType: string; discountValue: number; discountAmount: number }
  | undefined;

async function requireUser() {
  const user = await getSessionUser();
  if (!user) redirect("/login?next=/checkout");
  return user;
}

export async function addAddress(formData: FormData) {
  const user = await requireUser();
  const supabase = await createClient();

  const fullName = String(formData.get("full_name") ?? "").trim();
  const address = String(formData.get("address") ?? "").trim();
  const isDefault = formData.get("is_default") === "on";

  if (!fullName || !address) return;

  const { error } = await supabase
    .from("user_addresses")
    .insert({ user_id: user.id, full_name: fullName, address, is_default: isDefault });
  if (error) throw error;

  revalidatePath("/checkout");
}

export async function previewPromo(code: string, subtotal: number): Promise<PromoPreviewState> {
  await requireUser();
  if (!code.trim()) return undefined;

  const supabase = await createClient();
  const { data, error } = await supabase
    .rpc("preview_promo", { p_code: code.trim(), p_subtotal: subtotal })
    .single();

  if (error) return { error: error.message };
  return {
    discountType: data.discount_type,
    discountValue: data.discount_value,
    discountAmount: data.discount_amount,
  };
}

export async function placeOrder(
  _prevState: CheckoutFormState,
  formData: FormData
): Promise<CheckoutFormState> {
  await requireUser();
  const supabase = await createClient();

  const addressId = formData.get("address_id");
  const cartIdsRaw = String(formData.get("cart_ids") ?? "");
  const paymentMethod = String(formData.get("payment_method") ?? "");
  const promoCode = String(formData.get("promo_code") ?? "").trim();

  const cartIds = cartIdsRaw
    .split(",")
    .map((id) => Number(id.trim()))
    .filter((id) => Number.isFinite(id));

  if (cartIds.length === 0) {
    return { error: "Your cart is empty." };
  }
  if (!addressId) {
    return { error: "Select or add a shipping address." };
  }
  if (!paymentMethod) {
    return { error: "Select a payment method." };
  }

  const { data: addressRow, error: addressError } = await supabase
    .from("user_addresses")
    .select("full_name, address")
    .eq("id", Number(addressId))
    .single();
  if (addressError) return { error: "Selected address not found." };

  const { data: orderGroupId, error } = await supabase.rpc("create_order", {
    p_cart_ids: cartIds,
    p_address: `${addressRow.full_name}, ${addressRow.address}`,
    p_payment_method: paymentMethod,
    p_payment_code: paymentMethod === "bank_transfer" ? "pending_verification" : undefined,
    p_promo_code: promoCode || undefined,
  });

  if (error) {
    return { error: error.message };
  }

  redirect(`/orders?placed=${orderGroupId}`);
}
