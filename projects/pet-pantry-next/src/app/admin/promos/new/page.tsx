import { createPromo } from "@/lib/actions/admin/promos";
import PromoForm from "../promo-form";

export default function NewPromoPage() {
  return (
    <div>
      <h2>Add promo</h2>
      <PromoForm action={createPromo} />
    </div>
  );
}
