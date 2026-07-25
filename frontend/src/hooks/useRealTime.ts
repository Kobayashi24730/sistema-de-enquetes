import { useEffect, useState } from "react";
import { PollOptionResult } from "../types/types";

export function useRealTime(pollId: number | string) {
    const [option, setOptions] = useState<PollOptionResult[]>([]);
    const [isConnect, setIsConnnect] = useState(false);

    useEffect(() => {
        if (!pollId) return;
        const eventSource = new EventSource(`http://localhost:8000/api/polls/${pollId}/stream`);
        eventSource.onmessage = (event) => {
            try {
                const update: PollOptionResult[] = JSON.parse(event.data);
                setOptions(update);
            } catch (error) {
                console.error('Erro ao processar atualização em tempo real: ', error);
                setIsConnnect(false);
                eventSource.close();
            }
        }
        eventSource.onerror = (err) => {
            console.error('Erro na conexao SSE: ', err);
            setIsConnnect(false);
            eventSource.close();
        }
        return () => {
            eventSource.close();
        };
    }, [pollId]);
    return { option, isConnect };
}
