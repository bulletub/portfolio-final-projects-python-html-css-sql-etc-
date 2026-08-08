import Link from "next/link";
import { createClient } from "@/lib/supabase/server";
import DeletePromoButton from "./delete-promo-button";

export default async function AdminPromosPage() {
  const supabase = await createClient();
  const { data: promos, error } = await supabase
    .from("promos")
    .select("*")
    .order("created_at", { ascending: false });
  if (error) throw error;

  return (
    <div>
      <p>
        <Link href="/admin/promos/new" className="btn">
          + Add promo
        </Link>
      </p>
      <table className="cart-table">
        <thead>
          <tr>
            <th>Code</th>
            <th>Title</th>
            <th>Discount</th>
            <th>Usage</th>
            <th>Active</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          {promos.map((promo) => (
            <tr key={promo.id}>
              <td>{promo.code}</td>
              <td>{promo.title}</td>
              <td>
                {promo.discount_type === "percent"
                  ? `${promo.discount_value}%`
                  : `₱${promo.discount_value.toFixed(2)}`}
              </td>
              <td>
                {promo.usage_count}
                {promo.usage_limit ? ` / ${promo.usage_limit}` : ""}
              </td>
              <td>{promo.active ? "Yes" : "No"}</td>
              <td>
                <Link href={`/admin/promos/${promo.id}/edit`}>Edit</Link>{" "}
                <DeletePromoButton promoId={promo.id} />
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
