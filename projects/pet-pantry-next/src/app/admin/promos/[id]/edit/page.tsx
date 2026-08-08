import { notFound } from "next/navigation";
import { createClient } from "@/lib/supabase/server";
import { updatePromo } from "@/lib/actions/admin/promos";
import PromoForm from "../../promo-form";

export default async function EditPromoPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  const supabase = await createClient();
  const { data: promo, error } = await supabase
    .from("promos")
    .select("*")
    .eq("id", Number(id))
    .maybeSingle();
  if (error) throw error;
  if (!promo) notFound();

  return (
    <div>
      <h2>Edit promo</h2>
      <PromoForm action={updatePromo.bind(null, promo.id)} promo={promo} />
    </div>
  );
}
