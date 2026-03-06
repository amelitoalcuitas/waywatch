import { defineStore } from 'pinia';

const TOKEN_KEY = 'waywatch_token';
const USER_KEY = 'waywatch_user';

export interface User {
  id: number;
  name: string;
  email: string;
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null as User | null,
    token: null as string | null,
    pending: false
  }),

  getters: {
    isAuthenticated: (state) => !!state.token
  },

  actions: {
    init() {
      if (import.meta.client) {
        const stored = localStorage.getItem(TOKEN_KEY);
        const storedUser = localStorage.getItem(USER_KEY);
        if (stored) {
          this.token = stored;
          this.user = storedUser ? (JSON.parse(storedUser) as User) : null;
        }
      }
    },

    setAuth(user: User, token: string) {
      this.user = user;
      this.token = token;
      if (import.meta.client) {
        localStorage.setItem(TOKEN_KEY, token);
        localStorage.setItem(USER_KEY, JSON.stringify(user));
      }
    },

    clearAuth() {
      this.user = null;
      this.token = null;
      if (import.meta.client) {
        localStorage.removeItem(TOKEN_KEY);
        localStorage.removeItem(USER_KEY);
      }
    },

    async login(email: string, password: string) {
      this.pending = true;
      try {
        const config = useRuntimeConfig();
        const apiBase = config.public.apiBase as string;
        const res = await $fetch<{ user: User; token: string }>(
          `${apiBase}/login`,
          {
            method: 'POST',
            body: { email, password }
          }
        );
        this.setAuth(res.user, res.token);
        return res;
      } finally {
        this.pending = false;
      }
    },

    async register(
      name: string,
      email: string,
      password: string,
      password_confirmation: string
    ) {
      this.pending = true;
      try {
        const config = useRuntimeConfig();
        const apiBase = config.public.apiBase as string;
        const res = await $fetch<{ user: User; token: string }>(
          `${apiBase}/register`,
          {
            method: 'POST',
            body: { name, email, password, password_confirmation }
          }
        );
        this.setAuth(res.user, res.token);
        return res;
      } finally {
        this.pending = false;
      }
    },

    async logout() {
      if (!this.token) {
        this.clearAuth();
        return;
      }
      try {
        const config = useRuntimeConfig();
        const apiBase = config.public.apiBase as string;
        await $fetch(`${apiBase}/logout`, {
          method: 'POST',
          headers: {
            Authorization: `Bearer ${this.token}`
          }
        });
      } catch {
        // Clear auth even if logout request fails (e.g. token expired)
      } finally {
        this.clearAuth();
      }
    }
  }
});
