import { create } from 'zustand';
import { api } from '@/lib/api';
import type { Message } from '@/types';

interface ChatState {
  messages: Message[];
  isLoading: boolean;
  isStreaming: boolean;
  streamingContent: string;
  error: string | null;

  fetchMessages: (prdId: string) => Promise<void>;
  sendMessage: (prdId: string, content: string) => Promise<void>;
  applyUpdate: (prdId: string, messageId: string) => Promise<void>;
  clearMessages: () => void;
  clearError: () => void;
}

export const useChatStore = create<ChatState>((set, get) => ({
  messages: [],
  isLoading: false,
  isStreaming: false,
  streamingContent: '',
  error: null,

  fetchMessages: async (prdId: string) => {
    set({ isLoading: true, error: null });
    try {
      const { data } = await api.get(`/prds/${prdId}/messages`);
      set({ messages: data.data, isLoading: false });
    } catch (error: unknown) {
      const err = error as { message?: string };
      set({ error: err.message || 'Failed to fetch messages', isLoading: false });
    }
  },

  sendMessage: async (prdId: string, content: string) => {
    set({ isLoading: true, isStreaming: true, streamingContent: '', error: null });

    // Optimistically add user message
    const tempUserMessage: Message = {
      id: `temp-${Date.now()}`,
      role: 'user',
      content,
      prd_update_suggestion: null,
      update_applied: false,
      token_count: Math.ceil(content.length / 4),
      created_at: new Date().toISOString(),
    };

    set((state) => ({
      messages: [...state.messages, tempUserMessage],
    }));

    try {
      // Use fetch for SSE
      const token = localStorage.getItem('auth_token');
      const response = await fetch(`/api/prds/${prdId}/messages`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'text/event-stream',
          'Authorization': `Bearer ${token}`,
        },
        body: JSON.stringify({ content }),
      });

      if (!response.ok) {
        throw new Error('Failed to send message');
      }

      const reader = response.body?.getReader();
      const decoder = new TextDecoder();
      let fullContent = '';

      if (reader) {
        while (true) {
          const { done, value } = await reader.read();
          if (done) break;

          const chunk = decoder.decode(value);
          const lines = chunk.split('\n');

          for (const line of lines) {
            if (line.startsWith('data: ')) {
              try {
                const data = JSON.parse(line.slice(6));
                
                if (data.error) {
                  throw new Error(data.error);
                }
                
                if (data.text) {
                  fullContent += data.text;
                  set({ streamingContent: fullContent });
                }
                
                if (data.done) {
                  // Fetch updated messages to get the saved assistant message
                  await get().fetchMessages(prdId);
                }
              } catch {
                // Ignore parse errors for incomplete chunks
              }
            }
          }
        }
      }

      set({ isLoading: false, isStreaming: false, streamingContent: '' });

    } catch (error: unknown) {
      const err = error as { message?: string };
      set({
        error: err.message || 'Failed to send message',
        isLoading: false,
        isStreaming: false,
        streamingContent: '',
      });
      // Remove optimistic message on error
      set((state) => ({
        messages: state.messages.filter((m) => m.id !== tempUserMessage.id),
      }));
    }
  },

  applyUpdate: async (prdId: string, messageId: string) => {
    set({ isLoading: true, error: null });
    try {
      await api.post(`/prds/${prdId}/messages/${messageId}/apply`);
      // Update the message in state
      set((state) => ({
        messages: state.messages.map((m) =>
          m.id === messageId ? { ...m, update_applied: true } : m
        ),
        isLoading: false,
      }));
    } catch (error: unknown) {
      const err = error as { message?: string };
      set({ error: err.message || 'Failed to apply update', isLoading: false });
      throw error;
    }
  },

  clearMessages: () => {
    set({ messages: [], streamingContent: '' });
  },

  clearError: () => {
    set({ error: null });
  },
}));
