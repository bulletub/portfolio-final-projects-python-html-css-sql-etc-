import { notFound } from "next/navigation";
import { getProductById } from "@/lib/data/products";
import { updateProduct } from "@/lib/actions/admin/products";
import ProductForm from "../../product-form";

export default async function EditProductPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  const product = await getProductById(Number(id));
  if (!product) notFound();

  return (
    <div>
      <h2>Edit product</h2>
      <ProductForm action={updateProduct.bind(null, product.id)} product={product} />
    </div>
  );
}
