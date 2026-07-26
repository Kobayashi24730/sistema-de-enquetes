import React, { useState } from 'react';
import toast from 'react-hot-toast'; // Importa o toast

export default function ForgotPassword() {
    const [email, setEmail] = useState('');

    const handleRequestReset = async (e) => {
        e.preventDefault();

        // Notificação de carregamento enquanto aguarda a API
        const loadingToast = toast.loading('Enviando e-mail...');

        try {
            const response = await fetch('/api/forgot-password', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email }),
            });

            const data = await response.json();

            // Remove a mensagem de carregamento
            toast.dismiss(loadingToast);

            if (response.ok) {
                // Substitui: alert("Instruções enviadas!")
                toast.success(data.message || 'Instruções enviadas para o seu e-mail!');
                setEmail('');
            } else {
                // Substitui: alert("Erro: " + data.error)
                toast.error(data.error || 'Ocorreu um erro ao solicitar.');
            }
        } catch (error) {
            toast.dismiss(loadingToast);
            toast.error('Erro de conexão com o servidor.');
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