export function useApi() {
  const config = useRuntimeConfig()
  const apiBase = config.public.apiBase as string
  const authStore = useAuthStore()

  function authHeaders(): Record<string, string> {
    const headers: Record<string, string> = {}
    if (authStore.token) {
      headers.Authorization = `Bearer ${authStore.token}`
    }
    return headers
  }

  async function apiFetch<T>(
    path: string,
    options: {
      method?: string
      body?: Record<string, unknown>
      query?: Record<string, string | number>
    } = {}
  ): Promise<T> {
    const { method = 'GET', body, query } = options
    const url = path.startsWith('http') ? path : `${apiBase}${path.startsWith('/') ? '' : '/'}${path}`
    return $fetch<T>(url, {
      method,
      body,
      query,
      headers: authHeaders(),
    })
  }

  async function apiFetchForm<T>(
    path: string,
    formData: FormData,
    options: { method?: string } = {}
  ): Promise<T> {
    const { method = 'POST' } = options
    const url = path.startsWith('http') ? path : `${apiBase}${path.startsWith('/') ? '' : '/'}${path}`
    const headers = authHeaders()
    return $fetch<T>(url, {
      method,
      body: formData,
      headers,
    })
  }

  return { apiBase, authHeaders, apiFetch, apiFetchForm }
}
