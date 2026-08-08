import type { Metadata } from "next";
import SiteHeader from "@/components/site-header";
import ChatWidgetLoader from "@/components/chat-widget-loader";
import "./globals.css";

export const metadata: Metadata = {
  title: "Pet Pantry",
  description: "Pet supplies, delivered.",
};

export default function RootLayout({ children }: LayoutProps<"/">) {
  return (
    <html lang="en">
      <body>
        <SiteHeader />
        {children}
        <ChatWidgetLoader />
      </body>
    </html>
  );
}
