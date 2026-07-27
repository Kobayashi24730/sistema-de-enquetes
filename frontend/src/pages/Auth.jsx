import {useContext, useState} from 'react';
import toast from 'react-hot-toast';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Mail, Lock, User, Loader2, AlertCircle } from 'lucide-react';
import {
    Form,
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from "@/components/ui/form.jsx";
import { Input } from "@/components/ui/input.jsx";
import {AuthContext} from "@/context/AuthContext.jsx";
import { useNavigate } from 'react-router-dom';
import { Link } from 'react-router-dom';

// Schema de validação do formulário
const formSchema = z.object({
    nome: z.string().optional(),
    email: z.string().email("Insira um e-mail válido"),
    senha: z.string().min(6, "A senha deve ter pelo menos 6 caracteres"),
});

export default function Auth() {
    const { login, register } = useContext(AuthContext);
    const navigate = useNavigate();
    const [typeForm, setTypeForm] = useState("login");
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);
    const isLogin = typeForm === "login";
    const form = useForm({
        resolver: zodResolver(formSchema),
        defaultValues: { nome: '', email: '', senha: '' }
    });

    // Função de envio do formulário /login && /register
    async function onSubmit(values) {
        setError('');
        setLoading(true);
        try {
            if (typeForm === "login") {
                await login(values.email, values.senha);
                navigate('/');
            } else {
                await register(values.nome, values.email, values.senha);
                setLoading(true);
                toast.success('Conta criada com sucesso.');
                setTypeForm('login');
                form.reset({ nome: '', email: '', senha: '' });
            }
        } catch (err) {
            setError(err.response?.data?.error ||'Ocorreu um erro ao processar sua solicitação.');
        } finally {
            setLoading(false);
        }
    }

    return (
        <section className="w-full max-w-md mx-auto mt-10 overflow-hidden rounded-xl border bg-background shadow-sm">
            <div className="h-1 w-full bg-gradient-to-r from-primary/40 via-primary to-primary/40" />
            <div className="p-8">
                <div className="mb-6 flex items-start justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            {isLogin ? "Bem-vindo de volta" : "Criar conta"}
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {isLogin
                                ? "Entre com seus dados para continuar"
                                : "Preencha os campos para começar"}
                        </p>
                    </div>
                </div>

                <Form {...form}>
                    <form onSubmit={form.handleSubmit(onSubmit)} className="flex flex-col gap-4">
                        {!isLogin && (
                            <FormField
                                control={form.control}
                                name="nome"
                                render={({ field }) => (
                                    <FormItem>
                                        <FormLabel>Nome</FormLabel>
                                        <FormControl>
                                            <div className="relative">
                                                <User className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                                <Input
                                                    placeholder="Seu nome completo"
                                                    className="pl-9"
                                                    {...field}
                                                />
                                            </div>
                                        </FormControl>
                                        <FormMessage />
                                    </FormItem>
                                )}
                            />
                        )}

                        <FormField
                            control={form.control}
                            name="email"
                            render={({ field }) => (
                                <FormItem>
                                    <FormLabel>E-mail</FormLabel>
                                    <FormControl>
                                        <div className="relative">
                                            <Mail className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                            <Input
                                                type="email"
                                                placeholder="seu@email.com"
                                                className="pl-9"
                                                {...field}
                                            />
                                        </div>
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            )}
                        />

                        <FormField
                            control={form.control}
                            name="senha"
                            render={({ field }) => (
                                <FormItem>
                                    <div className="flex items-center justify-between">
                                        <FormLabel>Senha</FormLabel>
                                        {isLogin && (
                                            <Link
                                                to="/forgot-password"
                                                className="text-xs text-muted-foreground underline-offset-2 transition-colors hover:text-primary hover:underline"
                                            >
                                                Esqueceu a senha?
                                            </Link>
                                        )}
                                    </div>
                                    <FormControl>
                                        <div className="relative">
                                            <Lock className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                            <Input
                                                type="password"
                                                placeholder="••••••••"
                                                className="pl-9"
                                                {...field}
                                            />
                                        </div>
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            )}
                        />

                        {error && (
                            <div className="flex items-start gap-2 rounded-md border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive">
                                <AlertCircle className="mt-0.5 h-4 w-4 shrink-0" />
                                <span>{error}</span>
                            </div>
                        )}

                        <button
                            type="submit"
                            disabled={loading}
                            className="mt-1 flex items-center justify-center gap-2 rounded-md bg-primary px-3.5 py-2.5 font-medium text-primary-foreground shadow-sm transition-all hover:opacity-90 active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {loading && <Loader2 className="h-4 w-4 animate-spin" />}
                            {loading ? "Carregando..." : isLogin ? "Entrar" : "Cadastrar"}
                        </button>
                    </form>
                </Form>

                <div className="mt-6 border-t pt-4 text-center text-sm text-muted-foreground">
                    {isLogin ? "Ainda não tem uma conta?" : "Já tem uma conta?"}{" "}
                    <button
                        type="button"
                        className="font-medium text-primary underline-offset-2 transition-colors hover:underline"
                        onClick={() => {
                            setTypeForm(isLogin ? "register" : "login");
                            setError('');
                            form.reset({ nome: '', email: '', senha: '' });
                        }}
                    >
                        {isLogin ? "Criar uma conta" : "Fazer login"}
                    </button>
                </div>
            </div>
        </section>
    );
}