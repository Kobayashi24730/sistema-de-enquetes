import { Link } from "react-router-dom";
import { Clock, User2, BarChart3, ChevronRight, CheckCircle2 } from "lucide-react";
import Resultados from "@/components/Resultados.jsx";
import { getVotedEnquete } from "@/hooks/useRealTime";

export default function Card({ enquete }) {
    if (!enquete) {
        return (
            <div className="p-6 text-center rounded-lg border border-dashed border-neutral-200 bg-neutral-50 text-neutral-500 text-sm">
                <p>Nenhuma enquete encontrada.</p>
            </div>
        );
    }

    // Garante que opções sejam sempre um array
    let options = enquete.options || [];
    if (typeof options === "string") {
        try {
            options = JSON.parse(options);
        } catch {
            options = [];
        }
    }

    // Injeta options parseadas de volta no objeto enquete
    const safeEnquete = { ...enquete, options };
    // Verifica se já votou
    const votedEnquete = getVotedEnquete();
    const hasVoted = safeEnquete.hasVoted ?? votedEnquete.includes(safeEnquete.id);
    const isExpired = safeEnquete.expires_at ? new Date(safeEnquete.expires_at) < new Date() : false;
    // Calcula total de votos
    const totalVotes = safeEnquete.total_votes ?? options.reduce((acc, opt) => acc + Number(opt.votes ?? opt.votos ?? 0), 0);

    return (
        <article className="group relative">
            <Link
                to={`/enquete/${safeEnquete.id}`}
                className="group flex flex-col justify-between h-full p-5 rounded-xl border border-neutral-200/80 bg-white shadow-sm transition-all duration-200 hover:border-neutral-300 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900"
            >
                <div className="space-y-3">
                    {/* Categoria / Status / Encerrada */}
                    <div className="flex flex-wrap items-center justify-between gap-2 text-xs">
                        <div className="flex items-center gap-1.5">
                            {safeEnquete.category && (
                                <span className="rounded-md bg-neutral-100 px-2.5 py-0.5 text-xs font-medium text-neutral-600 border border-neutral-200/60">
                                    {safeEnquete.category}
                                </span>
                            )}

                            {hasVoted && (
                                <span className="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 border border-emerald-200/60">
                                    <CheckCircle2 className="size-3 text-emerald-600" />
                                    Votado
                                </span>
                            )}

                            {isExpired && (
                                <span className="rounded-md bg-neutral-100 px-2.5 py-0.5 text-neutral-500 font-medium">
                                    Encerrada
                                </span>
                            )}
                        </div>

                        {/* Autor */}
                        <span className="inline-flex items-center gap-1.5 text-neutral-500 font-medium truncate max-w-[140px]">
                            <User2 className="size-3.5 text-neutral-400 shrink-0" />
                            <span className="truncate">{safeEnquete.criador || safeEnquete.author || "Anônimo"}</span>
                        </span>
                    </div>

                    {/* Título e Descrição */}
                    <div className="space-y-1">
                        <h3 className="text-base font-semibold tracking-tight text-neutral-900 group-hover:text-blue-600 transition-colors line-clamp-2">
                            {safeEnquete.title}
                        </h3>
                        {safeEnquete.description && (
                            <p className="line-clamp-2 text-xs text-neutral-500 font-normal leading-relaxed">{safeEnquete.description}</p>
                        )}
                    </div>

                    {/* Prévia de Resultados */}
                    <div className="pt-2">
                        <Resultados enquete={safeEnquete} />
                    </div>
                </div>

                {/* Rodapé com contagem de votos e data */}
                <div className="mt-5 pt-3 border-t border-neutral-100 flex items-center justify-between text-xs text-neutral-500">
                    <div className="flex items-center gap-3">
                        <span className="inline-flex items-center gap-1 font-medium text-neutral-700">
                            <BarChart3 className="size-3.5 text-neutral-400" />
                            <span className="tabular-nums">{totalVotes}</span> {totalVotes === 1 ? 'voto' : 'votos'}
                        </span>

                        {safeEnquete.created_at && (
                            <span className="inline-flex items-center gap-1 text-neutral-400">
                                <Clock className="size-3 text-neutral-400" />
                                {new Date(safeEnquete.created_at).toLocaleDateString('pt-BR', { day: '2-digit', month: 'short' })}
                            </span>
                        )}
                    </div>

                    <span className="inline-flex items-center gap-0.5 text-blue-600 font-medium opacity-0 group-hover:opacity-100 transition-opacity">
                        {hasVoted || isExpired ? "Ver resultados" : "Votar"} <ChevronRight className="size-3.5" />
                    </span>
                </div>
            </Link>
        </article>
    );
}