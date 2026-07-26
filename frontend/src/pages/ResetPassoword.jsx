import React, { useState } from 'react';
import { useSearchParams, useNavigate } from 'react-router-dom';
import toast from 'react-hot-toast';
import api from '../services/api';

export default function ResetPassword() {
    const [searchParams] = useSearchParams();
    const token = searchParams.get('token');
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
            const response = await api.post('/reset-password', { token, password });
            toast.dismiss(loadingToast);
            toast.success(response.data.message || 'Senha alterada com sucesso!');
            navigate('/auth');
        } catch (error) {
            toast.dismiss(loadingToast);
            const errorMessage = error.response?.data?.error || 'Erro ao redefinir a senha.';
            toast.error(errorMessage);
        }
    };

    return (
        <div className="min-h-screen flex items-center justify-center bg-gray-50 px-4 sm:px-6 lg:px-8">
            <div className="max-w-md w-full space-y-8 bg-white p-8 rounded-xl shadow-lg border border-gray-100">
                <div className="text-center">
                    <h2 className="text-3xl font-extrabold text-gray-900">Redefinir Senha</h2>
                    <p className="mt-2 text-sm text-gray-600">
                        Digite sua nova senha abaixo para recuperar o acesso à conta.
                    </p>
                </div>

                <form className="mt-8 space-y-6" onSubmit={handleResetPassword}>
                    <div>
                        <label htmlFor="password" className="block text-sm font-medium text-gray-700 mb-2">
                            Nova Senha
                        </label>
                        <input
                            id="password"
                            type="password"
                            value={password}
                            onChange={(e) => setPassword(e.target.value)}
                            placeholder="Mínimo 6 caracteres"
                            minLength={6}
                            required
                            className="appearance-none rounded-lg relative block w-full px-3 py-2.5 border border-gray-300 placeholder-gray-400 text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-colors"
                        />
                    </div>

                    <button
                        type="submit"
                        className="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                    >
                        Salvar Nova Senha
                    </button>
                </form>
            </div>
        </div>
    );
}