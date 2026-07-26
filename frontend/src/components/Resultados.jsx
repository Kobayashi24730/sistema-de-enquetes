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

export default function Resultados({ enquete, vote }) {
    const options = enquete.options || enquete.opcoes || [];
    const total = options.reduce((acc, opt) => {
        const votes = Number(opt.votes ?? opt.votes ?? 0);
        return acc + votes;
    }, 0);

    if (options.length === 0) {
        return <p className="text-xs text-muted-foreground">Sem opções cadastradas</p>
    }

    return (
        <ul>
            {options.map((option, index) => {
                const optionId = option.id;
                const optionTexto = option.text || option.opcao_text || option.titulo;
                const optionVotos = Number(option.votes ?? option.votos ?? 0);
                const porcentagem = total > 0 ? (optionVotos / total) * 100 : 0;

                return (
                    <li key={optionId}>
                        <span className="text-xs text-muted-foreground">{optionTexto}</span>
                        <span className="text-xs text-muted-foreground">{optionVotos} votos</span>
                        <span className="text-xs text-muted-foreground">{porcentagem.toFixed(2)}%</span>
                    </li>
                )
            })}
        </ul>
    );
}