// User types
export interface User {
  id: string;
  name: string;
  email: string;
  avatar_url: string | null;
  preferred_language: string;
  tier: 'free' | 'pro' | 'team' | 'enterprise';
  last_prd_id: string | null;
}

// PRD types
export type PrdStatus = 'draft' | 'active' | 'archived';

export interface Prd {
  id: string;
  title: string;
  status: PrdStatus;
  estimated_tokens: number;
  created_from_template_id: string | null;
  created_at: string;
  updated_at: string;
}

// Message types
export interface Message {
  id: string;
  role: 'user' | 'assistant';
  content: string;
  prd_update_suggestion: string | null;
  update_applied: boolean;
  token_count: number;
  created_at: string;
}

// API Error type
export interface ApiError {
  message: string;
  code: string;
  details?: Record<string, string[]>;
  retry_after?: number;
  status?: number;
}

// Auth types
export interface AuthState {
  user: User | null;
  isLoading: boolean;
  isAuthenticated: boolean;
  error: string | null;
}
