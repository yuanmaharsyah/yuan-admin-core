import { Eye, EyeOff } from 'lucide-react';
import { useState } from 'react';
import type { ComponentProps } from 'react';
import { useT } from '../../lib/i18n';
import { cn } from '../../lib/utils';
import { Input } from '../ui/input';

/** A password field with a show / hide button. */
export function PasswordInput({
    className,
    ...props
}: Omit<ComponentProps<'input'>, 'type'>) {
    const t = useT();
    const [visible, setVisible] = useState(false);

    return (
        <div className="relative flex">
            <Input
                type={visible ? 'text' : 'password'}
                className={cn('pr-10', className)}
                {...props}
            />
            <button
                type="button"
                aria-label={t(
                    visible ? 'auth.hide_password' : 'auth.show_password',
                )}
                aria-pressed={visible}
                onClick={() => setVisible((shown) => !shown)}
                className="absolute inset-y-0.5 right-0.5 flex size-control-sm items-center justify-center rounded text-muted-foreground hover:text-foreground"
            >
                {visible ? (
                    <EyeOff className="size-4" />
                ) : (
                    <Eye className="size-4" />
                )}
            </button>
        </div>
    );
}
