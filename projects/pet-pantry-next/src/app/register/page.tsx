import Link from "next/link";
import AuthCard from "@/components/auth-card";
import RegisterForm from "./register-form";

export default function RegisterPage() {
  return (
    <AuthCard>
      <h1 className="mb-4 text-xl font-bold text-neutral-900">Create an account</h1>
      <RegisterForm />
      <p className="mt-4 text-center text-sm text-neutral-500">
        Already have an account?{" "}
        <Link href="/login" className="font-semibold text-brand-orange">
          Log in
        </Link>
      </p>
    </AuthCard>
  );
}
