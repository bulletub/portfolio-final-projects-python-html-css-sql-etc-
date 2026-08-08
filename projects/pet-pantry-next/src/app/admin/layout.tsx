import Link from "next/link";
import { requireAdmin } from "@/lib/data/session";

export default async function AdminLayout({ children }: { children: React.ReactNode }) {
  await requireAdmin();

  return (
    <main>
      <h1>Admin</h1>
      <nav style={{ display: "flex", gap: "1rem", marginBottom: "1.5rem" }}>
        <Link href="/admin">Dashboard</Link>
        <Link href="/admin/products">Products</Link>
        <Link href="/admin/orders">Orders</Link>
      </nav>
      {children}
    </main>
  );
}
