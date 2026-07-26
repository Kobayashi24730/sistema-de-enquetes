import { BrowserRouter, Routes, Route } from 'react-router-dom';
import Header from '@/components/Header';
import Home from '@/pages/Home';
import Auth from '@/pages/Auth';
import Perfil from '@/pages/Perfil';
import Criar from '@/pages/Criar';
import {AuthProvider} from "@/context/AuthContext";
import EnqueteDetalhe from "@/pages/enqueteDetalhe";

export default function App() {
    return (
        <AuthProvider>
            <BrowserRouter>
                <div className="min-h-screen bg-background text-foreground">
                    <Header />
                    <main>
                        <Routes>
                            <Route path="/" element={<Home />} />
                            <Route path="/auth" element={<Auth />} />
                            <Route path="/perfil" element={<Perfil />} />
                            <Route path="/criar" element={<Criar />} />
                            <Route path="/enquete/:id" element={<EnqueteDetalhe />} />
                        </Routes>
                    </main>
                </div>
            </BrowserRouter>
        </AuthProvider>
    );
}