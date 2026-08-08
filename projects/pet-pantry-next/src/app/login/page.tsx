import { Suspense } from "react";
import Link from "next/link";
import LoginForm from "./login-form";

export default function LoginPage() {
  return (
    <main className="auth-page">
      <h1>Log in</h1>
      <Suspense fallback={null}>
        <LoginForm />
      </Suspense>
      <p>
        Don&apos;t have an account? <Link href="/register">Register</Link>
      </p>
    </main>
  );
}
