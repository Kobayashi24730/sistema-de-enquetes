import { useState } from 'react';
import Card from '@/components/Cards';

const mockPolls = [
    {
        id: "1",
        category: 'Cultura',
        authorName: 'Ana Ribeiro',
        title: 'Café ou chá durante o code review?',
        description: 'Escolha sua bebida favorita.',
        options: [
            { id: "101", text: 'Café', votes: 104 },
            { id: "102", text: 'Chá', votes: 45 },
            { id: "103", text: 'Água', votes: 43 },
        ],
    },
    {
        id: "2",
        category: 'Trabalho',
        authorName: 'Caio Lopes',
        title: 'Modelo de trabalho ideal',
        description: 'Como você prefere trabalhar no dia a dia?',
        options: [
            { id: "201", text: '100% remoto', votes: 115 },
            { id: "202", text: 'Híbrido', votes: 70 },
            { id: "203", text: 'Presencial', votes: 31 },
        ],
    },
];

export default function Home() {
    const [destaque] = useState(mockPolls[0].title);
    const [votos] = useState(mockPolls[0].options[0].votes);

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
                    Em destaque agora: <span className="text-primary font-semibold">{destaque}</span> com <span className="text-primary font-semibold">{votos}</span> votos.
                </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                {mockPolls.map((poll) => (
                    <Card key={poll.id} poll={poll} />
                ))}
            </div>
        </section>
    );
}