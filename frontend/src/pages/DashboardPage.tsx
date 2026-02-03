import { useEffect, useState, useMemo } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuthStore } from '@/stores/authStore';
import { usePrdStore } from '@/stores/prdStore';
import { Button } from '@/components/ui/Button';
import type { Prd, PrdStatus } from '@/types';
import { cn } from '@/lib/cn';

// PRD Card component
const PrdCard: React.FC<{ prd: Prd; onDelete: (id: string) => void }> = ({ prd, onDelete }) => {
  const navigate = useNavigate();
  const [showMenu, setShowMenu] = useState(false);

  const statusColors: Record<PrdStatus, string> = {
    draft: 'bg-slate-100 text-slate-700',
    active: 'bg-green-100 text-green-700',
    archived: 'bg-amber-100 text-amber-700',
  };

  const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString(undefined, {
      month: 'short',
      day: 'numeric',
      year: date.getFullYear() !== new Date().getFullYear() ? 'numeric' : undefined,
    });
  };

  return (
    <div
      className="bg-white rounded-xl border border-slate-200 p-4 hover:border-slate-300 hover:shadow-sm transition-all cursor-pointer relative group"
      onClick={() => navigate(`/prd/${prd.id}`)}
      onKeyDown={(e) => e.key === 'Enter' && navigate(`/prd/${prd.id}`)}
      tabIndex={0}
      role="button"
      aria-label={`Open ${prd.title}`}
    >
      <div className="flex items-start justify-between mb-3">
        <h3 className="font-medium text-slate-900 line-clamp-2">{prd.title}</h3>
        <button
          onClick={(e) => {
            e.stopPropagation();
            setShowMenu(!showMenu);
          }}
          className="p-1 rounded hover:bg-slate-100 opacity-0 group-hover:opacity-100 transition-opacity"
          aria-label="More options"
        >
          <svg className="w-5 h-5 text-slate-400" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z" />
          </svg>
        </button>

        {showMenu && (
          <div className="absolute right-4 top-12 z-10 bg-white border border-slate-200 rounded-lg shadow-lg py-1 min-w-[120px]">
            <button
              onClick={(e) => {
                e.stopPropagation();
                if (confirm('Are you sure you want to delete this PRD?')) {
                  onDelete(prd.id);
                }
                setShowMenu(false);
              }}
              className="w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50"
            >
              Delete
            </button>
          </div>
        )}
      </div>

      <div className="flex items-center justify-between text-sm">
        <span className="text-slate-500">{formatDate(prd.updated_at)}</span>
        <span className={cn('px-2 py-0.5 rounded-full text-xs font-medium', statusColors[prd.status])}>
          {prd.status}
        </span>
      </div>
    </div>
  );
};

// Empty state component
const EmptyState: React.FC<{ onCreateClick: () => void }> = ({ onCreateClick }) => (
  <div className="text-center py-16 animate-fade-in">
    <div className="w-16 h-16 mx-auto mb-4 rounded-full bg-slate-100 flex items-center justify-center">
      <svg className="w-8 h-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
      </svg>
    </div>
    <h3 className="text-lg font-semibold text-slate-900 mb-2">No PRDs yet</h3>
    <p className="text-slate-500 max-w-sm mx-auto mb-6">
      Create your first PRD to get started with AI-powered product requirements documentation.
    </p>
    <Button onClick={onCreateClick}>Create your first PRD</Button>
  </div>
);

// Loading skeleton
const LoadingSkeleton: React.FC = () => (
  <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    {[...Array(6)].map((_, i) => (
      <div key={i} className="bg-white rounded-xl border border-slate-200 p-4 animate-pulse">
        <div className="h-5 bg-slate-200 rounded w-3/4 mb-4"></div>
        <div className="flex justify-between">
          <div className="h-4 bg-slate-200 rounded w-20"></div>
          <div className="h-4 bg-slate-200 rounded w-16"></div>
        </div>
      </div>
    ))}
  </div>
);

