import "server-only";
import { cache } from "react";
import { redirect } from "next/navigation";
import { createClient } from "@/lib/supabase/server";

export const getSessionUser = cache(async () => {
  const supabase = await createClient();
  // getClaims() revalidates the JWT against Supabase's public keys; unlike
  // getSession(), it can be trusted for auth/authorization checks.
  const { data } = await supabase.auth.getClaims();
  const claims = data?.claims;
  if (!claims) return null;

  return { id: claims.sub as string, email: claims.email as string | undefined };
});

export const getSessionProfile = cache(async () => {
  const user = await getSessionUser();
  if (!user) return null;

  const supabase = await createClient();
  const { data, error } = await supabase
    .from("profiles")
    .select("id, name, account_type")
    .eq("id", user.id)
    .single();

  if (error) throw error;
  return data;
});

export async function requireAdmin() {
  const profile = await getSessionProfile();
  if (!profile || profile.account_type !== "admin") {
    redirect("/");
  }
  return profile;
}
