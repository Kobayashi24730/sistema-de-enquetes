import React from 'react';
import { ResponsiveContainer, PieChart, Pie, Cell, Tooltip } from 'recharts';

// Paleta estilo Microsoft Fluent (Azul principal + tons de apoio)
const CHART_COLORS = [
    '#0078D4', // Fluent Blue
    '#2B88D8',
    '#005A9E',
    '#107C41', // Accent Green
    '#5C2D91', // Accent Purple
    '#D13438', // Accent Red
    '#008272', // Teal
    '#6B69D6'
];

export function Chart({ options = [] }) {
    // 1. Mapeia as opções do banco/API para o formato esperado pelo Recharts
    const chartData = options.map((opt) => ({
        name: opt.option_text,
        value: Number(opt.votes) || 0
    }));

    // 2. Calcula o total de votos
    const totalVotes = chartData.reduce((acc, opt) => acc + opt.value, 0);

    // Estilo Fluent UI para o container do Card
    const fluentCard = "w-full bg-white border border-neutral-200/80 rounded-lg p-5 shadow-sm";

    return (
        <div className={fluentCard}>
            <h2 className="text-[11px] font-semibold uppercase tracking-wider text-neutral-500 mb-2">
                Distribuição dos Votos
            </h2>

            <div className="relative flex h-52 w-full items-center justify-center">
                {totalVotes > 0 ? (
                    <>
                        <ResponsiveContainer width="100%" height="100%">
                            <PieChart>
                                <Pie
                                    data={chartData}
                                    cx="50%"
                                    cy="50%"
                                    innerRadius={60}
                                    outerRadius={80}
                                    paddingAngle={3}
                                    dataKey="value"
                                >
                                    {chartData.map((entry, index) => (
                                        <Cell
                                            key={`cell-${index}`}
                                            fill={CHART_COLORS[index % CHART_COLORS.length]}
                                            stroke="#ffffff"
                                            strokeWidth={2}
                                        />
                                    ))}
                                </Pie>
                                <Tooltip
                                    formatter={(value) => [`${value} votos`, 'Quantidade']}
                                    contentStyle={{
                                        backgroundColor: '#ffffff',
                                        borderRadius: '6px',
                                        borderColor: '#e5e5e5',
                                        fontSize: '12px',
                                        boxShadow: '0 2px 8px rgba(0,0,0,0.08)'
                                    }}
                                />
                            </PieChart>
                        </ResponsiveContainer>

                        {/* Número centralizado dentro do Donut */}
                        <div className="pointer-events-none absolute flex flex-col items-center justify-center text-center">
                            <span className="text-2xl font-bold leading-none text-neutral-900">{totalVotes}</span>
                            <span className="mt-1 text-[11px] font-medium text-neutral-500">votos</span>
                        </div>
                    </>
                ) : (
                    <div className="text-center text-xs text-neutral-400">
                        Nenhum voto registrado para exibir o gráfico.
                    </div>
                )}
            </div>
        </div>
    );
}