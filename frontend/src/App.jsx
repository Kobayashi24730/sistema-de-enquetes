import { BrowserRouter, Routes, Route } from 'react-router-dom';
import Header from '@/components/Header.jsx';
import Home from '@/pages/Home.jsx';
import Auth from '@/pages/Auth.jsx';
import Perfil from '@/pages/Perfil.jsx';
import Criar from '@/pages/Criar.jsx';
import Editar from '@/pages/Editar.jsx';
import {AuthProvider} from "@/context/AuthContext.jsx";
import EnqueteDetalhe from "@/pages/EnqueteDetalhe.jsx";
import ForgotPassword from "@/pages/ForgotPassword.jsx";
import ResetPassword from "@/pages/ResetPassword.jsx";
import { Toaster} from "react-hot-toast";

export default function App() {
    return (
        <AuthProvider>
            <BrowserRouter>
                <div className="min-h-screen bg-background text-foreground">
                    <Toaster position="top-right" reverseOrder={false}/>
                    <Header />
                    <main>
                        <Routes>
                            <Route path="/" element={<Home />} />
                            <Route path="/auth" element={<Auth />} />
                            <Route path="/perfil" element={<Perfil />} />
                            <Route path="/criar" element={<Criar />} />
                            <Route path="/enquete/:id" element={<EnqueteDetalhe />} />
                            <Route path="/editar/:id" element={<Editar />} />
                            <Route path="/forgot-password" element={<ForgotPassword />} />
                            <Route path="/reset-password" element={<ResetPassword />} />
                        </Routes>
                    </main>
                </div>
            </BrowserRouter>
        </AuthProvider>
    );
}