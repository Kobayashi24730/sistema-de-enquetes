import { useState, useContext } from 'react';
import {Link, useNavigate} from 'react-router-dom';
import { Plus, Trash2 } from 'lucide-react';
import api from '@/services/api';
import { AuthContext } from '@/context/AuthContext';

const inputsSettings = [
    { title: "Título", type: "text", name: "title", placeholder: "Ex: Qual a melhor linguagem?" },
    { title: "Categoria", type: "text", name: "category", placeholder: "Ex: Tecnologia" },
    { title: "Data de expiração", type: "date", name: "expiresAt", placeholder: "Data de expiração" }
];

export default function Criar() {
    const navigate = useNavigate();
    const [formData, setFormData] = useState({
        title: '',
        category: '',
        expiresAt: ''
    });
    const [options, setOptions] = useState(['', '']);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const { user } = useContext(AuthContext);

    if (!user) {
        return (
            <div className="min-h-[60vh] flex items-center justify-center p-4">
                <div className="max-w-md w-full bg-white p-8 rounded-2xl shadow-lg border border-gray-100 text-center flex flex-col items-center gap-5">
                    <div>
                        <p className="text-sm text-gray-500 mt-1">Por favor, faça login para criar uma enquete.</p>
                    </div>
                    <Link to="/auth" className="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2.5 px-4 rounded-lg shadow-sm transition-colors text-sm text-center inline-block">
                        Fazer Login
                    </Link>
                </div>
            </div>
        );
    }

    const handleInputChange = (e) => {
        const { name, value } = e.target;
        setFormData((prev) => ({ ...prev, [name]: value }));
    };

    const handleOptionChange = (index, value) => {
        const newOptions = [...options];
        newOptions[index] = value;
        setOptions(newOptions);
    };

    const addOption = () => {
        if (options.length < 8) {
            setOptions([...options, '']);
        }
    };
    const removeOption = (index) => {
        if (options.length > 2) {
            setOptions(options.filter((_, i) => i !== index));
        }
    };

    // Enviar formulário para o backend
    const handleSubmit = async (e) => {
        e.preventDefault();
        setError('');

        // Validação local de opções vazias
        const filteredOptions = options.map((opt) => opt.trim()).filter((opt) => opt !== '');
        if (filteredOptions.length < 2) {
            setError('Preencha pelo menos 2 opções válidas.');
            return;
        }

        setLoading(true);

        try {
            await api.post('/enquetes', {
                title: formData.title,
                category: formData.category || 'Geral',
                expires_at: formData.expiresAt || null,
                options: filteredOptions
            });

            navigate('/');
        } catch (err) {
            setError(err.response?.data?.error || 'Erro ao criar enquete.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="max-w-2xl mx-auto p-6 space-y-6">
            <div>
                <h1 className="text-3xl font-bold tracking-tight">Nova enquete</h1>
                <p className="text-muted-foreground mt-1">Mínimo de 2 opções e máximo de 8 opções.</p>
            </div>

            {error && ( <div className="p-3 text-sm bg-red-50 text-red-600 border border-red-200 rounded-lg">{error}</div>) }

            <form onSubmit={handleSubmit} className="space-y-6">
                <div className="space-y-4">
                    {inputsSettings.map((i) => (
                        <div key={i.name} className="flex flex-col gap-1.5">
                            <label htmlFor={i.name} className="text-sm font-medium">{i.title}</label>
                            <input
                                id={i.name}
                                name={i.name}
                                type={i.type}
                                placeholder={i.placeholder}
                                value={formData[i.name]}
                                onChange={handleInputChange}
                                required={i.name}
                                className="w-full px-3 py-2 border rounded-lg text-sm bg-background focus:outline-none focus:ring-2 focus:ring-primary/50"
                            />
                        </div>
                    ))}
                </div>

                <div className="space-y-3 pt-2">
                    <label className="text-sm font-medium block">Opções da Enquete</label>
                    {options.map((option, index) => (
                        <div key={index} className="flex gap-2 items-center">
                            <input
                                type="text"
                                placeholder={`Opção ${index + 1}`}
                                value={option}
                                onChange={(e) => handleOptionChange(index, e.target.value)}
                                required
                                className="flex-1 px-3 py-2 border rounded-lg text-sm bg-background focus:outline-none focus:ring-2 focus:ring-primary/50"
                            />
                            {options.length > 2 && (
                                <button
                                    type="button"
                                    onClick={() => removeOption(index)}
                                    className="p-2 text-muted-foreground hover:text-red-500 transition-colors"
                                    title="Remover opção"
                                >
                                    <Trash2 className="size-4" />
                                </button>
                            )}
                        </div>
                    ))}

                    {options.length < 8 && (
                        <button
                            type="button"
                            onClick={addOption}
                            className="flex items-center gap-1.5 text-sm font-medium text-primary hover:underline mt-2"
                        >
                            <Plus className="size-4" />
                            Adicionar opção ({options.length}/8)
                        </button>
                    )}
                </div>

                <button
                    type="submit"
                    disabled={loading}
                    className="w-full bg-primary text-primary-foreground font-medium py-2.5 rounded-lg hover:opacity-90 transition-opacity disabled:opacity-50"
                >
                    {loading ? 'Criando enquete...' : 'Criar enquete'}
                </button>
            </form>
        </div>
    );
}