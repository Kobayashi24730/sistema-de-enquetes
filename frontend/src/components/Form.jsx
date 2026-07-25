import { useState } from "react";
import { Plus, Trash2 } from "lucide-react";
import { CATEGORIES, type Poll } from "@/lib/types";

export type PollFormValues = {
    title: string;
    description?: string;
    category: string;
    expiresAt?: string;
    options: string[];
};

const MAX_OPTIONS = 8;

/** Formulário compartilhado entre criação e edição de enquetes. */
export function PollForm({
                             poll,
                             submitLabel,
                             onSubmit,
                         }: {
    poll?: Poll;
    submitLabel: string;
    onSubmit: (values: PollFormValues) => void;
}) {
    const [title, setTitle] = useState(poll?.title ?? "");
    const [description, setDescription] = useState(poll?.description ?? "");
    const [category, setCategory] = useState<string>(poll?.category ?? CATEGORIES[0]);
    const [expiresAt, setExpiresAt] = useState(poll?.expiresAt ? poll.expiresAt.slice(0, 10) : "");
    const [options, setOptions] = useState<string[]>(
        poll ? poll.options.map((option) => option.text) : ["", ""],
    );
    const [error, setError] = useState<string | null>(null);

    function updateOption(index: number, value: string) {
        setOptions((current) => current.map((item, i) => (i === index ? value : item)));
    }

    function handleSubmit(event: React.FormEvent) {
        event.preventDefault();
        const cleaned = options.map((option) => option.trim()).filter(Boolean);

        if (title.trim().length < 5) {
            setError("O título precisa ter ao menos 5 caracteres.");
            return;
        }
        if (cleaned.length < 2) {
            setError("Informe pelo menos 2 opções de resposta.");
            return;
        }
        if (new Set(cleaned.map((o) => o.toLowerCase())).size !== cleaned.length) {
            setError("As opções não podem se repetir.");
            return;
        }

        setError(null);
        onSubmit({
            title,
            description,
            category,
            expiresAt: expiresAt ? new Date(`${expiresAt}T23:59:59`).toISOString() : undefined,
            options: cleaned,
        });
    }

    return (
        <form onSubmit={handleSubmit} className="panel mt-6 space-y-5 p-6">
            <div>
                <label htmlFor="title" className="mb-1.5 block text-sm font-medium">
                    Título
                </label>
                <input
                    id="title"
                    value={title}
                    onChange={(event) => setTitle(event.target.value)}
                    className="field focus:field-focus"
                    placeholder="Qual pergunta você quer fazer?"
                />
            </div>

            <div>
                <label htmlFor="description" className="mb-1.5 block text-sm font-medium">
                    Descrição <span className="text-muted-foreground">(opcional)</span>
                </label>
                <textarea
                    id="description"
                    value={description}
                    onChange={(event) => setDescription(event.target.value)}
                    rows={3}
                    className="field resize-none focus:field-focus"
                    placeholder="Dê mais contexto para quem for votar"
                />
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div>
                    <label htmlFor="category" className="mb-1.5 block text-sm font-medium">
                        Categoria
                    </label>
                    <select
                        id="category"
                        value={category}
                        onChange={(event) => setCategory(event.target.value)}
                        className="field focus:field-focus"
                    >
                        {CATEGORIES.map((item) => (
                            <option key={item} value={item}>
                                {item}
                            </option>
                        ))}
                    </select>
                </div>
                <div>
                    <label htmlFor="expires" className="mb-1.5 block text-sm font-medium">
                        Expira em <span className="text-muted-foreground">(opcional)</span>
                    </label>
                    <input
                        id="expires"
                        type="date"
                        value={expiresAt}
                        onChange={(event) => setExpiresAt(event.target.value)}
                        className="field focus:field-focus"
                    />
                </div>
            </div>

            <div>
        <span className="mb-1.5 block text-sm font-medium">
          Opções de resposta{" "}
            <span className="text-muted-foreground">({options.length}/{MAX_OPTIONS})</span>
        </span>
                <div className="space-y-2">
                    {options.map((option, index) => (
                        <div key={index} className="flex gap-2">
                            <input
                                value={option}
                                onChange={(event) => updateOption(index, event.target.value)}
                                aria-label={`Opção ${index + 1}`}
                                placeholder={`Opção ${index + 1}`}
                                className="field focus:field-focus"
                            />
                            {options.length > 2 && (
                                <button
                                    type="button"
                                    aria-label={`Remover opção ${index + 1}`}
                                    onClick={() => setOptions((current) => current.filter((_, i) => i !== index))}
                                    className="rounded-md border border-input px-3 text-muted-foreground transition-colors hover:border-destructive hover:text-destructive"
                                >
                                    <Trash2 className="size-4" />
                                </button>
                            )}
                        </div>
                    ))}
                </div>
                {options.length < MAX_OPTIONS && (
                    <button
                        type="button"
                        onClick={() => setOptions((current) => [...current, ""])}
                        className="mt-3 inline-flex items-center gap-1.5 rounded-md border border-input px-3 py-2 text-sm text-muted-foreground transition-colors hover:text-foreground"
                    >
                        <Plus className="size-4" /> Adicionar opção
                    </button>
                )}
            </div>

            {error && <p className="text-sm text-destructive">{error}</p>}

            <button
                type="submit"
                className="w-full rounded-md bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition-opacity hover:opacity-90"
            >
                {submitLabel}
            </button>
        </form>
    );
}
