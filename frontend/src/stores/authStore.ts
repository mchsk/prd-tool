import { create } from 'zustand';
import { api } from '@/lib/api';
import type { User } from '@/types';

interface AuthState {
  user: User | null;
  isLoading: boolean;
  isAuthenticated: boolean;
  error: string | null;

  login: () => void;
  logout: () => Promise<void>;
  checkAuth: () => Promise<void>;
  setToken: (token: string) => void;
  clearError: () => void;
}

export const useAuthStore = create<AuthState>((set, get) => ({
  user: null,
  isLoading: true,
  isAuthenticated: false,
  error: null,

  login: () => {
    // Redirect to backend OAuth endpoint
    window.location.href = '/auth/google';
  },

  logout: async () => {
    set({ isLoading: true });
    try {
      await api.post('/logout');
    } catch {
      // Ignore errors, still clear local state
    } finally {
      localStorage.removeItem('auth_token');
      set({ user: null, isAuthenticated: false, isLoading: false });
      window.location.href = '/login';
    }
  },

  checkAuth: async () => {
    set({ isLoading: true, error: null });

    const token = localStorage.getItem('auth_token');
    if (!token) {
      set({ user: null, isAuthenticated: false, isLoading: false });
      return;
    }

    // Set token in axios headers
    api.defaults.headers.common['Authorization'] = `Bearer ${token}`;

    try {
      const { data } = await api.get('/user');
      set({ user: data, isAuthenticated: true, isLoading: false });
    } catch {
      localStorage.removeItem('auth_token');
      set({ user: null, isAuthenticated: false, isLoading: false });
    }
  },

  setToken: (token: string) => {
    localStorage.setItem('auth_token', token);
    api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
    // Immediately check auth to get user data
    get().checkAuth();
  },

  clearError: () => {
    set({ error: null });
  },
}));
