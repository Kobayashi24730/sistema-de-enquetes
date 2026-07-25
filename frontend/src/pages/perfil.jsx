import { useContext, useState } from 'react';
import { AuthContext } from '@/context/AuthContext';
import api from '@/services/api';

export default function Perfil() {
    const { user, setUser, logout } = useContext(AuthContext);
    const [nome, setNome] = useState(user?.nome || '');
    const [email, setEmail] = useState(user?.email || '');

    // UPDATE (Editar dados do Usuário)
    const handleUpdate = async (e) => {
        e.preventDefault();
        try {
            await api.put(`/usuarios/${user.id}`, { nome, email });
            setUser({ ...user, nome, email }); // Atualiza o estado global
            alert('Perfil atualizado com sucesso!');
        } catch (err) {
            alert('Erro ao atualizar perfil.');
        }
    };

    // DELETE (Excluir Conta)
    const handleDeleteAccount = async () => {
        if (confirm('Tem certeza que deseja excluir sua conta?')) {
            try {
                await api.delete(`/usuarios/${user.id}`);
                logout(); // Desloga o usuário após deletar
            } catch (err) {
                alert('Erro ao deletar conta.');
            }
        }
    };

    return (
        <div>
            <h2>Meu Perfil</h2>
            <form onSubmit={handleUpdate}>
                <input value={nome} onChange={(e) => setNome(e.target.value)} />
                <input value={email} onChange={(e) => setEmail(e.target.value)} />
                <button type="submit">Salvar Alterações</button>
            </form>

            <button onClick={handleDeleteAccount} className="bg-red-500 text-white">
                Deletar Minha Conta
            </button>
        </div>
    );
}