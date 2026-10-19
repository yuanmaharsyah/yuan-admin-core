import type { FormEvent, ReactNode } from 'react';

type Props = {
    title: string;
    description?: string;
    onSubmit?: () => void;
    children: ReactNode;
};

/** The auth card: title over the 2px ink rule, optional description, then the form body. */
export function AuthCard({ title, description, onSubmit, children }: Props) {
    const submit = (event: FormEvent) => {
        event.preventDefault();
        onSubmit?.();
    };

    return (
        <form
            noValidate
            aria-labelledby="auth-card-title"
            onSubmit={submit}
            className="flex flex-col gap-4 rounded bg-card p-6 text-card-foreground"
        >
            <div className="flex flex-col gap-2">
                <h1
                    id="auth-card-title"
                    className="m-0 border-b-2 border-rule pb-2.5 text-card-title"
                >
                    {title}
                </h1>
                {description && (
                    <p className="m-0 text-pretty text-muted-foreground">
                        {description}
                    </p>
                )}
            </div>
            {children}
        </form>
    );
}
