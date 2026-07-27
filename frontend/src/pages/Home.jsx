import { useState } from 'react';
import Card from '@/components/Cards.jsx';
import { Search } from "lucide-react";
import { useEnqueteRealtime } from '@/hooks/useRealTime.ts';
import Filters from '@/components/Filters.jsx';

export default function Home() {
    const { enquetes, loading } = useEnqueteRealtime(8000);
    const [search, setSearch] = useState('');
    const [category, setCategory] = useState('todas');
    const [sort, setSort] = useState("recentes");
    const safeEnquetes = enquetes || []; // Garante fallback seguro para enquetes, evitando erros de null

    const processedEnquetes = safeEnquetes
        .filter((item) => {
            // Filtro por termo de busca no título
            const matchesSearch = item.title?.toLowerCase().includes(search.toLowerCase());
            // Filtro por categoria
            const matchesCategory = category === 'todas' || item.category?.toLowerCase() === category.toLowerCase();
            return matchesSearch && matchesCategory;
        })
        .sort((a, b) => {
            if (sort === 'populares') {
                const votesA = (a.options || a.opcoes || []).reduce((acc, o) => acc + Number(o.votes || o.votos || 0), 0);
                const votesB = (b.options || b.opcoes || []).reduce((acc, o) => acc + Number(o.votes || o.votos || 0), 0);
                return votesB - votesA; // Mais votadas primeiro
            }

            // Ordenação padrão: Recentes
            return new Date(b.created_at).getTime() - new Date(a.created_at).getTime();
        });

    // Cálculo da enquete em destaque no cabeçalho
    const topEnquete = safeEnquetes.reduce((max, item) => {
        const totalVotes = (item.options || []).reduce(
            (acc, opt) => acc + Number(opt.votes || 0), 0
        );
        return totalVotes > (max.totalVotes || 0)
            ? { title: item.title, totalVotes } : max;
    }, { title: 'Nenhuma', totalVotes: 0 });

    return (
        <section className="p-6 max-w-6xl mx-auto space-y-8">
            <div className="space-y-2">
                <h1 className="text-3xl font-bold tracking-tight">
                    Faça a pergunta. Veja a resposta acontecendo ao vivo.
                </h1>
                <p className="text-muted-foreground">
                    Crie enquetes com até 8 opções, vote e acompanhe cada novo voto em tempo real.
                </p>
                {topEnquete.totalVotes > 0 && (
                    <div className="inline-block mt-2 rounded-lg bg-muted px-3 py-1 text-sm font-medium">
                        Em destaque agora: <span className="text-primary font-semibold">{topEnquete.title}</span> com <span className="text-primary font-semibold">{topEnquete.totalVotes}</span> votos.
                    </div>
                )}
            </div>

            <div className="flex flex-wrap items-center gap-4">
                <div className="flex items-center gap-2 border rounded-lg px-3 py-1.5 text-sm w-full sm:w-auto">
                    <Search className="size-4 text-muted-foreground" />
                    <input
                        type="text"
                        placeholder="Procurar enquete..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        className="bg-transparent outline-none placeholder:text-muted-foreground text-sm w-full"
                    />
                </div>
                <Filters onCategoryChange={setCategory} onSortChange={setSort} />
            </div>

            {loading ? (
                <div className="flex h-40 items-center justify-center text-sm text-muted-foreground">
                    Carregando enquetes...
                </div>
            ) : processedEnquetes.length > 0 ? (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {processedEnquetes.map((enquete) => (
                        <Card key={enquete.id} enquete={enquete} />
                    ))}
                </div>
            ) : (
                <div className="p-8 text-center border rounded-lg text-muted-foreground text-sm">
                    Nenhuma enquete encontrada.
                </div>
            )}
        </section>
    );
}