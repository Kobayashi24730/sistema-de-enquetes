import { useState, useEffect, useCallback, useRef } from 'react';
import api from '../services/api.js';

const BASE_URL = api.defaults.baseURL || "http://localhost:8000";

// lista os votos do localStorage
export const getVotedEnquete = () => {
    try {
        const voted = localStorage.getItem('voted_enquete');
        return voted ? JSON.parse(voted) : [];
    } catch {
        return [];
    }
};

// Salva o voto do usuário localmente se ele ainda não votou naquela enquete
export const saveVotedEnquete = (enqueteId) => {
    try {
        const voted = getVotedEnquete();
        if (!voted.includes(enqueteId)) {
            voted.push(enqueteId);
            localStorage.setItem('voted_enquete', JSON.stringify(voted));
        }
    } catch (e) {
        console.error('Erro ao salvar voto localmente', e);
    }
};


export function useEnqueteRealtime(intervalo = 3000) {
    const [enquetes, setEnquetes] = useState([]);
    const [loading, setLoading] = useState(true);
    const [userVotes, setUserVotes] = useState(getVotedEnquete());
    const isFetchingRef = useRef(false);

    // Função para registrar o voto do usuário e atualizar a tela imediatamente
    const recordVote = useCallback((enqueteId) => {
        saveVotedEnquete(enqueteId);
        setUserVotes((prev) => [...prev, enqueteId]);
    }, []);

    // Função utilitária para mapear enquetes injetando o status de `hasVoted`
    const processEnquetesWithVoteStatus = useCallback((dataList) => {
        const localVotes = getVotedEnquete();
        return (dataList || []).map((enquete) => ({
            ...enquete,
            hasVoted: localVotes.includes(enquete.id)
        }));
    }, []);

    const fetchEnquetes = useCallback(async (isInitial = false) => {
        if (isFetchingRef.current) return;
        isFetchingRef.current = true;
        if (isInitial) setLoading(true);

        try {
            const response = await api.get('/enquetes');
            const data = response.data || [];
            setEnquetes(processEnquetesWithVoteStatus(data));
        } catch (error) {
            console.error('Erro ao atualizar lista de enquetes:', error);
        } finally {
            isFetchingRef.current = false;
            if (isInitial) setLoading(false);
        }
    }, [processEnquetesWithVoteStatus]);

    useEffect(() => {
        let eventSource = null;
        let reconnectTimeout = null;

        const connectSSE = () => {
            try {
                eventSource = new EventSource(`${BASE_URL}/stream`);
                eventSource.onmessage = (event) => {
                    try {
                        const data = JSON.parse(event.data)
                        setEnquetes(processEnquetesWithVoteStatus(data));
                        setLoading(false);
                    } catch (err) {
                        console.error('Erro ao processar evento SSE:', err);
                    }
                }
            } catch (error) {
                console.error('Erro ao conectar SSE:', error);
            }
            eventSource.onerror = (error) => {
                console.warn('Conexão SSE perdida. Tentando reconectar em 5 segundos...', error);
                eventSource.close();
                reconnectTimeout = setTimeout(() => { connectSSE(); }, 5000);
            };
        };

        fetchEnquetes(true);
        connectSSE();

        // Cleanup ao desmontar o componente
        return () => {
            if (eventSource) eventSource.close();
            if (reconnectTimeout) clearTimeout(reconnectTimeout);
        }
    }, [fetchEnquetes, processEnquetesWithVoteStatus]);

    return {
        enquetes,
        loading,
        userVotes,
        recordVote,
        refetch: () => fetchEnquetes(false)
    };
}