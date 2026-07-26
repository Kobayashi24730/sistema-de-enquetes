import React, { useState } from 'react';
import toast from 'react-hot-toast';
import api from '../services/api';

export default function ForgotPassword() {
    const [email, setEmail] = useState('');

    const handleRequestReset = async (e) => {
        e.preventDefault();

        // Notificação de carregamento enquanto aguarda a API
        const loadingToast = toast.loading('Enviando e-mail...');

        try {
            // 1. O Axios faz o POST e já converte a resposta JSON em objeto
            const response = await api.post('/forgot-password', { email });

            // Remove o toast de carregamento
            toast.dismiss(loadingToast);

            // 2. Se chegou aqui, o status é 2xx (Sucesso)
            // Os dados do PHP estarão dentro de response.data
            toast.success(response.data.message || 'Instruções enviadas para o seu e-mail!');
            setEmail('');

        } catch (error) {
            toast.dismiss(loadingToast);

            // 3. Se o PHP respondeu com erro (400, 500, etc), a mensagem estará em error.response.data
            const errorMessage = error.response?.data?.error || 'Erro de conexão com o servidor.';
            toast.error(errorMessage);
        }
    };

    return (
        <form onSubmit={handleRequestReset}>
            <input
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder="Seu e-mail"
                required
            />
            <button type="submit">Recuperar Senha</button>
        </form>
    );
}