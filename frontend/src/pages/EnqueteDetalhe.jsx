import { useEffect, useState, useContext } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import { ArrowLeft, Share2, Edit3, Trash2, User2, Clock, CheckCircle2, BarChart2 } from 'lucide-react';
import api from '../services/api.js';
import { AuthContext } from '../context/AuthContext.jsx';
import Resultados from '@/components/Resultados.jsx';
import { Chart } from '@/components/Charts.jsx';
import { getVotedEnquete, saveVotedEnquete } from '@/hooks/useEnqueteRealtime';

// Estilos Fluent estritamente claros
const fluentCard =
    'rounded-lg border border-neutral-200/80 bg-white p-6 space-y-4 shadow-[0_2px_4px_rgba(0,0,0,0.04)]';

const fluentButtonSecondary =
    'inline-flex items-center gap-2 rounded bg-white border border-neutral-300 px-3.5 py-1.5 text-xs font-semibold text-neutral-800 transition-all hover:bg-neutral-100 hover:border-neutral-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-600';

export default function EnqueteDetalhe() {
    const { id } = useParams();
    const navigate = useNavigate();
    const { user } = useContext(AuthContext);

    const [poll, setPoll] = useState(null);
    const [loadingPoll, setLoadingPoll] = useState(true);
    const [error, setError] = useState('');
    const [selectedOption, setSelectedOption] = useState(null);
    const [votedOptionId, setVotedOptionId] = useState(null);
    const [hasVotedLocal, setHasVotedLocal] = useState(false);
    const [voting, setVoting] = useState(false);

    useEffect(() => {
        // Checa no localStorage assim que abre a página usando a função correta
        const localVotes = getVotedEnquete();
        if (localVotes.includes(id) || localVotes.includes(Number(id))) {
            setHasVotedLocal(true);
        }

        fetchPoll();
    }, [id]);

    const fetchPoll = async () => {
        try {
            const response = await api.get(`/enquetes/show?id=${id}`);
            setPoll(response.data);

            if (response.data.voted_option_id) {
                setVotedOptionId(response.data.voted_option_id);
                setHasVotedLocal(true);
            }
        } catch (err) {
            console.error('Erro ao buscar enquete:', err);
            setError('Enquete não encontrada ou falha no servidor.');
        } finally {
            setLoadingPoll(false);
        }
    };

    const handleVote = async (e) => {
        e.preventDefault();
        if (!selectedOption) return;

        setVoting(true);
        try {
            // Envia para o backend (payload: poll_id / option_id)
            await api.post('/enquetes/vote', {
                poll_id: id,
                option_id: selectedOption
            });

            // 1. Grava no localStorage do navegador para bloquear votos futuros
            saveVotedEnquete(id);
            setHasVotedLocal(true);
            setVotedOptionId(selectedOption);

            // 2. Recarrega os dados da enquete para atualizar contagens
            await fetchPoll();
        } catch (err) {
            alert(err.response?.data?.error || 'Erro ao registrar seu voto.');
        } finally {
            setVoting(false);
        }
    };

    const handleDelete = async () => {
        if (window.confirm('Tem certeza de que deseja excluir esta enquete?')) {
            try {
                await api.delete(`/enquetes/delete?id=${id}`);
                navigate('/');
            } catch (err) {
                alert(err.response?.data?.error || 'Erro ao excluir a enquete.');
            }
        }
    };

    const handleShare = () => {
        navigator.clipboard.writeText(window.location.href);
        alert('Link copiado para a área de transferência!');
    };

    if (loadingPoll) {
        return (
            <div className="flex h-64 items-center justify-center">
                <div className="flex items-center gap-2 text-xs font-semibold text-neutral-500">
                    <span className="size-4 animate-spin rounded-full border-2 border-blue-600 border-t-transparent" />
                    Carregando enquete...
                </div>
            </div>
        );
    }

    if (error || !poll) {
        return (
            <div className="max-w-3xl mx-auto p-12 text-center space-y-4">
                <p className="text-sm font-medium text-red-600">{error || 'Enquete não encontrada.'}</p>
                <Link to="/" className="inline-flex items-center gap-2 text-xs font-semibold text-blue-600 hover:underline">
                    <ArrowLeft className="size-3.5" /> Voltar para a Home
                </Link>
            </div>
        );
    }

    const options = poll.options || poll.opcoes || [];
    const totalVotes = options.reduce((acc, opt) => acc + Number(opt.votes ?? opt.votos ?? 0), 0);

    const isExpired = poll.expires_at ? new Date(poll.expires_at) < new Date() : false;
    const isOwner = Boolean(user && poll && (Number(user.id) === Number(poll.user_id) || user.nome === poll.criador));

    // Pode votar se: NÃO tiver expirado E NÃO tiver votado antes (nem localmente nem na API)
    const canVote = !hasVotedLocal && !votedOptionId && !isExpired;

    return (
        <div className="max-w-3xl mx-auto space-y-5 p-4 text-neutral-900 md:p-6 font-sans">
            {/* Navegação Voltar */}
            <Link
                to="/"
                className="inline-flex items-center gap-1.5 text-xs font-medium text-neutral-500 hover:text-neutral-900 transition-colors"
            >
                <ArrowLeft className="size-3.5" />
                Todas as enquetes
            </Link>

            {/* Cabeçalho da Enquete */}
            <div className={fluentCard}>
                <div className="flex flex-wrap items-center justify-between gap-2 border-b border-neutral-100 pb-3">
                    <div className="flex items-center gap-2">
                        {poll.category && (
                            <span className="rounded bg-neutral-100 px-2 py-0.5 text-[11px] font-semibold text-neutral-600 border border-neutral-200/60">
                                {poll.category}
                            </span>
                        )}

                        {isExpired && (
                            <span className="rounded bg-neutral-100 px-2 py-0.5 text-[11px] font-medium text-neutral-500">
                                Encerrada
                            </span>
                        )}

                        {hasVotedLocal && (
                            <span className="inline-flex items-center gap-1 rounded bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 border border-emerald-200/60">
                                <CheckCircle2 className="size-3 text-emerald-600" />
                                Votado
                            </span>
                        )}
                    </div>

                    <div className="flex items-center gap-3 text-xs text-neutral-500">
                        <span className="inline-flex items-center gap-1 font-medium">
                            <User2 className="size-3.5 text-neutral-400" />
                            {poll.criador || poll.author || 'Anônimo'}
                        </span>

                        {poll.created_at && (
                            <span className="inline-flex items-center gap-1 text-neutral-400">
                                <Clock className="size-3.5 text-neutral-400" />
                                {new Date(poll.created_at).toLocaleDateString('pt-BR')}
                            </span>
                        )}
                    </div>
                </div>

                <div className="space-y-1.5 pt-1">
                    <h1 className="text-xl font-bold tracking-tight text-neutral-900 md:text-2xl">
                        {poll.title}
                    </h1>
                    {poll.description && (
                        <p className="text-xs leading-relaxed text-neutral-600">
                            {poll.description}
                        </p>
                    )}
                </div>
            </div>

            {/* Formulário de Votação (Apenas se canVote === true) */}
            {canVote ? (
                <form onSubmit={handleVote} className={fluentCard}>
                    <h2 className="text-[11px] font-semibold uppercase tracking-wider text-neutral-500">
                        Opções de Voto
                    </h2>

                    <div className="space-y-2">
                        {options.map((option) => {
                            const isSelected = selectedOption === option.id;
                            return (
                                <label
                                    key={option.id}
                                    className={`flex cursor-pointer items-center gap-3 rounded border p-3 transition-all ${
                                        isSelected
                                            ? 'border-blue-600 bg-blue-50/60 text-blue-950 shadow-sm'
                                            : 'border-neutral-200 bg-white hover:bg-neutral-50'
                                    }`}
                                >
                                    <input
                                        type="radio"
                                        name="poll-option"
                                        value={option.id}
                                        checked={isSelected}
                                        onChange={() => setSelectedOption(option.id)}
                                        className="accent-blue-600 size-4"
                                    />
                                    <span className="text-xs font-medium text-neutral-800">
                                        {option.option_text || option.text || option.opcao_texto}
                                    </span>
                                </label>
                            );
                        })}
                    </div>

                    <button
                        type="submit"
                        disabled={!selectedOption || voting}
                        className="w-full rounded bg-blue-600 py-2 text-xs font-semibold text-white shadow-sm transition-all hover:bg-blue-700 active:bg-blue-800 disabled:opacity-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-600"
                    >
                        {voting ? 'Computando voto...' : 'Confirmar Voto'}
                    </button>
                </form>
            ) : (
                /* Alerta de Voto Confirmado / Já Votou */
                hasVotedLocal && (
                    <div className="flex items-center gap-2 rounded border border-emerald-200 bg-emerald-50/70 p-3 text-xs font-medium text-emerald-800">
                        <CheckCircle2 className="size-4 shrink-0 text-emerald-600" />
                        Seu voto foi registrado nesta enquete.
                    </div>
                )
            )}

            {/* Painel de Resultados em Barras */}
            <div className={fluentCard}>
                <div className="flex items-center justify-between border-b border-neutral-100 pb-2.5">
                    <h2 className="text-[11px] font-semibold uppercase tracking-wider text-neutral-500">
                        Resultados
                    </h2>
                    <span className="inline-flex items-center gap-1 text-xs text-neutral-500 font-medium">
                        <BarChart2 className="size-3.5 text-blue-600" />
                        {totalVotes} {totalVotes === 1 ? 'voto' : 'votos'}
                    </span>
                </div>

                <Resultados enquete={poll} votedOptionId={votedOptionId} />

                {isOwner && (
                    <p className="border-t border-neutral-100 pt-2 text-[11px] text-neutral-400">
                        Você é o proprietário desta enquete.
                    </p>
                )}
            </div>

            {/* Componente de Gráfico Donut/Pie */}
            <Chart options={options} />

            {/* Barra de Ações do Rodapé */}
            <div className="flex flex-wrap items-center justify-between gap-3 pt-1">
                <button onClick={handleShare} className={fluentButtonSecondary}>
                    <Share2 className="size-3.5" />
                    Compartilhar
                </button>

                {isOwner && (
                    <div className="flex items-center gap-2">
                        <button
                            onClick={() => navigate(`/editar/${id}`)}
                            className={fluentButtonSecondary}
                        >
                            <Edit3 className="size-3.5" />
                            Editar
                        </button>

                        <button
                            onClick={handleDelete}
                            className={`${fluentButtonSecondary} text-red-600 hover:text-red-700 hover:bg-red-50 hover:border-red-300`}
                        >
                            <Trash2 className="size-3.5" />
                            Excluir
                        </button>
                    </div>
                )}
            </div>
        </div>
    );
}