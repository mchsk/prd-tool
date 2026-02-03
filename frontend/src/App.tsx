import { useEffect, useState } from 'react';
import { checkHealth } from '@/lib/api';

interface HealthStatus {
  status: string;
  timestamp: string;
  version: string;
}

function App() {
  const [health, setHealth] = useState<HealthStatus | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchHealth = async () => {
      try {
        const data = await checkHealth();
        setHealth(data);
        setError(null);
      } catch (err) {
        setError('Could not connect to backend API');
        console.error('Health check failed:', err);
      } finally {
        setLoading(false);
      }
    };

    fetchHealth();
  }, []);

  return (
    <div className="min-h-screen flex items-center justify-center bg-slate-50">
      <div className="w-full max-w-md p-8 bg-white rounded-xl shadow-lg animate-fade-in">
        {/* Logo */}
        <div className="text-center mb-8">
          <h1 className="text-3xl font-bold text-slate-900">PRD Tool</h1>
          <p className="text-slate-600 mt-2">AI-powered PRD creation</p>
        </div>

        {/* Status */}
        <div className="space-y-4">
          <div className="p-4 rounded-lg bg-slate-50 border border-slate-200">
            <h2 className="text-sm font-medium text-slate-700 mb-3">System Status</h2>
            
            {loading && (
              <div className="flex items-center gap-2">
                <div className="w-4 h-4 border-2 border-primary-500 border-t-transparent rounded-full animate-spin" />
                <span className="text-sm text-slate-600">Checking backend...</span>
              </div>
            )}

            {!loading && health && (
              <div className="space-y-2">
                <div className="flex items-center gap-2">
                  <div className="w-3 h-3 rounded-full bg-green-500" />
                  <span className="text-sm text-green-700 font-medium">Backend Connected</span>
                </div>
                <div className="text-xs text-slate-500">
                  <p>Status: {health.status}</p>
                  <p>Version: {health.version}</p>
                  <p>Last check: {new Date(health.timestamp).toLocaleTimeString()}</p>
                </div>
              </div>
            )}

            {!loading && error && (
              <div className="space-y-2">
                <div className="flex items-center gap-2">
                  <div className="w-3 h-3 rounded-full bg-amber-500" />
                  <span className="text-sm text-amber-700 font-medium">Backend Offline</span>
                </div>
                <p className="text-xs text-slate-500">{error}</p>
                <p className="text-xs text-slate-400">Start with: docker-compose up</p>
              </div>
            )}
          </div>

          <div className="p-4 rounded-lg bg-slate-50 border border-slate-200">
            <h2 className="text-sm font-medium text-slate-700 mb-2">Frontend</h2>
            <div className="flex items-center gap-2">
              <div className="w-3 h-3 rounded-full bg-green-500" />
              <span className="text-sm text-green-700 font-medium">Running</span>
            </div>
            <p className="text-xs text-slate-500 mt-1">React + Vite + Tailwind</p>
          </div>
        </div>

        <p className="text-center text-xs text-slate-400 mt-8">
          Phase 0: Project Scaffolding Complete
        </p>
      </div>
    </div>
  );
}

export default App;
