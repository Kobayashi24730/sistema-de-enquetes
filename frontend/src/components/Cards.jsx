import { Link } from "react-router-dom";
import { Clock, User2 } from "lucide-react";
import Resultados from "@/components/Resultados";
import { Search } from "lucide-react";

function checkIsExpired(expiresAt) {
    if (!expiresAt) return false;
    return new Date(expiresAt) < new Date();
}

function getTotalVotes(options) {
    if (!options || !Array.isArray(options)) return 0;
    return options.reduce((acc, curr) => acc + (curr.votes || 0), 0);
}
export default function Card({ poll, votedOptionId }) {
    if (!poll) return null;
    const expired = checkIsExpired(poll.expiresAt);
    const votesCount = getTotalVotes(poll.options);

    return (
        <div>
            <div className="flex items-center gap-2">
                <div>
                    <Search className="size-3.5" />
                    <span className="text-muted-foreground">Procurar enquete</span>
                </div>
                <button className="text-muted-foreground rounded-lg px-2 py-1 border border-slate-800">
                    todas as categorias
                </button>
                <dvi className="flex gap-2">
                    <button>
                        recentes
                    </button>
                    <button>
                        populares
                    </button>
                </dvi>
            </div>
            <div>
                <Link
                    to={`/enquete/${poll.id}`}
                    className="panel block p-5 rounded-xl border border-slate-800 bg-[#0f172a] transition-colors hover:border-primary/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                >
                    {/* Cabeçalho */}
                    <div className="mb-3 flex flex-wrap items-center gap-2 text-xs">
        <span className="rounded-full bg-secondary px-2.5 py-1 text-secondary-foreground font-medium">
          {poll.category}
        </span>

                        {expired ? (
                            <span className="rounded-full bg-muted px-2.5 py-1 text-muted-foreground font-medium">
            Encerrada
          </span>
                        ) : (
                            <span className="inline-flex items-center gap-1 rounded-full bg-primary/15 px-2.5 py-1 text-primary font-medium">
            <span className="size-1.5 animate-pulse rounded-full bg-primary" />
            ao vivo
          </span>
                        )}

                        <span className="inline-flex items-center gap-1 text-muted-foreground">
          <User2 className="size-3.5" />
                            {poll.authorName || poll.author}
        </span>
                    </div>

                    {/* Título e Descrição */}
                    <h3 className="text-lg font-semibold leading-snug text-foreground">
                        {poll.title}
                    </h3>
                    {poll.description && (
                        <p className="mt-1 line-clamp-2 text-sm text-muted-foreground">
                            {poll.description}
                        </p>
                    )}

                    {/* Resultados / Progresso */}
                    <div className="mt-4">
                        <Resultados poll={poll} votedOptionId={votedOptionId} />
                    </div>

                    {/* Rodapé */}
                    <div className="mt-4 flex items-center justify-between text-xs text-muted-foreground">
        <span className="tabular-nums">
          {votesCount} {votesCount === 1 ? "voto" : "votos"}
        </span>

                        {poll.expiresAt && (
                            <span className="inline-flex items-center gap-1">
            <Clock className="size-3.5" />
                                {expired
                                    ? "expirada"
                                    : `expira em ${new Date(poll.expiresAt).toLocaleDateString("pt-BR")}`}
          </span>
                        )}
                    </div>
                </Link>
            </div>
        </div>
    );
}