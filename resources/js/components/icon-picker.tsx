import {
    Award,
    BarChart3,
    BookOpen,
    Briefcase,
    Camera,
    Car,
    Code,
    Coffee,
    Cpu,
    Dog,
    DollarSign,
    Dumbbell,
    Film,
    Flag,
    Flame,
    Gamepad2,
    Globe,
    GraduationCap,
    Heart,
    HelpCircle,
    Home,
    Image,
    Laptop,
    Leaf,
    Lightbulb,
    type LucideIcon,
    Megaphone,
    MessageCircle,
    Mic,
    Mountain,
    Music,
    Newspaper,
    Palette,
    Plane,
    Rocket,
    Scale,
    Search,
    Shield,
    ShoppingBag,
    Smile,
    Sparkles,
    Star,
    Stethoscope,
    TreePine,
    Trophy,
    Tv,
    Users,
    Utensils,
    Wrench,
    X,
    Zap,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';

const ICONS: Record<string, LucideIcon> = {
    'message-circle': MessageCircle,
    users: Users,
    globe: Globe,
    code: Code,
    'book-open': BookOpen,
    heart: Heart,
    star: Star,
    music: Music,
    film: Film,
    camera: Camera,
    image: Image,
    palette: Palette,
    'gamepad-2': Gamepad2,
    trophy: Trophy,
    briefcase: Briefcase,
    'graduation-cap': GraduationCap,
    lightbulb: Lightbulb,
    wrench: Wrench,
    shield: Shield,
    car: Car,
    plane: Plane,
    home: Home,
    mountain: Mountain,
    'tree-pine': TreePine,
    leaf: Leaf,
    coffee: Coffee,
    utensils: Utensils,
    dog: Dog,
    smile: Smile,
    flame: Flame,
    zap: Zap,
    rocket: Rocket,
    newspaper: Newspaper,
    megaphone: Megaphone,
    mic: Mic,
    tv: Tv,
    laptop: Laptop,
    cpu: Cpu,
    'dollar-sign': DollarSign,
    'bar-chart-3': BarChart3,
    scale: Scale,
    flag: Flag,
    award: Award,
    dumbbell: Dumbbell,
    stethoscope: Stethoscope,
    'shopping-bag': ShoppingBag,
    sparkles: Sparkles,
    'help-circle': HelpCircle,
};

export function getIconComponent(name: string): LucideIcon | null {
    return ICONS[name] ?? null;
}

export default function IconPicker({
    value,
    onChange,
}: {
    value: string;
    onChange: (value: string) => void;
}) {
    const [open, setOpen] = useState(false);
    const [search, setSearch] = useState('');

    const filtered = useMemo(() => {
        if (!search) return Object.entries(ICONS);
        const q = search.toLowerCase();
        return Object.entries(ICONS).filter(([name]) => name.includes(q));
    }, [search]);

    const SelectedIcon = value ? ICONS[value] : null;

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    variant="outline"
                    className="h-9 w-full justify-start gap-2 font-normal"
                    type="button"
                >
                    {SelectedIcon ? (
                        <>
                            <SelectedIcon className="h-4 w-4 shrink-0" />
                            <span className="truncate">{value}</span>
                        </>
                    ) : (
                        <span className="text-muted-foreground">
                            Pick an icon...
                        </span>
                    )}
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-72 p-3" align="start">
                <div className="space-y-3">
                    <div className="relative">
                        <Search className="absolute top-1/2 left-2.5 h-3.5 w-3.5 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Search icons..."
                            className="h-8 pl-8 text-sm"
                        />
                    </div>

                    <div className="grid max-h-52 grid-cols-6 gap-1 overflow-y-auto">
                        {filtered.map(([name, Icon]) => (
                            <button
                                key={name}
                                type="button"
                                title={name}
                                onClick={() => {
                                    onChange(name);
                                    setOpen(false);
                                    setSearch('');
                                }}
                                className={cn(
                                    'flex h-8 w-8 items-center justify-center rounded-md transition-colors hover:bg-accent',
                                    value === name &&
                                        'bg-primary text-primary-foreground hover:bg-primary/90',
                                )}
                            >
                                <Icon className="h-4 w-4" />
                            </button>
                        ))}
                        {filtered.length === 0 && (
                            <p className="col-span-6 py-3 text-center text-xs text-muted-foreground">
                                No icons found
                            </p>
                        )}
                    </div>

                    {value && (
                        <button
                            type="button"
                            onClick={() => {
                                onChange('');
                                setOpen(false);
                                setSearch('');
                            }}
                            className="flex w-full items-center justify-center gap-1 rounded-md py-1 text-xs text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                        >
                            <X className="h-3 w-3" />
                            Clear selection
                        </button>
                    )}
                </div>
            </PopoverContent>
        </Popover>
    );
}
