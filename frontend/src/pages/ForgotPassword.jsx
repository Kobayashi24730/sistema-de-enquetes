import React, { useState } from 'react';
import toast from 'react-hot-toast';
import api from '../services/api';

export default function ForgotPassword() {
    const [email, setEmail] = useState('');

    const handleRequestReset = async (e) => {
        e.preventDefault();

        const loadingToast = toast.loading('Enviando e-mail...');

        try {
            const response = await api.post('/forgot-password', { email });

            toast.dismiss(loadingToast);
            toast.success(response.data.message || 'Instruções enviadas para o seu e-mail!');
            setEmail('');

        } catch (error) {
            toast.dismiss(loadingToast);
            const errorMessage = error.response?.data?.error || 'Erro de conexão com o servidor.';
            toast.error(errorMessage);
        }
    };

    return (
        <div className="min-h-screen flex items-center justify-center bg-gray-50 px-4 sm:px-6 lg:px-8">
            <div className="max-w-md w-full space-y-8 bg-white p-8 rounded-xl shadow-lg border border-gray-100">
                <div className="text-center">
                    <h2 className="text-3xl font-extrabold text-gray-900">Recuperar Senha</h2>
                    <p className="mt-2 text-sm text-gray-600">
                        Informe o seu e-mail e enviaremos um link para você redefinir sua senha.
                    </p>
                </div>

                <form className="mt-8 space-y-6" onSubmit={handleRequestReset}>
                    <div>
                        <label htmlFor="email" className="block text-sm font-medium text-gray-700 mb-2">
                            Endereço de E-mail
                        </label>
                        <input
                            id="email"
                            type="email"
                            value={email}
                            onChange={(e) => setEmail(e.target.value)}
                            placeholder="seu@email.com"
                            required
                            className="appearance-none rounded-lg relative block w-full px-3 py-2.5 border border-gray-300 placeholder-gray-400 text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-colors"
                        />
                    </div>

                    <button
                        type="submit"
                        className="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                    >
                        Enviar Link de Recuperação
                    </button>
                </form>
            </div>
        </div>
    );
}