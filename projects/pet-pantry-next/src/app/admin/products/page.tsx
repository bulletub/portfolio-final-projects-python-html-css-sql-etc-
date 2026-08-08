import Link from "next/link";
import { createClient } from "@/lib/supabase/server";
import DeleteProductButton from "./delete-product-button";

export default async function AdminProductsPage() {
  const supabase = await createClient();
  const { data: products, error } = await supabase
    .from("products")
    .select("*")
    .order("created_at", { ascending: false });
  if (error) throw error;

  return (
    <div>
      <p>
        <Link href="/admin/products/new" className="btn">
          + Add product
        </Link>
      </p>
      <table className="cart-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Category</th>
            <th>Price</th>
            <th>Stock</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          {products.map((product) => (
            <tr key={product.id}>
              <td>{product.name}</td>
              <td>{product.category}</td>
              <td>₱{product.price.toFixed(2)}</td>
              <td>{product.stock}</td>
              <td>
                <Link href={`/admin/products/${product.id}/edit`}>Edit</Link>{" "}
                <DeleteProductButton productId={product.id} />
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
