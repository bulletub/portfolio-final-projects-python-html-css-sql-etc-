"use server";

import { revalidatePath } from "next/cache";
import { createClient } from "@/lib/supabase/server";
import { requireAdmin } from "@/lib/data/session";

const STATUSES = ["pending", "shipping", "completed", "cancelled"] as const;

export async function updateOrderStatus(orderGroupId: number, status: string) {
  await requireAdmin();
  if (!STATUSES.includes(status as (typeof STATUSES)[number])) {
    throw new Error("Invalid status.");
  }

  const supabase = await createClient();
  const { error } = await supabase
    .from("order_groups")
    .update({ status })
    .eq("id", orderGroupId);
  if (error) throw error;

  revalidatePath("/admin/orders");
}

export async function setPaymentStatus(orderGroupId: number, paymentCode: "verified" | "failed") {
  await requireAdmin();
  const supabase = await createClient();

  const { error } = await supabase
    .from("order_groups")
    .update({ payment_code: paymentCode })
    .eq("id", orderGroupId);
  if (error) throw error;

  revalidatePath("/admin/orders");
}
