import { Cell, Pie, PieChart, ResponsiveContainer, Tooltip } from "recharts";
import { CHART_COLORS } from "./PollResults";
import { totalVotes, type Poll } from "@/lib/types";

/** Gráfico de rosca com a distribuição de votos em tempo real. */
export function PollChart({ poll }: { poll: Poll }) {
    const total = totalVotes(poll);
    const data = poll.options.map((option) => ({
        name: option.text,
        value: option.votes,
    }));

    if (!total) {
        return (
            <div className="grid h-56 place-items-center rounded-lg border border-dashed border-border text-sm text-muted-foreground">
                Ainda sem votos
            </div>
        );
    }

    return (
        <div className="relative h-56">
            <ResponsiveContainer width="100%" height="100%">
                <PieChart>
                    <Pie
                        data={data}
                        dataKey="value"
                        nameKey="name"
                        innerRadius="60%"
                        outerRadius="88%"
                        paddingAngle={2}
                        stroke="none"
                        isAnimationActive
                    >
                        {data.map((entry, index) => (
                            <Cell key={entry.name} fill={CHART_COLORS[index % CHART_COLORS.length]} />
                        ))}
                    </Pie>
                    <Tooltip
                        contentStyle={{
                            background: "var(--color-popover)",
                            border: "1px solid var(--color-border)",
                            borderRadius: "0.5rem",
                            color: "var(--color-popover-foreground)",
                            fontSize: "0.8rem",
                        }}
                    />
                </PieChart>
            </ResponsiveContainer>
            <div className="pointer-events-none absolute inset-0 grid place-content-center text-center">
                <span className="font-display text-2xl font-bold tabular-nums">{total}</span>
                <span className="text-xs text-muted-foreground">votos</span>
            </div>
        </div>
    );
}
