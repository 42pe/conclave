import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import type { Descendant } from 'slate';
import { SlateEditor, EMPTY_DOCUMENT } from '@/components/slate-editor';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';

type SlateNode = Record<string, string | boolean | number | null | SlateNode[]>;

interface ReplyFormProps {
    discussionId: number;
    parentId?: number | null;
    onCancel?: () => void;
    autoFocus?: boolean;
}

export default function ReplyForm({
    discussionId,
    parentId = null,
    onCancel,
    autoFocus = false,
}: ReplyFormProps) {
    const [editorKey, setEditorKey] = useState(0);

    const { data, setData, post, processing, errors, reset } = useForm({
        discussion_id: discussionId,
        parent_id: parentId,
        body: EMPTY_DOCUMENT as unknown as SlateNode[],
    });

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        post('/replies', {
            preserveScroll: true,
            onSuccess: () => {
                reset('body');
                setEditorKey((k) => k + 1);
                onCancel?.();
            },
        });
    }

    return (
        <form onSubmit={handleSubmit} className="space-y-3">
            <SlateEditor
                key={editorKey}
                value={data.body as unknown as Descendant[]}
                onChange={(value) =>
                    setData('body', value as unknown as SlateNode[])
                }
                placeholder="Write a reply..."
            />
            <InputError message={errors.body} />

            <div className="flex items-center gap-2">
                <Button size="sm" disabled={processing}>
                    {parentId ? 'Reply' : 'Post Reply'}
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
