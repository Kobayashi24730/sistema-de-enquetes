import axios from 'axios';

const api = axios.create({
    baseURL: 'http://localhost:8000', // Retirada a barra do final
});

api.interceptors.request.use((config) => {
    // Chave corrigida para bater com o AuthContext
    const token = localStorage.getItem('@Enquetes:token');

    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }

    return config;
}, (error) => {
    return Promise.reject(error);
});

export default api;