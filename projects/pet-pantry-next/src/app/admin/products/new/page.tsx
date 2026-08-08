import { createProduct } from "@/lib/actions/admin/products";
import ProductForm from "../product-form";

export default function NewProductPage() {
  return (
    <div>
      <h2>Add product</h2>
      <ProductForm action={createProduct} />
    </div>
  );
}
