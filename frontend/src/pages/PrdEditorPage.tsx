import { useEffect, useState, useCallback } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useAuthStore } from '@/stores/authStore';
import { usePrdStore } from '@/stores/prdStore';
import { Button } from '@/components/ui/Button';
import { Spinner } from '@/components/ui/Spinner';
import { api } from '@/lib/api';
import { cn } from '@/lib/cn';

// Simple debounce hook
const useDebounce = <T,>(value: T, delay: number): T => {
  const [debouncedValue, setDebouncedValue] = useState(value);
  useEffect(() => {
    const timer = setTimeout(() => setDebouncedValue(value), delay);
    return () => clearTimeout(timer);
  }, [value, delay]);
  return debouncedValue;
};

// Save status component
const SaveStatus: React.FC<{ status: 'idle' | 'saving' | 'saved' | 'error' }> = ({ status }) => {
  const statusConfig = {
    idle: { text: '', color: '' },
    saving: { text: 'Saving...', color: 'text-slate-500' },
    saved: { text: 'Saved', color: 'text-green-600' },
    error: { text: 'Save failed', color: 'text-red-600' },
  };

  if (status === 'idle') return null;

  return (
    <span className={cn('text-sm flex items-center gap-1', statusConfig[status].color)}>
      {status === 'saving' && <Spinner size="sm" />}
      {statusConfig[status].text}
    </span>
  );
};

export const PrdEditorPage: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const { user } = useAuthStore();
  const { getPrd, updatePrd } = usePrdStore();

  const [content, setContent] = useState('');
  const [title, setTitle] = useState('');
  const [isLoading, setIsLoading] = useState(true);
  const [saveStatus, setSaveStatus] = useState<'idle' | 'saving' | 'saved' | 'error'>('idle');
  const [isEditingTitle, setIsEditingTitle] = useState(false);

  const debouncedContent = useDebounce(content, 1000);

  // Load PRD
  useEffect(() => {
    const loadPrd = async () => {
      if (!id) return;
      setIsLoading(true);
      try {
        const prd = await getPrd(id);
        setTitle(prd.title);
        // Load content
        const { data } = await api.get(`/prds/${id}/content`);
        setContent(data.content);
      } catch {
        navigate('/');
      } finally {
        setIsLoading(false);
      }
    };
    loadPrd();
  }, [id, getPrd, navigate]);

  // Auto-save content
  useEffect(() => {
    const saveContent = async () => {
      if (!id || isLoading) return;
      setSaveStatus('saving');
      try {
        await api.put(`/prds/${id}/content`, { content: debouncedContent });
        setSaveStatus('saved');
        setTimeout(() => setSaveStatus('idle'), 2000);
      } catch {
        setSaveStatus('error');
      }
    };

    if (debouncedContent !== '') {
      saveContent();
    }
  }, [debouncedContent, id, isLoading]);

  // Save title
  const handleTitleSave = useCallback(async () => {
    if (!id || !title.trim()) return;
    setIsEditingTitle(false);
    try {
      await updatePrd(id, { title: title.trim() });
    } catch {
      // Error handled in store
    }
  }, [id, title, updatePrd]);

  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-slate-50">
        <div className="text-center">
          <Spinner size="lg" className="mx-auto mb-4" />
          <p className="text-sm text-slate-600">Loading PRD...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-slate-50 flex flex-col">
      {/* Header */}
      <header className="bg-white border-b border-slate-200 sticky top-0 z-10">
        <div className="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
          <div className="flex items-center gap-4">
            <Button variant="ghost" size="sm" onClick={() => navigate('/')}>
              <svg className="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
              </svg>
              Back
            </Button>

            {isEditingTitle ? (
              <input
                type="text"
                value={title}
                onChange={(e) => setTitle(e.target.value)}
                onBlur={handleTitleSave}
                onKeyDown={(e) => e.key === 'Enter' && handleTitleSave()}
                className="text-lg font-semibold border-b-2 border-blue-500 focus:outline-none bg-transparent"
                autoFocus
              />
            ) : (
              <button
                onClick={() => setIsEditingTitle(true)}
                className="text-lg font-semibold text-slate-900 hover:text-blue-600 transition-colors"
              >
                {title || 'Untitled PRD'}
              </button>
            )}
          </div>

          <div className="flex items-center gap-4">
            <SaveStatus status={saveStatus} />
            {user?.avatar_url && (
              <img
                src={user.avatar_url}
                alt={user.name}
                className="w-8 h-8 rounded-full"
              />
            )}
          </div>
        </div>
      </header>

      {/* Editor area */}
      <div className="flex-1 flex">
        {/* Left panel - Editor */}
        <div className="flex-1 p-6">
          <div className="max-w-4xl mx-auto bg-white rounded-xl border border-slate-200 shadow-sm h-full">
            <textarea
              value={content}
              onChange={(e) => setContent(e.target.value)}
              placeholder="Start writing your PRD in Markdown..."
              className="w-full h-full min-h-[600px] p-6 resize-none focus:outline-none text-slate-800 font-mono text-sm leading-relaxed"
            />
          </div>
        </div>

        {/* Right panel - Chat (placeholder for Phase 3) */}
        <div className="w-80 border-l border-slate-200 bg-white hidden lg:block">
          <div className="p-4 border-b border-slate-200">
            <h3 className="font-semibold text-slate-900">AI Assistant</h3>
            <p className="text-sm text-slate-500 mt-1">Coming in Phase 3</p>
          </div>
          <div className="p-4 text-center text-slate-400 text-sm">
            <svg className="w-12 h-12 mx-auto mb-3 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            Chat with AI to refine your PRD
          </div>
        </div>
      </div>
    </div>
  );
};
