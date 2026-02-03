import { useEffect, useRef, useState } from 'react';
import { useChatStore } from '@/stores/chatStore';
import { Button } from '@/components/ui/Button';
import { Spinner } from '@/components/ui/Spinner';
import { cn } from '@/lib/cn';
import type { Message } from '@/types';

interface ChatPanelProps {
  prdId: string;
  onUpdateApplied?: () => void;
}

// Message bubble component
const MessageBubble: React.FC<{
  message: Message;
  prdId: string;
  onApplyUpdate: () => void;
}> = ({ message, prdId, onApplyUpdate }) => {
  const { applyUpdate, isLoading } = useChatStore();
  const isUser = message.role === 'user';

  const handleApply = async () => {
    try {
      await applyUpdate(prdId, message.id);
      onApplyUpdate();
    } catch {
      // Error handled in store
    }
  };

  // Remove PRD update tags from display
  const displayContent = message.content.replace(/<prd_update>[\s\S]*?<\/prd_update>/g, '').trim();

  return (
    <div className={cn('flex gap-3', isUser ? 'justify-end' : 'justify-start')}>
      {!isUser && (
        <div className="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
          <svg className="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
          </svg>
        </div>
      )}

      <div className={cn('max-w-[80%] space-y-2', isUser ? 'items-end' : 'items-start')}>
        <div
          className={cn(
            'rounded-xl px-4 py-2.5 text-sm',
            isUser
              ? 'bg-blue-600 text-white'
              : 'bg-slate-100 text-slate-900'
          )}
        >
          <p className="whitespace-pre-wrap">{displayContent}</p>
        </div>

        {/* PRD Update suggestion */}
        {message.prd_update_suggestion && !message.update_applied && (
          <div className="bg-amber-50 border border-amber-200 rounded-lg p-3 text-sm">
            <div className="flex items-center gap-2 text-amber-800 font-medium mb-2">
              <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
              PRD Update Available
            </div>
            <Button
              size="sm"
              onClick={handleApply}
              disabled={isLoading}
              className="w-full"
            >
              Apply to PRD
            </Button>
          </div>
        )}

        {message.update_applied && (
          <div className="flex items-center gap-1 text-xs text-green-600">
            <svg className="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
            </svg>
            Update applied
          </div>
        )}
      </div>
    </div>
  );
};

// Streaming message component
const StreamingMessage: React.FC<{ content: string }> = ({ content }) => (
  <div className="flex gap-3 justify-start">
    <div className="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
      <svg className="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
      </svg>
    </div>
    <div className="max-w-[80%]">
      <div className="bg-slate-100 text-slate-900 rounded-xl px-4 py-2.5 text-sm">
        <p className="whitespace-pre-wrap">{content || '...'}</p>
        <span className="inline-block w-2 h-4 bg-slate-400 animate-pulse ml-0.5" />
      </div>
    </div>
  </div>
);

export const ChatPanel: React.FC<ChatPanelProps> = ({ prdId, onUpdateApplied }) => {
  const { messages, isLoading, isStreaming, streamingContent, error, fetchMessages, sendMessage } =
    useChatStore();
  const [input, setInput] = useState('');
  const messagesEndRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    fetchMessages(prdId);
    return () => useChatStore.getState().clearMessages();
  }, [prdId, fetchMessages]);

  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages, streamingContent]);

  const handleSend = async () => {
    if (!input.trim() || isLoading) return;
    const message = input.trim();
    setInput('');
    await sendMessage(prdId, message);
  };

  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      handleSend();
    }
  };

  return (
    <div className="flex flex-col h-full">
      {/* Header */}
      <div className="p-4 border-b border-slate-200">
        <h3 className="font-semibold text-slate-900">AI Assistant</h3>
        <p className="text-xs text-slate-500 mt-1">Chat to refine your PRD</p>
      </div>

      {/* Messages */}
      <div className="flex-1 overflow-y-auto p-4 space-y-4">
        {messages.length === 0 && !isLoading && !isStreaming && (
          <div className="text-center text-sm text-slate-500 py-8">
            <svg className="w-10 h-10 mx-auto mb-3 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            <p>Start a conversation to refine your PRD</p>
          </div>
        )}

        {messages.map((message) => (
          <MessageBubble
            key={message.id}
            message={message}
            prdId={prdId}
            onApplyUpdate={onUpdateApplied || (() => {})}
          />
        ))}

        {isStreaming && <StreamingMessage content={streamingContent} />}

        <div ref={messagesEndRef} />
      </div>

      {/* Error */}
      {error && (
        <div className="mx-4 mb-2 p-2 bg-red-50 border border-red-200 rounded text-xs text-red-700">
          {error}
        </div>
      )}

      {/* Input */}
      <div className="p-4 border-t border-slate-200">
        <div className="flex gap-2">
          <textarea
            value={input}
            onChange={(e) => setInput(e.target.value)}
            onKeyDown={handleKeyDown}
            placeholder="Ask about your PRD..."
            rows={2}
            className="flex-1 resize-none rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            disabled={isLoading}
          />
          <Button onClick={handleSend} disabled={!input.trim() || isLoading} className="self-end">
            {isLoading ? <Spinner size="sm" /> : (
              <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M14 5l7 7m0 0l-7 7m7-7H3" />
              </svg>
            )}
          </Button>
        </div>
        <p className="text-xs text-slate-400 mt-2">
          Press Enter to send, Shift+Enter for new line
        </p>
      </div>
    </div>
  );
};
