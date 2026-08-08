"use client";

import { useState } from "react";

export default function CopyCodeButton({ code }: { code: string }) {
  const [copied, setCopied] = useState(false);

  return (
    <button
      type="button"
      onClick={async () => {
        await navigator.clipboard.writeText(code);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
      }}
      className="rounded-full bg-brand-orange px-3 py-1 text-xs font-semibold text-white hover:bg-brand-orange-dark"
    >
      {copied ? "Copied!" : "📋 Copy"}
    </button>
  );
}
