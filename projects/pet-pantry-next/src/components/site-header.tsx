import Link from "next/link";
import { getSessionUser } from "@/lib/data/session";
import { logout } from "@/lib/actions/auth";

export default async function SiteHeader() {
  const user = await getSessionUser();

  return (
    <header className="site-header">
      <Link href="/" className="brand">
        Pet Pantry
      </Link>
      <nav>
        <Link href="/shop">Shop</Link>
        <Link href="/cart">Cart</Link>
        {user ? (
          <>
            <Link href="/orders">Orders</Link>
            <form action={logout}>
              <button type="submit">Log out</button>
            </form>
          </>
        ) : (
          <Link href="/login">Log in</Link>
        )}
      </nav>
    </header>
  );
}
