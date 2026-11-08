import { Chrome, Github } from 'lucide-react';
import { useT } from '../../lib/i18n';
import { Button } from '../ui/button';

type Provider = 'google' | 'github';

const ICONS = { google: Chrome, github: Github } as const;

/**
 * Social sign-in buttons over an "atau masuk dengan" divider (login and
 * register). Reserved room, owner's decision (restyle 03): they link to `#`
 * until Socialite is set up; `admin-core.auth.social` lists or hides them.
 */
export function AuthSocial({ providers }: { providers: Provider[] }) {
    const t = useT();

    if (providers.length === 0) {
        return null;
    }

    return (
        <div className="flex flex-col gap-5">
            <div className="grid gap-3 sm:grid-cols-2">
                {providers.map((provider) => {
                    const ProviderIcon = ICONS[provider];

                    return (
                        <Button
                            key={provider}
                            asChild
                            variant="outline"
                            size="auth"
                        >
                            <a href="#">
                                <ProviderIcon />
                                {t(
                                    provider === 'google'
                                        ? 'auth.social_google'
                                        : 'auth.social_github',
                                )}
                            </a>
                        </Button>
                    );
                })}
            </div>
            <div className="flex items-center gap-3 text-table text-muted-foreground">
                <span aria-hidden="true" className="h-px flex-1 bg-border" />
                {t('auth.social_divider')}
                <span aria-hidden="true" className="h-px flex-1 bg-border" />
            </div>
        </div>
    );
}
