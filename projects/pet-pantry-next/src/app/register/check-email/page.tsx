import AuthCard from "@/components/auth-card";

export default function CheckEmailPage() {
  return (
    <AuthCard>
      <h1 className="mb-2 text-xl font-bold text-neutral-900">Check your email</h1>
      <p className="text-sm text-neutral-600">
        We sent you a confirmation link. Click it to finish creating your account.
      </p>
    </AuthCard>
  );
}
