import "server-only";
import { createClient } from "@/lib/supabase/server";

export type ProductFilters = {
  category?: string;
  search?: string;
};

export async function getProducts(filters: ProductFilters = {}) {
  const supabase = await createClient();
  let query = supabase.from("products").select("*").order("created_at", { ascending: false });

  if (filters.category) {
    query = query.eq("category", filters.category);
  }
  if (filters.search) {
    query = query.ilike("name", `%${filters.search}%`);
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
