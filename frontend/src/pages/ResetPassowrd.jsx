import React, { useState } from 'react';
import { useSearchParams, useNavigate } from 'react-router-dom';
import toast from 'react-hot-toast';
import api from '../services/api';

export default function ResetPassword() {
    const [searchParams] = useSearchParams();
    const token = searchParams.get('token'); // Captura o ?token=... da URL
    const [password, setPassword] = useState('');
    const navigate = useNavigate();

    const handleResetPassword = async (e) => {
        e.preventDefault();

        if (!token) {
            toast.error('Token inválido ou ausente na URL.');
            return;
        }

        const loadingToast = toast.loading('Redefinindo senha...');

        try {
            // Envia o token e a nova senha diretamente como objeto JS
            const response = await api.post('/reset-password', { token, password });

            toast.dismiss(loadingToast);

            // Sucesso (Status 200 OK)
            toast.success(response.data.message || 'Senha alterada com sucesso!');

            // Redireciona o usuário para a tela de login
            navigate('/login');

        } catch (error) {
            toast.dismiss(loadingToast);

            // Trata o erro vindo do PHP (ex: link expirado ou senha curta)
            const errorMessage = error.response?.data?.error || 'Erro ao redefinir a senha.';
            toast.error(errorMessage);
        }
    };

    return (
        <form onSubmit={handleResetPassword}>
            <h2>Redefinir Senha</h2>
            <input
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                placeholder="Nova senha (mínimo 6 caracteres)"
                minLength={6}
                required
            />
            <button type="submit">Salvar Nova Senha</button>
        </form>
    );
}