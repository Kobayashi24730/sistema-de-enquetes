import React from 'react';

const CHART_COLORS = [
    "var(--color-chart-1)",
    "var(--color-chart-2)",
    "var(--color-chart-3)",
    "var(--color-chart-4)",
    "var(--color-chart-5)",
    "var(--color-chart-6)",
    "var(--color-chart-7)",
    "var(--color-chart-8)",
];

// Função auxiliar para calcular o total de votos da enquete
function getTotalVotes(options) {
    if (!options || !Array.isArray(options)) return 0;
    return options.reduce((acc, curr) => acc + (curr.votes || 0), 0);
}

// 1. Props desestruturadas corretamente com { poll, votedOptionId }
export default function Resultados({ poll, votedOptionId }) {
    // Garantia para não quebrar enquanto os dados não chegam
    if (!poll || !poll.options) return null;

    // 2. Uso da função de soma declarada
    const total = getTotalVotes(poll.options);

    return (
        <ul className="space-y-3">
            {poll.options.map((option, index) => {
                const votes = option.votes || 0;
                const percent = total ? (votes / total) * 100 : 0;
                const isChoice = String(option.id) === String(votedOptionId);

                return (
                    <li key={option.id}>
                        <div className="mb-1.5 flex items-baseline justify-between gap-3 text-sm">
              <span className={isChoice ? "font-semibold text-foreground" : "text-foreground/90"}>
                {option.text}
                  {isChoice && <span className="ml-2 text-xs text-primary font-medium">(seu voto)</span>}
              </span>
                            <span className="shrink-0 tabular-nums text-muted-foreground text-xs">
                {percent.toFixed(1)}% · {votes} {votes === 1 ? 'voto' : 'votos'}
              </span>
                        </div>

                        <div className="h-2.5 w-full overflow-hidden rounded-full bg-muted">
                            <div
                                className="h-full rounded-full transition-[width] duration-700 ease-out"
                                style={{
                                    width: `${percent}%`,
                                    backgroundColor: CHART_COLORS[index % CHART_COLORS.length],
                                }}
                            />
                        </div>
                    </li>
                );
            })}
        </ul>
    );
}

export { CHART_COLORS };