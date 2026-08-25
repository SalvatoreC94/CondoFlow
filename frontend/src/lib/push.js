import api from "@/lib/api";

function urlBase64ToUint8Array(base64String) {
  const padding = "=".repeat((4 - (base64String.length % 4)) % 4);
  const base64 = (base64String + padding).replace(/-/g, "+").replace(/_/g, "/");
  const rawData = window.atob(base64);
  return Uint8Array.from([...rawData].map((char) => char.charCodeAt(0)));
}

export function isPushSupported() {
  return "serviceWorker" in navigator && "PushManager" in window;
}

export async function getPushSubscription() {
  if (!isPushSupported()) return null;
  const registration = await navigator.serviceWorker.ready;
  return registration.pushManager.getSubscription();
}

export async function enablePush() {
  const registration = await navigator.serviceWorker.ready;
  const { data } = await api.get("/api/push/vapid-public-key");
  if (!data.public_key) {
    throw new Error("Le notifiche push non sono configurate su questo server.");
  }

  const subscription = await registration.pushManager.subscribe({
    userVisibleOnly: true,
    applicationServerKey: urlBase64ToUint8Array(data.public_key),
  });
  await api.post("/api/push-subscriptions", subscription.toJSON());

  return subscription;
}

export async function disablePush() {
  const subscription = await getPushSubscription();
  if (!subscription) return;

  await api.delete("/api/push-subscriptions", {
    data: { endpoint: subscription.endpoint },
  });
  await subscription.unsubscribe();
}
