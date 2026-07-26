import {useEffect, useState} from 'react';
import Card from '@/components/Cards';
import {Search} from "lucide-react";
import api from '@/services/api';

export default function Home() {
    const [enquetes, setEnquetes] = useState([]);
    const [loading, setLoading] = useState(true);
    console.log(enquetes);

    const carregarEnquetes = async () => {
        try {
            const response = await api.get('/enquetes');
            setEnquetes(response.data);
        } catch (error) {
            console.error('Erro ao carregar enquetes:', error);
        } finally {
            setLoading(false);
        }
    }

    useEffect(() => {
        carregarEnquetes();
    }, []);

    return (
        <section className="p-6 max-w-6xl mx-auto space-y-8">
            <div className="space-y-2">
                <h1 className="text-3xl font-bold tracking-tight">
                    Faça a pergunta. Veja a resposta acontecendo ao vivo.
                </h1>
                <p className="text-muted-foreground">
                    Crie enquetes com até 8 opções, vote e acompanhe cada novo voto em tempo real.
                </p>
                <div className="inline-block mt-2 rounded-lg bg-muted px-3 py-1 text-sm font-medium">
                    Em destaque agora: <span className="text-primary font-semibold">ola</span> com <span className="text-primary font-semibold">1213</span> votos.
                </div>
            </div>

            <div className="flex flex-wrap items-center gap-4">
                <div className="flex items-center gap-2 border rounded-lg px-3 py-1.5 text-sm">
                    <Search className="size-4 text-muted-foreground" />
                    <span className="text-muted-foreground">Procurar enquete</span>
                </div>
                <button className="text-sm text-muted-foreground rounded-lg px-3 py-1.5 border border-slate-800 hover:bg-slate-800/10 transition-colors">
                    todas as categorias
                </button>
                <div className="flex gap-2 text-sm">
                    <button className="px-3 py-1.5 rounded-lg bg-muted font-medium">
                        recentes
                    </button>
                    <button className="px-3 py-1.5 rounded-lg text-muted-foreground hover:bg-muted/50">
                        populares
                    </button>
                </div>
            </div>
            {loading ? (
                <p className="text-muted-foreground">Carregando enquetes...</p>
            ) : enquetes && enquetes.length > 0 ? (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {enquetes.map((i) => (
                        <Card key={i.id} enquete={i} />
                    ))}
                </div>
            ) : (
                <p>Nenhum enquete encontrado</p>
            )}
        </section>
    );
}