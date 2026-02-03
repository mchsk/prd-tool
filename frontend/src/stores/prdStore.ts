import { create } from 'zustand';
import { api } from '@/lib/api';
import type { Prd, PrdStatus } from '@/types';

interface PrdState {
  prds: Prd[];
  currentPrd: Prd | null;
  isLoading: boolean;
  error: string | null;

  fetchPrds: (status?: PrdStatus) => Promise<void>;
  createPrd: (title?: string) => Promise<Prd>;
  updatePrd: (id: string, data: Partial<Prd>) => Promise<void>;
  deletePrd: (id: string) => Promise<void>;
  getPrd: (id: string) => Promise<Prd>;
  clearError: () => void;
}

export const usePrdStore = create<PrdState>((set) => ({
  prds: [],
  currentPrd: null,
  isLoading: false,
  error: null,

  fetchPrds: async (status?: PrdStatus) => {
    set({ isLoading: true, error: null });
    try {
      const params = status ? `?status=${status}` : '';
      const { data } = await api.get(`/prds${params}`);
      set({ prds: data.data, isLoading: false });
    } catch (error: unknown) {
      const err = error as { message?: string };
      set({ error: err.message || 'Failed to fetch PRDs', isLoading: false });
    }
  },

  createPrd: async (title?: string) => {
    set({ isLoading: true, error: null });
    try {
      const { data } = await api.post('/prds', { title });
      set((state) => ({
        prds: [data, ...state.prds],
        isLoading: false,
      }));
      return data as Prd;
    } catch (error: unknown) {
      const err = error as { message?: string };
      set({ error: err.message || 'Failed to create PRD', isLoading: false });
      throw error;
    }
  },

  updatePrd: async (id: string, updateData: Partial<Prd>) => {
    set({ isLoading: true, error: null });
    try {
      const { data } = await api.put(`/prds/${id}`, updateData);
      set((state) => ({
        prds: state.prds.map((p) => (p.id === id ? data : p)),
        currentPrd: state.currentPrd?.id === id ? data : state.currentPrd,
        isLoading: false,
      }));
    } catch (error: unknown) {
      const err = error as { message?: string };
      set({ error: err.message || 'Failed to update PRD', isLoading: false });
      throw error;
    }
  },

  deletePrd: async (id: string) => {
    set({ isLoading: true, error: null });
    try {
      await api.delete(`/prds/${id}`);
      set((state) => ({
        prds: state.prds.filter((p) => p.id !== id),
        currentPrd: state.currentPrd?.id === id ? null : state.currentPrd,
        isLoading: false,
      }));
    } catch (error: unknown) {
      const err = error as { message?: string };
      set({ error: err.message || 'Failed to delete PRD', isLoading: false });
      throw error;
    }
  },

  getPrd: async (id: string) => {
    set({ isLoading: true, error: null });
    try {
      const { data } = await api.get(`/prds/${id}`);
      set({ currentPrd: data, isLoading: false });
      return data as Prd;
    } catch (error: unknown) {
      const err = error as { message?: string };
      set({ error: err.message || 'Failed to fetch PRD', isLoading: false });
      throw error;
    }
  },

  clearError: () => {
    set({ error: null });
  },
}));
