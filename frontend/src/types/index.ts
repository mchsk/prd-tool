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
