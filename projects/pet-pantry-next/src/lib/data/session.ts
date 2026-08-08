import "server-only";
import { cache } from "react";
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
