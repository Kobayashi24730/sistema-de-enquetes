import axios from 'axios';

const api = axios.create({
    baseURL: import.meta.env.VITE_API_URL || 'https://sistema-de-enquetes-backend.vercel.app/api',
});

api.interceptors.request.use((config) => {
    const token = localStorage.getItem('@Enquetes:token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
}, (error) => {
    // em caso de erro ele retorna o erro para quem chamou a api
    return Promise.reject(error);
});

export default api;