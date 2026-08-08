import Image from "next/image";

export default function AuthCard({ children }: { children: React.ReactNode }) {
  return (
    <div
      className="flex min-h-[calc(100vh-4rem)] items-center justify-center px-4 py-12"
      style={{
        backgroundImage: "radial-gradient(var(--color-brand-gold) 1px, transparent 1px)",
        backgroundSize: "30px 30px",
      }}
    >
      <div className="w-full max-w-md overflow-hidden rounded-xl bg-white shadow-xl">
        <div className="flex items-center justify-between bg-black px-8 py-6">
          <span className="font-display text-lg text-white">
            PetPantry<span className="text-yellow-400">+</span>
          </span>
          <Image
            src="/logo.png"
            alt="PetPantry+"
            width={40}
            height={40}
            className="h-10 w-10 rounded-full border-2 border-yellow-400 object-cover"
          />
        </div>
        <div className="p-8">{children}</div>
      </div>
    </div>
  );
}
