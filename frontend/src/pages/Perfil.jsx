import { useContext, useState, useEffect } from 'react';
import { AuthContext } from '@/context/AuthContext';
import { Link } from 'react-router-dom';
import api from '@/services/api.js';
import toast from 'react-hot-toast';

export default function Perfil() {
    const { user, setUser, logout, loading } = useContext(AuthContext);
    const [nome, setNome] = useState('');
    const [email, setEmail] = useState('');
    const [submitting, setSubmitting] = useState(false);

    // Sincroniza o formulário sempre que o usuário for carregado/atualizado
    useEffect(() => {
        if (user) {
            setNome(user.nome || user.name || '');
            setEmail(user.email || '');
        }
    }, [user]);

    if (loading) {
        return (
            <div className="min-h-[60vh] flex flex-col items-center justify-center p-6">
                <div className="flex flex-col items-center gap-4 bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
                    <div className="w-10 h-10 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"></div>
                    <h1 className="text-gray-600 font-medium text-lg animate-pulse">
                        Carregando perfil...
                    </h1>
                </div>
            </div>
        );
    }

    if (!user) {
        return (
            <div className="min-h-[60vh] flex items-center justify-center p-4">
                <div className="max-w-md w-full bg-white p-8 rounded-2xl shadow-lg border border-gray-100 text-center flex flex-col items-center gap-5">
                    <div>
                        <p className="text-sm text-gray-500 mt-1">Por favor, faça login para acessar seu perfil.</p>
                    </div>
                    <Link to="/auth" className="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2.5 px-4 rounded-lg shadow-sm transition-colors text-sm text-center inline-block">
                        Fazer Login
                    </Link>
                </div>
            </div>
        );
    }

    // UPDATE (Editar dados do Usuário)
    const handleUpdate = async (e) => {
        e.preventDefault();
        if (!nome.trim() || !email.trim()) {
            toast.error('Preencha todos os campos!');
            return;
        }

        setSubmitting(true);
        try {
            await api.put(`/usuarios/item`, { nome, email });
            setUser({ ...user, nome, email });
            toast.success('Perfil atualizado com sucesso!');
        } catch (err) {
            const msg = err.response?.data?.error || 'Erro ao atualizar perfil.';
            toast.error(msg);
        } finally {
            setSubmitting(false);
        }
    };

    // DELETE (Excluir Conta)
    const handleDeleteAccount = async () => {
        if (window.confirm('Tem certeza que deseja excluir sua conta? Esta ação não poderá ser desfeita.')) {
            try {
                await api.delete(`/usuarios/${user.id}`);
                toast.success('Conta excluída com sucesso.');
                logout(); // Desloga o usuário após deletar
            } catch (err) {
                const msg = err.response?.data?.error || 'Erro ao deletar conta.';
                toast.error(msg);
            }
        }
    };

    // Pega a inicial com fallback seguro
    const initialLetter = (user?.nome || user?.name || 'U').charAt(0).toUpperCase();

    return (
        <div className="max-w-xl mx-auto my-8 p-6 bg-white rounded-2xl shadow-lg border border-gray-100 flex flex-col gap-6">
            {/* Cabeçalho */}
            <div className="border-b pb-4">
                <h2 className="text-2xl font-bold text-gray-800">
                    Olá, <span className="text-indigo-600">{user?.nome || user?.name}</span>!
                </h2>
                <p className="text-sm text-gray-500 mt-1">Seja bem-vindo(a) ao seu perfil</p>
            </div>

            {/* Card de Avatar / Usuário */}
            <div className="flex items-center gap-4 bg-gray-50 p-4 rounded-xl border border-gray-100">
                <div className="relative shrink-0">
                    <div className="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-100 to-indigo-200 border border-indigo-200 flex items-center justify-center overflow-hidden">
                        {user?.image ? (
                            <img
                                src={user.image}
                                alt={user?.nome || 'Avatar'}
                                className="w-full h-full object-cover"
                            />
                        ) : (
                            <span className="text-sm font-bold text-indigo-600">{initialLetter}</span>
                        )}
                    </div>
                    {/* Indicador Ativo (Tailwind puro) */}
                    <span className="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full border-2 border-white bg-emerald-500" />
                </div>
                <div>
                    <h3 className="font-semibold text-gray-800 text-lg">{user?.nome || user?.name}</h3>
                    <p className="text-sm text-gray-500">{user?.email}</p>
                </div>
            </div>

            {/* Formulário de Edição */}
            <form onSubmit={handleUpdate} className="flex flex-col gap-4">
                <h3 className="text-md font-semibold text-gray-700">Editar Dados</h3>

                <div className="flex flex-col gap-1">
                    <label className="text-xs font-medium text-gray-600">Nome completo</label>
                    <input
                        type="text"
                        value={nome}
                        placeholder="Digite seu nome"
                        onChange={(e) => setNome(e.target.value)}
                        className="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all text-sm"
                    />
                </div>

                <div className="flex flex-col gap-1">
                    <label className="text-xs font-medium text-gray-600">E-mail</label>
                    <input
                        type="email"
                        value={email}
                        placeholder="Digite seu e-mail"
                        onChange={(e) => setEmail(e.target.value)}
                        className="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all text-sm"
                    />
                </div>

                <button
                    type="submit"
                    disabled={submitting}
                    className="mt-2 w-full bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-medium py-2.5 rounded-lg shadow-sm transition-colors text-sm flex items-center justify-center gap-2"
                >
                    {submitting ? 'Salvando...' : 'Salvar Alterações'}
                </button>
            </form>

            <hr className="border-gray-100 my-1" />

            {/* Ações de Segurança / Conta */}
            <div className="flex flex-col sm:flex-row items-center justify-between gap-3">
                <Link
                    to="/forgot-password"
                    className="w-full sm:w-auto text-sm font-medium text-indigo-600 hover:text-indigo-800 hover:underline py-2 transition-colors text-center"
                >
                    Recuperar senha
                </Link>

                <button
                    type="button"
                    onClick={handleDeleteAccount}
                    className="w-full sm:w-auto text-sm font-medium bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 px-4 py-2 rounded-lg transition-colors"
                >
                    Deletar Minha Conta
                </button>
            </div>
        </div>
    );
}