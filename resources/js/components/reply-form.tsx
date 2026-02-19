import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { Descendant } from 'slate';
import { Button } from '@/components/ui/button';
import { SlateEditor } from '@/components/slate-editor/editor';
import { DEFAULT_INITIAL_VALUE } from '@/components/slate-editor/types';

interface ReplyFormProps {
    discussionId: number;
    parentId?: number | null;
    onCancel?: () => void;
    placeholder?: string;
}

export function ReplyForm({
    discussionId,
    parentId = null,
    onCancel,
    placeholder = 'Write a reply...',
}: ReplyFormProps) {
    const [editorKey, setEditorKey] = useState(0);

    const form = useForm<{
        body: Descendant[];
        parent_id: number | null;
    }>({
        body: DEFAULT_INITIAL_VALUE,
        parent_id: parentId,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        form.post(`/discussions/${discussionId}/replies`, {
            onSuccess: () => {
                form.reset();
                setEditorKey((k) => k + 1);
                onCancel?.();
            },
        });
    };

    return (
        <form onSubmit={handleSubmit} className="space-y-3">
            <SlateEditor
                key={editorKey}
                initialValue={DEFAULT_INITIAL_VALUE}
                onChange={(value) => form.setData('body', value)}
                placeholder={placeholder}
            />
            {form.errors.body && (
                <p className="text-sm text-destructive">{form.errors.body}</p>
            )}
            <div className="flex items-center gap-2">
                <Button type="submit" size="sm" disabled={form.processing}>
                    {form.processing ? 'Posting...' : 'Post Reply'}
                </Button>
                {onCancel && (
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={onCancel}
                    >
                        Cancel
                    </Button>
                )}
            </div>
        </form>
    );
}
