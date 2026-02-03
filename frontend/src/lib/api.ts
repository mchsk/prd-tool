import axios from 'axios';

// Create axios instance with base configuration
export const api = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  withCredentials: true,
});

// Response interceptor for error handling
api.interceptors.response.use(
  (response) => response,
  (error) => {
    const message = error.response?.data?.message || 'An error occurred';
    const code = error.response?.data?.code || 'UNKNOWN_ERROR';
    
    // Handle common error cases
    if (error.response?.status === 401) {
      // Redirect to login if unauthenticated
      if (window.location.pathname !== '/login') {
        window.location.href = '/login';
      }
    }
    
    return Promise.reject({ message, code, status: error.response?.status });
  }
);

// Health check function
export const checkHealth = async () => {
  const response = await api.get('/health');
  return response.data;
};
