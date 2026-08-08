"use client";

import { useActionState } from "react";
import type { ProductFormState } from "@/lib/actions/admin/products";

type ProductFormAction = (state: ProductFormState, formData: FormData) => Promise<ProductFormState>;

type ExistingProduct = {
  name: string;
  category: string;
  subcategory: string | null;
  description: string | null;
  price: number;
  stock: number;
  image_path: string | null;
};

export default function ProductForm({
  action,
  product,
}: {
  action: ProductFormAction;
  product?: ExistingProduct;
}) {
  const [state, formAction, pending] = useActionState(action, undefined);

  return (
    <form action={formAction} className="admin-form">
      <div>
        <label htmlFor="name">Name</label>
        <input id="name" name="name" defaultValue={product?.name} required />
      </div>
      <div>
        <label htmlFor="category">Category</label>
        <input id="category" name="category" defaultValue={product?.category} required />
      </div>
      <div>
        <label htmlFor="subcategory">Subcategory</label>
        <input id="subcategory" name="subcategory" defaultValue={product?.subcategory ?? ""} />
      </div>
      <div>
        <label htmlFor="description">Description</label>
        <textarea id="description" name="description" defaultValue={product?.description ?? ""} />
      </div>
      <div>
        <label htmlFor="price">Price</label>
        <input id="price" name="price" type="number" step="0.01" min="0" defaultValue={product?.price} required />
      </div>
      <div>
        <label htmlFor="stock">Stock</label>
        <input id="stock" name="stock" type="number" min="0" defaultValue={product?.stock} required />
      </div>
      <div>
        <label htmlFor="image">Image {product ? "(leave empty to keep current)" : ""}</label>
        {product?.image_path && (
          <img src={product.image_path} alt="" style={{ width: 100, display: "block", marginBottom: "0.5rem" }} />
        )}
        <input id="image" name="image" type="file" accept="image/*" />
      </div>

      {state?.error && <p role="alert">{state.error}</p>}

      <button type="submit" disabled={pending}>
        {pending ? "Saving…" : "Save product"}
      </button>
    </form>
  );
}
