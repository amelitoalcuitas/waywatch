const LOCAL_API_HOST_RE = /^https?:\/\/(localhost|127\.0\.0\.1)(:\d+)?(\/|$)/i;
const LOCAL_ORIGIN_HOSTS = new Set(['localhost', '127.0.0.1']);

/**
 * Resolve an API base that works both on desktop localhost and LAN mobile testing.
 * If a localhost absolute URL leaks into a phone build, force relative /api.
 */
export function useApiBase(): string {
  const config = useRuntimeConfig();
  const rawBase = String(config.public.apiBase || '/api');

  if (!import.meta.client) {
    return rawBase;
  }

  if (LOCAL_API_HOST_RE.test(rawBase)) {
    // Keep localhost absolute API for desktop local dev, but switch to
    // same-origin /api when opened from mobile/ngrok where localhost is unreachable.
    return LOCAL_ORIGIN_HOSTS.has(window.location.hostname) ? rawBase : '/api';
  }

  return rawBase;
}
