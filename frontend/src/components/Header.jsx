import { useState, useContext } from "react";
import { Link, useNavigate, useLocation } from "react-router-dom";
import { BarChart3, LogOut, Plus, Menu, X, User } from "lucide-react";
import { AuthContext } from '@/context/AuthContext.jsx';

export default function Header() {
    const navigate = useNavigate();
    const location = useLocation();
    const { user, logout } = useContext(AuthContext);
    const [isMenuOpen, setIsMenuOpen] = useState(false);

    const handleLogout = () => {
        logout();
        navigate("/auth");
    };
    const isActive = (path) => location.pathname === path;
    return (
        <header className="sticky top-0 z-30 border-b border-border/70 bg-background/80 backdrop-blur-xl">
            <div className="mx-auto flex max-w-5xl items-center justify-between gap-4 px-4 py-3">
                {/* Logo e Nome */}
                <Link to="/" className="flex items-center gap-2.5 transition-opacity hover:opacity-90">
                    <span className="grid size-9 place-items-center rounded-xl bg-primary text-primary-foreground shadow-sm">
                        <BarChart3 className="size-5" />
                    </span>
                    <span className="font-display text-lg font-bold tracking-tight">Enquetes</span>
                </Link>

                {/* Navegação Desktop */}
                <nav className="hidden items-center gap-1 text-sm md:flex">
                    <Link
                        to="/"
                        className={`rounded-lg px-3.5 py-2 font-medium transition-colors ${
                            isActive("/")
                                ? "bg-muted text-foreground"
                                : "text-muted-foreground hover:bg-muted/60 hover:text-foreground"
                        }`}
                    >
                        Explorar
                    </Link>
                    <Link
                        to="/perfil"
                        className={`rounded-lg px-3.5 py-2 font-medium transition-colors ${
                            isActive("/perfil")
                                ? "bg-muted text-foreground"
                                : "text-muted-foreground hover:bg-muted/60 hover:text-foreground"
                        }`}
                    >
                        Meu perfil
                    </Link>
                </nav>

                {/* componentes de ações  */}
                <div className="flex items-center gap-2">
                    <Link
                        to="/criar"
                        className="inline-flex items-center gap-1.5 rounded-lg bg-primary px-3.5 py-2 text-sm font-medium text-primary-foreground shadow-sm transition-all hover:opacity-90 active:scale-95"
                    >
                        <Plus className="size-4" />
                        <span className="hidden sm:inline">Nova enquete</span>
                    </Link>

                    {user?.email ? (
                        <button
                            type="button"
                            aria-label="Sair"
                            onClick={handleLogout}
                            className="hidden rounded-lg border border-border p-2 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground md:inline-flex"
                        >
                            <LogOut className="size-4" />
                        </button>
                    ) : (
                        <Link
                            to="/auth"
                            className="hidden rounded-lg bg-secondary px-3.5 py-2 text-sm font-medium text-secondary-foreground transition-colors hover:bg-secondary/80 md:inline-flex"
                        >
                            Entrar
                        </Link>
                    )}

                    {/* Botão do Menu Hambúrguer, apenas para mobile */}
                    <button
                        type="button"
                        onClick={() => setIsMenuOpen(!isMenuOpen)}
                        className="rounded-lg border border-border p-2 text-muted-foreground transition-colors hover:bg-muted md:hidden"
                        aria-label="Toggle Menu"
                    >
                        {isMenuOpen ? <X className="size-5" /> : <Menu className="size-5" />}
                    </button>
                </div>
            </div>

            {/* Menu Retrátil Mobile */}
            {isMenuOpen && (
                <div className="border-b border-border/70 bg-background px-4 py-4 md:hidden">
                    <nav className="flex flex-col gap-2 text-sm font-medium">
                        <Link
                            to="/"
                            onClick={() => setIsMenuOpen(false)}
                            className="rounded-lg px-3 py-2.5 text-muted-foreground hover:bg-muted hover:text-foreground"
                        >
                            Explorar
                        </Link>
                        <Link
                            to="/perfil"
                            onClick={() => setIsMenuOpen(false)}
                            className="rounded-lg px-3 py-2.5 text-muted-foreground hover:bg-muted hover:text-foreground"
                        >
                            Meu perfil
                        </Link>

                        <hr className="my-1 border-border/60" />

                        {user?.email ? (
                            <button
                                type="button"
                                onClick={() => {
                                    setIsMenuOpen(false);
                                    handleLogout();
                                }}
                                className="flex w-full items-center gap-2 rounded-lg px-3 py-2.5 text-red-500 hover:bg-red-500/10"
                            >
                                <LogOut className="size-4" />
                                Sair da conta
                            </button>
                        ) : (
                            <Link
                                to="/auth"
                                onClick={() => setIsMenuOpen(false)}
                                className="flex items-center gap-2 rounded-lg px-3 py-2.5 text-primary hover:bg-primary/10"
                            >
                                <User className="size-4" />
                                Entrar na conta
                            </Link>
                        )}
                    </nav>
                </div>
            )}
        </header>
    );
}