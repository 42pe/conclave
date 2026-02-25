import { useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { iconMap } from '@/lib/lucide-icons';
import { cn } from '@/lib/utils';

export function IconPicker({
    value,
    onChange,
}: {
    value: string;
    onChange: (value: string) => void;
}) {
    const [open, setOpen] = useState(false);
    const [search, setSearch] = useState('');

    const iconEntries = useMemo(() => Object.entries(iconMap), []);

    const filteredIcons = useMemo(() => {
        if (!search) {
            return iconEntries;
        }

        const term = search.toLowerCase();
        return iconEntries.filter(([name]) =>
            name.toLowerCase().includes(term),
        );
    }, [iconEntries, search]);

    const SelectedIcon = value ? iconMap[value] : null;

    const handleSelect = (name: string) => {
        onChange(name);
        setOpen(false);
        setSearch('');
    };

    const handleClear = () => {
        onChange('');
    };

    return (
        <div className="flex items-center gap-2">
            <Dialog open={open} onOpenChange={setOpen}>
                <DialogTrigger asChild>
                    <Button
                        type="button"
                        variant="outline"
                        className="w-full justify-start gap-2"
                    >
                        {SelectedIcon ? (
                            <>
                                <SelectedIcon className="size-4" />
                                <span>{value}</span>
                            </>
                        ) : (
                            <span className="text-muted-foreground">
                                Select an icon...
                            </span>
                        )}
                    </Button>
                </DialogTrigger>
                <DialogContent className="max-h-[80vh] sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Select Icon</DialogTitle>
                    </DialogHeader>
                    <Input
                        placeholder="Search icons..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                    />
                    <div className="grid max-h-[50vh] grid-cols-6 gap-2 overflow-y-auto">
                        {filteredIcons.map(([name, Icon]) => (
                            <button
                                key={name}
                                type="button"
                                className={cn(
                                    'flex flex-col items-center gap-1 rounded-md p-2 text-xs hover:bg-accent',
                                    value === name &&
                                        'bg-accent ring-ring ring-2',
                                )}
                                onClick={() => handleSelect(name)}
                                title={name}
                            >
                                <Icon className="size-5" />
                                <span className="max-w-full truncate">
                                    {name}
                                </span>
                            </button>
                        ))}
                        {filteredIcons.length === 0 && (
                            <p className="col-span-6 py-8 text-center text-sm text-muted-foreground">
                                No icons found.
                            </p>
                        )}
                    </div>
                </DialogContent>
            </Dialog>
            {value && (
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    onClick={handleClear}
                >
                    Clear
                </Button>
            )}
        </div>
    );
}
