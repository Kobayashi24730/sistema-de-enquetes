import { createContext, useState, useEffect } from "react";
import api from "@/services/api.js";

export const AuthContext = createContext({});

export function AuthProvider({ children }) {
    const [user, setUser] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const token = localStorage.getItem('@Enquetes:token');

        if (token) {
            // Define o token no header para requisições futuras do Axios
            api.defaults.headers.common['Authorization'] = `Bearer ${token}`;

            api.get('/profile')
                .then(response => {
                    setUser(response.data);
                })
                .catch((err) => {
                    console.error("Erro ao carregar perfil com token:", err);
                    logout();
                })
                .finally(() => setLoading(false));
        } else {
            setLoading(false);
        }
    }, []);

    const login = async (email, senha) => {
        try {
            console.log("Enviando requisição de login...");
            const response = await api.post('/login', { email, senha });
            console.log("Resposta do servidor:", response.data);

            const { token, user: userData } = response.data;

            if (!token) {
                throw new Error("O servidor não retornou um token válido.");
            }

            // Salva no Storage
            localStorage.setItem('@Enquetes:token', token);
            // Seta o header padrão do Axios para as próximas chamadas
            api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
            setUser(userData);
        } catch (error) {
            console.error("Erro dentro do AuthContext -> login:", error);
            throw error;
        }
    };

    const register = async (nome, email, senha) => {
        try {
            console.log("Enviando requisição de cadastro...");
            const response = await api.post('/register', { nome, email, senha });
            console.log("Resposta do registro:", response.data);
            return response.data;
        } catch (error) {
            console.error("Erro dentro do AuthContext -> register:", error);
            throw error;
        }
    };

    const logout = () => {
        localStorage.removeItem('@Enquetes:token');
        delete api.defaults.headers.common['Authorization'];
        setUser(null);
    };

    return (
        <AuthContext.Provider value={{ user, setUser, login, register, logout, loading }}>
            {children}
        </AuthContext.Provider>
    );
}