import type { Metadata } from "next";
import { Anton, Inter } from "next/font/google";
import SiteHeader from "@/components/site-header";
import SiteFooter from "@/components/site-footer";
import ChatWidgetLoader from "@/components/chat-widget-loader";
import "./globals.css";

const anton = Anton({
  variable: "--font-anton",
  subsets: ["latin"],
  weight: "400",
});

const inter = Inter({
  variable: "--font-inter",
  subsets: ["latin"],
  weight: ["400", "600"],
});

export const metadata: Metadata = {
  title: "PetPantry+",
  description: "Pet supplies, delivered.",
};

export default function RootLayout({ children }: LayoutProps<"/">) {
  return (
    <html lang="en" className={`${anton.variable} ${inter.variable}`}>
      <body className="flex min-h-screen flex-col bg-white font-sans text-neutral-700">
        <SiteHeader />
        <div className="flex-1 pt-16">{children}</div>
        <SiteFooter />
        <ChatWidgetLoader />
      </body>
    </html>
  );
}
