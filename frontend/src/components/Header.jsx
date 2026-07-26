import { Link, useNavigate } from "react-router-dom";
import { BarChart3, LogOut, Plus } from "lucide-react";
import { AuthContext } from '@/context/AuthContext.jsx';
import {useContext} from "react";

export default function Header() {
    const navigate = useNavigate();
    const { user, logout } = useContext(AuthContext);

    const handleLogout = () => {
        logout();
        navigate("/auth");
    };

    return (
        <header className="sticky top-0 z-30 border-b border-border/70 bg-background/80 backdrop-blur-xl">
            <div className="mx-auto flex max-w-5xl items-center justify-between gap-4 px-4 py-3.5">
                <Link to="/" className="flex items-center gap-2.5">
                    <span className="grid size-9 place-items-center rounded-lg bg-primary text-primary-foreground">
                        <BarChart3 className="size-5" />
                    </span>
                    <span className="font-display text-lg font-bold tracking-tight">Enquetes</span>
                </Link>

                <nav className="flex items-center gap-2 text-sm">
                    <Link
                        to="/"
                        className="rounded-md px-3 py-2 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                    >
                        Explorar
                    </Link>
                    <Link
                        to="/perfil"
                        className="rounded-md px-3 py-2 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                    >
                        Meu perfil
                    </Link>
                    <Link
                        to="/criar"
                        className="inline-flex items-center gap-1.5 rounded-md bg-primary px-3 py-2 font-medium text-primary-foreground transition-opacity hover:opacity-90"
                    >
                        <Plus className="size-4" />
                        <span className="hidden sm:inline">Nova enquete</span>
                    </Link>
                    {user?.email ? (
                        <button
                            type="button"
                            aria-label="Sair"
                            onClick={handleLogout}
                            className="rounded-md border border-border p-2 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                        >
                            <LogOut className="size-4" />
                        </button>
                    ) : (
                        <Link
                            to="/auth"
                            className="rounded-md bg-primary px-3.5 py-2 font-medium text-primary-foreground transition-opacity hover:opacity-90"
                        >
                            Entrar
                        </Link>
                    )}
                </nav>
            </div>
        </header>
    );
}