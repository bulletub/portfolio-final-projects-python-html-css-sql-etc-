import "server-only";
import { createClient } from "@/lib/supabase/server";

export type ProductSort = "default" | "price_asc" | "price_desc" | "name_asc" | "name_desc";

export type ProductFilters = {
  category?: string;
  subcategory?: string;
  search?: string;
  sort?: ProductSort;
};

export async function getProducts(filters: ProductFilters = {}) {
  const supabase = await createClient();
  let query = supabase.from("products").select("*");

  if (filters.category) {
    query = query.eq("category", filters.category);
  }
  if (filters.subcategory) {
    query = query.eq("subcategory", filters.subcategory);
  }
  if (filters.search) {
    query = query.ilike("name", `%${filters.search}%`);
  }

  switch (filters.sort) {
    case "price_asc":
      query = query.order("price", { ascending: true });
      break;
    case "price_desc":
      query = query.order("price", { ascending: false });
      break;
    case "name_asc":
      query = query.order("name", { ascending: true });
      break;
    case "name_desc":
      query = query.order("name", { ascending: false });
      break;
    default:
      query = query.order("created_at", { ascending: false });
  }

  const { data, error } = await query;
  if (error) throw error;
  return data;
}

export async function getProductById(id: number) {
  const supabase = await createClient();
  const { data, error } = await supabase
    .from("products")
    .select("*")
    .eq("id", id)
    .maybeSingle();

  if (error) throw error;
  return data;
}

export async function getCategories() {
  const supabase = await createClient();
  const { data, error } = await supabase.from("products").select("category");
  if (error) throw error;
  return Array.from(new Set(data.map((row) => row.category))).sort();
}

export async function getSubcategories() {
  const supabase = await createClient();
  const { data, error } = await supabase.from("products").select("subcategory");
  if (error) throw error;
  return Array.from(new Set(data.map((row) => row.subcategory).filter((s): s is string => !!s))).sort();
}

export async function getBestSellers(limit = 8) {
  const supabase = await createClient();
  const { data, error } = await supabase
    .from("orders")
    .select("product_id, quantity, order_groups!inner(status)")
    .eq("order_groups.status", "completed");
  if (error) throw error;

  const totals = new Map<number, number>();
  for (const row of data) {
    totals.set(row.product_id, (totals.get(row.product_id) ?? 0) + row.quantity);
  }
  const topIds = [...totals.entries()]
    .sort((a, b) => b[1] - a[1])
    .slice(0, limit)
    .map(([id]) => id);
  if (topIds.length === 0) return [];

  const { data: products, error: productsError } = await supabase
    .from("products")
    .select("*")
    .in("id", topIds);
  if (productsError) throw productsError;

  const order = new Map(topIds.map((id, i) => [id, i]));
  return [...products].sort((a, b) => (order.get(a.id) ?? 0) - (order.get(b.id) ?? 0));
}

export async function getNewArrivals(limit = 8) {
  const supabase = await createClient();
  const { data, error } = await supabase
    .from("products")
    .select("*")
    .order("created_at", { ascending: false })
    .limit(limit);
  if (error) throw error;
  return data;
}

export async function getFeaturedProducts(limit = 8) {
  const supabase = await createClient();
  const { data, error } = await supabase
    .from("products")
    .select("*")
    .order("price", { ascending: false })
    .limit(limit);
  if (error) throw error;
  return data;
}
