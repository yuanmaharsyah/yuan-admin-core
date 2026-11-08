import type { FormEvent, ReactNode } from 'react';

type Props = {
    title: string;
    description?: string;
    onSubmit?: () => void;
    children: ReactNode;
};

/** The auth form: title (24px/600) and muted description, then the fields; it sits in AuthLayout's card. */
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
            className="flex flex-col gap-5"
        >
            <div className="flex flex-col gap-1">
                <h1 id="auth-card-title" className="m-0 text-title">
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