export const DashboardPage: React.FC = () => {
  const { user, logout } = useAuthStore();
  const { prds, isLoading, error, fetchPrds, createPrd, deletePrd } = usePrdStore();
  const navigate = useNavigate();
  const [searchQuery, setSearchQuery] = useState('');
  const [statusFilter, setStatusFilter] = useState<PrdStatus | null>(null);

  useEffect(() => {
    fetchPrds();
  }, [fetchPrds]);

  const filteredPrds = useMemo(() => {
    return prds.filter((prd) => {
      const matchesSearch = prd.title.toLowerCase().includes(searchQuery.toLowerCase());
      const matchesStatus = !statusFilter || prd.status === statusFilter;
      return matchesSearch && matchesStatus;
    });
  }, [prds, searchQuery, statusFilter]);

  const handleCreatePrd = async () => {
    try {
      const prd = await createPrd();
      navigate(`/prd/${prd.id}`);
    } catch {
      // Error is handled in store
    }
  };

  const handleDeletePrd = async (id: string) => {
    try {
      await deletePrd(id);
    } catch {
      // Error is handled in store
    }
  };

  return (
    <div className="min-h-screen bg-slate-50">
      {/* Header */}
      <header className="bg-white border-b border-slate-200">
        <div className="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
          <h1 className="text-xl font-bold text-slate-900">PRD Tool</h1>

          <div className="flex items-center gap-4">
            {user && (
              <div className="flex items-center gap-3">
                {user.avatar_url && (
                  <img
                    src={user.avatar_url}
                    alt={user.name}
                    className="w-8 h-8 rounded-full"
                  />
                )}
                <span className="text-sm text-slate-700 hidden sm:inline">{user.name}</span>
              </div>
            )}
            <Button variant="ghost" size="sm" onClick={logout}>
              Sign out
            </Button>
          </div>
        </div>
      </header>

      {/* Main content */}
      <main className="max-w-7xl mx-auto px-4 py-8">
        {/* Title and actions */}
        <div className="flex items-center justify-between mb-6">
          <h2 className="text-2xl font-bold text-slate-900">My PRDs</h2>
          <Button onClick={handleCreatePrd} disabled={isLoading}>
            <svg className="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
            </svg>
            New PRD
          </Button>
        </div>

        {/* Filters */}
        {prds.length > 0 && (
          <div className="flex flex-col sm:flex-row gap-4 mb-6">
            <div className="relative flex-1 max-w-xs">
              <svg
                className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
              <input
                type="text"
                placeholder="Search PRDs..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>

            <div className="flex gap-2">
              {(['all', 'draft', 'active', 'archived'] as const).map((status) => (
                <button
                  key={status}
                  onClick={() => setStatusFilter(status === 'all' ? null : status)}
                  className={cn(
                    'px-3 py-1.5 text-sm rounded-lg transition-colors',
                    (status === 'all' && !statusFilter) || statusFilter === status
                      ? 'bg-slate-900 text-white'
                      : 'bg-white text-slate-600 border border-slate-300 hover:bg-slate-50'
                  )}
                >
                  {status === 'all' ? 'All' : status.charAt(0).toUpperCase() + status.slice(1)}
                </button>
              ))}
            </div>
          </div>
        )}

        {/* Error state */}
        {error && (
          <div className="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
            <p className="font-medium">Error</p>
            <p className="text-sm">{error}</p>
            <button
              onClick={() => fetchPrds()}
              className="mt-2 text-sm font-medium hover:underline"
            >
              Try again
            </button>
          </div>
        )}

        {/* Loading state */}
        {isLoading && prds.length === 0 && <LoadingSkeleton />}

        {/* Empty state */}
        {!isLoading && prds.length === 0 && <EmptyState onCreateClick={handleCreatePrd} />}

        {/* PRD grid */}
        {!isLoading && prds.length > 0 && (
          <>
            {filteredPrds.length > 0 ? (
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                {filteredPrds.map((prd) => (
                  <PrdCard key={prd.id} prd={prd} onDelete={handleDeletePrd} />
                ))}
              </div>
            ) : (
              <div className="text-center py-12">
                <p className="text-slate-500">No PRDs match your search.</p>
                <button
                  onClick={() => {
                    setSearchQuery('');
                    setStatusFilter(null);
                  }}
                  className="mt-2 text-blue-600 hover:underline text-sm"
                >
                  Clear filters
                </button>
              </div>
            )}
          </>
        )}
      </main>
    </div>
  );
};
