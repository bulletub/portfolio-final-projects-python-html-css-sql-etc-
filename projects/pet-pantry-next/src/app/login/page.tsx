import { Suspense } from "react";
import Link from "next/link";
import AuthCard from "@/components/auth-card";
import LoginForm from "./login-form";

export default function LoginPage() {
  return (
    <AuthCard>
      <h1 className="mb-4 text-xl font-bold text-neutral-900">Log in</h1>
      <Suspense fallback={null}>
        <LoginForm />
      </Suspense>
      <p className="mt-4 text-center text-sm text-neutral-500">
        Don&apos;t have an account?{" "}
        <Link href="/register" className="font-semibold text-brand-orange">
          Register
        </Link>
      </p>
    </AuthCard>
  );
}
