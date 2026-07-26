import { useState, useEffect, useCallback, useRef } from 'react';
import api from '../services/api.js';

/**
 * Utilitários para gerenciar os votos no localStorage
 */
export const getVotedPolls = () => {
    try {
        const voted = localStorage.getItem('voted_polls');
        return voted ? JSON.parse(voted) : [];
    } catch {
        return [];
    }
};

export const saveVotedPoll = (pollId) => {
    try {
        const voted = getVotedPolls();
        if (!voted.includes(pollId)) {
            voted.push(pollId);
            localStorage.setItem('voted_polls', JSON.stringify(voted));
        }
    } catch (e) {
        console.error('Erro ao salvar voto localmente', e);
    }
};

/**
 * Busca a LISTA de todas as enquetes, adicionando o status de "hasVoted"
 */
export function usePollsRealtime(intervalo = 3000) {
    const [polls, setPolls] = useState([]);
    const [loading, setLoading] = useState(true);
    const [userVotes, setUserVotes] = useState(getVotedPolls());
    const isFetchingRef = useRef(false);

    // Função para registrar o voto do usuário e atualizar a tela imediatamente
    const recordVote = useCallback((pollId) => {
        saveVotedPoll(pollId);
        setUserVotes((prev) => [...prev, pollId]);
    }, []);

    const fetchPolls = useCallback(async (isInitial = false) => {
        if (isFetchingRef.current) return;
        isFetchingRef.current = true;
        if (isInitial) setLoading(true);

        try {
            const response = await api.get('/enquetes');
            const data = response.data || [];

            // Injeta a propriedade `hasVoted` baseada nos votos armazenados no navegador
            const votedIds = getVotedPolls();
            const pollsWithVoteStatus = data.map((poll) => ({
                ...poll,
                hasVoted: votedIds.includes(poll.id)
            }));

            setPolls(pollsWithVoteStatus);
        } catch (error) {
            console.error('Erro ao atualizar lista de enquetes:', error);
        } finally {
            isFetchingRef.current = false;
            if (isInitial) setLoading(false);
        }
    }, []);

    useEffect(() => {
        fetchPolls(true);

        const timer = setInterval(() => {
            if (document.visibilityState === 'visible') {
                fetchPolls(false);
            }
        }, intervalo);

        const handleVisibility = () => {
            if (document.visibilityState === 'visible') {
                fetchPolls(false);
            }
        };

        document.addEventListener('visibilitychange', handleVisibility);
        return () => {
            clearInterval(timer);
            document.removeEventListener('visibilitychange', handleVisibility);
        };
    }, [intervalo, fetchPolls]);

    return {
        polls,
        loading,
        userVotes,
        recordVote,
        refetch: () => fetchPolls(false)
    };
}