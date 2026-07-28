const chart_color = [
    "var(--color-chart-1, #2563eb)",
    "var(--color-chart-2, #3b82f6)",
    "var(--color-chart-3, #60a5fa)",
    "var(--color-chart-4, #93c5fd)",
    "var(--color-chart-5, #a855f7)",
    "var(--color-chart-6, #ec4899)",
    "var(--color-chart-7, #f97316)",
    "var(--color-chart-8, #10b981)",
];

export default function Resultados({ enquete, votedOptionId }) {
    if (!enquete) return null;

    // Garante que opções sejam um array
    let options = enquete.options || [];
    if (typeof options === "string") {
        try {
            options = JSON.parse(options);
        } catch {
            options = [];
        }
    }

    if (!Array.isArray(options) || options.length === 0) {
        return <p className="text-xs text-neutral-400">Sem opções cadastradas</p>;
    }

    // Soma total de votos
    const total = options.reduce((acc, opt) => {
        const votes = Number(opt.votes ?? opt.votos ?? 0);
        return acc + votes;
    }, 0);

    return (
        <ul className="space-y-3">
            {options.map((option, index) => {
                const optionId = option.id;
                const optionTexto = option.option_text || option.texto || "Opção";
                const optionVotos = Number(option.votes ?? option.votos ?? 0);

                // Calcula a porcentagem com base no total somado
                const porcentagem = total > 0 ? (optionVotos / total) * 100 : 0;
                const isVoted = Number(votedOptionId) === Number(optionId);
                const barColor = chart_color[index % chart_color.length];

                return (
                    <li key={optionId || index} className="space-y-1.5">
                        <div className="flex items-center justify-between text-xs">
                            <span className={`font-medium ${isVoted ? 'text-blue-600 font-semibold' : 'text-neutral-700'}`}>
                                {optionTexto} {isVoted && '✓'}
                            </span>
                            <div className="flex items-center gap-2 text-neutral-500 tabular-nums">
                                <span>{optionVotos} {optionVotos === 1 ? 'voto' : 'votos'}</span>
                                <span className="font-semibold text-neutral-800">{porcentagem.toFixed(1)}%</span>
                            </div>
                        </div>

                        {/* Barra de Progresso */}
                        <div className="h-2 w-full overflow-hidden rounded-full bg-neutral-100 border border-neutral-200/60">
                            <div
                                className="h-full transition-all duration-500 ease-out rounded-full"
                                style={{ width: `${porcentagem}%`, backgroundColor: isVoted ? '#2563eb' : barColor }}
                            />
                        </div>
                    </li>
                );
            })}
        </ul>
    );
}