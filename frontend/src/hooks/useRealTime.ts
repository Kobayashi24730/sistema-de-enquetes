import { useState, useEffect, useCallback, useRef } from 'react';
import api from '../services/api.js';

const BASE_URL = api.defaults.baseURL || "http://localhost:8080";

// Lista os votos do localStorage
export const getVotedEnquete = () => {
    try {
        const voted = localStorage.getItem('voted_enquete');
        return voted ? JSON.parse(voted) : [];
    } catch {
        return [];
    }
};

// Salva o voto do usuário localmente
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

export function useEnqueteRealtime() {
    const [enquetes, setEnquetes] = useState([]);
    const [loading, setLoading] = useState(true);
    const [userVotes, setUserVotes] = useState(getVotedEnquete());
    const isFetchingRef = useRef(false);

    // Registrar voto local
    const recordVote = useCallback((enqueteId) => {
        saveVotedEnquete(enqueteId);
        setUserVotes((prev) => [...prev, enqueteId]);
    }, []);

    const processEnquetesWithVoteStatus = useCallback((dataList) => {
        const localVotes = getVotedEnquete();
        return (dataList || []).map((enquete) => {
            let parsedOptions = enquete.options;
            // Converte string JSON vinda do MySQL/PHP
            if (parsedOptions && typeof parsedOptions === 'string') {
                try {
                    parsedOptions = JSON.parse(parsedOptions);
                } catch (e) {
                    parsedOptions = [];
                }
            }
            if (!Array.isArray(parsedOptions)) {
                parsedOptions = [];
            }

            // Normaliza cada opção garantindo que votes seja numérico
            const normalizedOptions = parsedOptions.map((opt) => {
                const rawVotes = opt.votes ?? opt.votos ?? opt.votos_count ?? 0;
                return {
                    ...opt,
                    votes: Number(rawVotes) || 0
                };
            });
            // Soma forçada para não depender de total_votes zerado da API
            const calculatedTotal = normalizedOptions.reduce((acc, opt) => acc + opt.votes, 0);
            return {
                ...enquete,
                options: normalizedOptions,
                total_votes: calculatedTotal,
                hasVoted: localVotes.includes(enquete.id)
            };
        });
    }, []);

    // Busca via REST API
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
        fetchEnquetes(true);

        const connect = () => {
            eventSource = new EventSource(`${BASE_URL}/stream`);

            eventSource.onmessage = (event) => {
                try {
                    // Ignora eventos de keep-alive enviados como comentário pelo PHP
                    if (!event.data || event.data.trim() === "" || event.data.startsWith(":")) return;
                    const data = JSON.parse(event.data);
                    if (Array.isArray(data) && data.length > 0) {
                        const processed = processEnquetesWithVoteStatus(data);
                        setEnquetes(processed);
                    }
                    setLoading(false);
                } catch (err) {
                    console.error('Erro ao processar JSON do SSE:', err);
                }
            };
            eventSource.onerror = (err) => {
            };
        };

        connect();

        return () => {
            if (eventSource) {
                eventSource.close();
            }
        };
    }, []);

    return {
        enquetes,
        loading,
        userVotes,
        recordVote,
        refetch: () => fetchEnquetes(false)
    };
}