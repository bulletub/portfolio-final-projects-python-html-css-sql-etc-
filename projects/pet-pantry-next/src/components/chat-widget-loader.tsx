import { getSessionProfile } from "@/lib/data/session";
import ChatWidget from "./chat-widget";

export default async function ChatWidgetLoader() {
  const profile = await getSessionProfile();
  if (!profile || profile.account_type === "admin") return null;
  return <ChatWidget />;
}
