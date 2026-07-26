import { useState, useEffect, useCallback, useRef } from 'react';
import api from '../services/api.js';

/**
 * Busca a LISTA de todas as enquetes, atualizando em intervalo fixo.
 * Use na Home (grid de cards). Para uma enquete específica, use usePollRealtime.
 */
export function usePollsRealtime(intervalo = 5000) {
    const [polls, setPolls] = useState([]);
    const [loading, setLoading] = useState(true);
    const isFetchingRef = useRef(false);

    const fetchPolls = useCallback(async (isInitial = false) => {
        if (isFetchingRef.current) return;
        isFetchingRef.current = true;
        if (isInitial) setLoading(true);

        try {
            // Ajuste a rota abaixo para bater com o endpoint de listagem do seu backend
            // (ex: '/enquetes', '/enquetes/index', '/polls')
            const response = await api.get('/enquetes');
            setPolls(response.data || []);
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

    return { polls, loading, refetch: () => fetchPolls(false) };
}