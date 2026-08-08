import Link from "next/link";
import { getSessionUser, getSessionProfile } from "@/lib/data/session";
import { getNotifications } from "@/lib/data/notifications";
import { logout } from "@/lib/actions/auth";
import NotificationBell from "./notification-bell";

export default async function SiteHeader() {
  const user = await getSessionUser();
  const profile = user ? await getSessionProfile() : null;
  const notifications = user ? await getNotifications() : [];
  const isAdmin = profile?.account_type === "admin";

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
            {isAdmin && <Link href="/admin">Admin</Link>}
            <NotificationBell
              initial={notifications}
              filter={isAdmin ? "audience=eq.admin" : `user_id=eq.${user.id}`}
              isAdmin={isAdmin}
            />
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
