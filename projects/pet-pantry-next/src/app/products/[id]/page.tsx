import { notFound } from "next/navigation";
import { getProductById } from "@/lib/data/products";
import AddToCartButton from "@/components/add-to-cart-button";

export default async function ProductPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  const product = await getProductById(Number(id));

  if (!product) {
    notFound();
  }

  return (
    <main>
      <img
        src={product.image_path ?? "/products/placeholder.svg"}
        alt={product.name}
        style={{ maxWidth: 400, width: "100%", borderRadius: 8 }}
      />
      <h1>{product.name}</h1>
      <p className="price">₱{product.price.toFixed(2)}</p>
      <p>{product.description}</p>
      <AddToCartButton productId={product.id} stock={product.stock} />
    </main>
  );
}
