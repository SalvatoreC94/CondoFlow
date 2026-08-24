import axios from "axios";

const baseURL = import.meta.env.VITE_API_URL || "";

const api = axios.create({
  baseURL,
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    Accept: "application/json",
  },
});

let csrfReady = null;

/**
 * Laravel Sanctum's SPA auth needs a CSRF cookie before any stateful
 * (session-based) mutation. We fetch it lazily, once, and reuse the promise
 * so concurrent requests don't trigger duplicate cookie fetches.
 */
export function ensureCsrfCookie() {
  if (!csrfReady) {
    csrfReady = api.get("/sanctum/csrf-cookie");
  }
  return csrfReady;
}

api.interceptors.request.use(async (config) => {
  const method = (config.method || "get").toLowerCase();
  if (method !== "get") {
    await ensureCsrfCookie();
  }
  return config;
});

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      csrfReady = null;
    }
    return Promise.reject(error);
  },
);

export default api;
