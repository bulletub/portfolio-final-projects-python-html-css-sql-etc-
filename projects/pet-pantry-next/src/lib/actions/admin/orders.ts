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
  const { data, error } = await supabase
    .from("order_groups")
    .update({ status })
    .eq("id", orderGroupId)
    .select("user_id")
    .single();
  if (error) throw error;

  await supabase.from("notifications").insert({
    audience: "customer",
    user_id: data.user_id,
    type: "order_status",
    message: `Your order #${orderGroupId} is now ${status}.`,
    order_group_id: orderGroupId,
  });

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
