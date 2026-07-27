import { useState } from 'react';
import { ChevronDown, Sparkles, Clock, Flame } from 'lucide-react';

export default function Filters({ onCategoryChange, onSortChange }) {
    const [category, setCategory] = useState('todas');
    const [sort, setSort] = useState('recentes');
    const categories = [
        { id: 'todas', label: 'Todas as categorias' },
        { id: 'tecnologia', label: 'Tecnologia' },
        { id: 'entretenimento', label: 'Entretenimento' },
        { id: 'esportes', label: 'Esportes' },
        { id: 'geral', label: 'Geral' }
    ];

    // Atualiza o estado de sort e chama a função onSortChange
    const handleSort = (type) => {
        setSort(type);
        if (onSortChange) onSortChange(type);
    };

    // Atualiza o estado de category e chama a função onCategoryChange
    const handleCategory = (e) => {
        const val = e.target.value;
        setCategory(val);
        if (onCategoryChange) onCategoryChange(val);
    };

    return (
        <div className="flex flex-wrap items-center justify-between gap-3 w-full">
            <div className="relative inline-block text-left">
                <select
                    value={category}
                    onChange={handleCategory}
                    className="appearance-none bg-background text-foreground text-sm font-medium border border-border/80 hover:border-border hover:bg-accent/50 rounded-lg px-3.5 py-1.5 pr-8 transition-colors cursor-pointer outline-none focus:ring-2 focus:ring-ring focus:ring-offset-1"
                >
                    {categories.map((cat) => (
                        <option key={cat.id} value={cat.id} className="bg-background text-foreground">
                            {cat.label}
                        </option>
                    ))}
                </select>
                <ChevronDown className="size-4 text-muted-foreground absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none"/>
            </div>
            <div className="flex items-center gap-1 bg-muted/60 p-1 rounded-lg border border-border/40 text-sm">
                <button
                    onClick={() => handleSort('recentes')}
                    className={`flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-medium transition-all ${
                        sort === 'recentes'
                            ? 'bg-background text-foreground shadow-sm'
                            : 'text-muted-foreground hover:text-foreground hover:bg-background/40'
                    }`}
                >
                    <Clock className="size-3.5" />
                    Recentes
                </button>
                <button
                    onClick={() => handleSort('populares')}
                    className={`flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-medium transition-all ${
                        sort === 'populares'
                            ? 'bg-background text-foreground shadow-sm'
                            : 'text-muted-foreground hover:text-foreground hover:bg-background/40'
                    }`}
                >
                    <Flame className="size-3.5" />
                    Popular
                </button>
            </div>
        </div>
    );
}