"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { createClient } from "@/lib/supabase/server";
import { requireAdmin } from "@/lib/data/session";

export type ProductFormState = { error: string } | undefined;

function readProductFields(formData: FormData) {
  return {
    name: String(formData.get("name") ?? "").trim(),
    category: String(formData.get("category") ?? "").trim(),
    subcategory: String(formData.get("subcategory") ?? "").trim() || null,
    description: String(formData.get("description") ?? "").trim() || null,
    price: Number(formData.get("price")),
    stock: Number(formData.get("stock")),
  };
}

async function uploadImageIfProvided(formData: FormData) {
  const file = formData.get("image");
  if (!(file instanceof File) || file.size === 0) return null;

  const supabase = await createClient();
  const path = `${Date.now()}-${file.name.replace(/[^a-zA-Z0-9._-]/g, "_")}`;
  const { error } = await supabase.storage.from("product-images").upload(path, file);
  if (error) throw error;

  return supabase.storage.from("product-images").getPublicUrl(path).data.publicUrl;
}

export async function createProduct(
  _prevState: ProductFormState,
  formData: FormData
): Promise<ProductFormState> {
  await requireAdmin();
  const fields = readProductFields(formData);

  if (!fields.name || !fields.category || !Number.isFinite(fields.price) || !Number.isFinite(fields.stock)) {
    return { error: "Name, category, price, and stock are required." };
  }

  const supabase = await createClient();
  const image_path = await uploadImageIfProvided(formData);

  const { error } = await supabase.from("products").insert({ ...fields, image_path });
  if (error) return { error: error.message };

  revalidatePath("/admin/products");
  revalidatePath("/shop");
  redirect("/admin/products");
}

export async function updateProduct(
  productId: number,
  _prevState: ProductFormState,
  formData: FormData
): Promise<ProductFormState> {
  await requireAdmin();
  const fields = readProductFields(formData);

  if (!fields.name || !fields.category || !Number.isFinite(fields.price) || !Number.isFinite(fields.stock)) {
    return { error: "Name, category, price, and stock are required." };
  }

  const supabase = await createClient();
  const image_path = await uploadImageIfProvided(formData);

  const { error } = await supabase
    .from("products")
    .update(image_path ? { ...fields, image_path } : fields)
    .eq("id", productId);
  if (error) return { error: error.message };

  revalidatePath("/admin/products");
  revalidatePath(`/products/${productId}`);
  revalidatePath("/shop");
  redirect("/admin/products");
}

export async function deleteProduct(productId: number) {
  await requireAdmin();
  const supabase = await createClient();

  const { error } = await supabase.from("products").delete().eq("id", productId);
  if (error) {
    if (error.code === "23503") {
      throw new Error("Can't delete — this product has order history.");
    }
    throw error;
  }

  revalidatePath("/admin/products");
  revalidatePath("/shop");
}
