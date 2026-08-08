"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { createClient } from "@/lib/supabase/server";
import { getSessionUser } from "@/lib/data/session";

async function requireUser() {
  const user = await getSessionUser();
  if (!user) redirect("/login?next=/cart");
  return user;
}

export async function addToCart(productId: number, quantity = 1) {
  const user = await requireUser();
  const supabase = await createClient();

  const { data: product, error: productError } = await supabase
    .from("products")
    .select("stock")
    .eq("id", productId)
    .single();
  if (productError) throw productError;

  const { data: existing, error: existingError } = await supabase
    .from("cart")
    .select("id, quantity")
    .eq("user_id", user.id)
    .eq("product_id", productId)
    .maybeSingle();
  if (existingError) throw existingError;

  const nextQuantity = Math.min((existing?.quantity ?? 0) + quantity, product.stock);

  if (existing) {
    const { error } = await supabase
      .from("cart")
      .update({ quantity: nextQuantity })
      .eq("id", existing.id);
    if (error) throw error;
  } else {
    const { error } = await supabase
      .from("cart")
      .insert({ user_id: user.id, product_id: productId, quantity: Math.min(quantity, product.stock) });
    if (error) throw error;
  }

  revalidatePath("/cart");
}

export async function updateCartItemQuantity(cartId: number, quantity: number) {
  await requireUser();
  const supabase = await createClient();

  if (quantity <= 0) {
    const { error } = await supabase.from("cart").delete().eq("id", cartId);
    if (error) throw error;
  } else {
    const { error } = await supabase.from("cart").update({ quantity }).eq("id", cartId);
    if (error) throw error;
  }

  revalidatePath("/cart");
}

export async function removeCartItem(cartId: number) {
  await requireUser();
  const supabase = await createClient();
  const { error } = await supabase.from("cart").delete().eq("id", cartId);
  if (error) throw error;

  revalidatePath("/cart");
}
