import { createClient } from "@/lib/supabase/server";

export default async function AdminDashboardPage() {
  const supabase = await createClient();

  const [{ count: productCount }, { count: lowStockCount }, { count: pendingCount }] =
    await Promise.all([
      supabase.from("products").select("*", { count: "exact", head: true }),
      supabase.from("products").select("*", { count: "exact", head: true }).lte("stock", 5),
      supabase
        .from("order_groups")
        .select("*", { count: "exact", head: true })
        .eq("status", "pending"),
    ]);

  return (
    <div className="product-grid">
      <div className="product-card">
        <div className="product-info">
          <strong>Products</strong>
          <span className="price">{productCount ?? 0}</span>
        </div>
      </div>
      <div className="product-card">
        <div className="product-info">
          <strong>Low stock (&le; 5)</strong>
          <span className="price">{lowStockCount ?? 0}</span>
        </div>
      </div>
      <div className="product-card">
        <div className="product-info">
          <strong>Pending orders</strong>
          <span className="price">{pendingCount ?? 0}</span>
        </div>
      </div>
    </div>
  );
}
