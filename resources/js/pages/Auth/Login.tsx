import { Head, Link, useForm } from '@inertiajs/react';
import { CircleAlert, CircleCheck } from 'lucide-react';
import { AuthCard } from '../../components/admin/AuthCard';
import { fieldMessageId, FormField } from '../../components/admin/FormField';
import { PasswordInput } from '../../components/admin/PasswordInput';
import { Alert, AlertDescription } from '../../components/ui/alert';
import { Button } from '../../components/ui/button';
import { Checkbox } from '../../components/ui/checkbox';
import { Input } from '../../components/ui/input';
import { Label } from '../../components/ui/label';
import { Spinner } from '../../components/ui/spinner';
import { useT } from '../../lib/i18n';

export default function Login(props: Yuan.AdminCore.Data.Auth.LoginPageData) {
    const t = useT();
    const form = useForm({ username: '', password: '', remember: false });
    // A message on a filled-in username is a sign-in failure (wrong credentials,
    // inactive account, too many attempts): shown above the form, not inline.
    const signInError =
        form.data.username !== '' ? form.errors.username : undefined;
    const submit = () =>
        form.post(props.submit_href, {
            onFinish: () => form.reset('password'),
        });

    return (
        <>
            <Head title={t('auth.login_title')} />
            <AuthCard
                title={t('auth.login_title')}
                description={t('auth.login_desc')}
                onSubmit={submit}
            >
                {props.status && (
                    <Alert variant="success">
                        <CircleCheck />
                        <AlertDescription>{props.status}</AlertDescription>
                    </Alert>
                )}
                {signInError && (
                    <Alert variant="destructive">
                        <CircleAlert />
                        <AlertDescription>{signInError}</AlertDescription>
                    </Alert>
                )}
                <FormField
                    name="username"
                    label={t('auth.username')}
                    required
                    error={signInError ? undefined : form.errors.username}
                >
                    <Input
                        id="username"
                        name="username"
                        autoComplete="username"
                        autoFocus
                        required
                        disabled={form.processing}
                        value={form.data.username}
                        onChange={(event) =>
                            form.setData('username', event.target.value)
                        }
                        aria-invalid={form.errors.username ? true : undefined}
                        aria-describedby={fieldMessageId('username')}
                    />
                </FormField>
                <FormField
                    name="password"
                    label={t('auth.password')}
                    required
                    error={form.errors.password}
                >
                    <PasswordInput
                        id="password"
                        name="password"
                        autoComplete="current-password"
                        required
                        disabled={form.processing}
                        value={form.data.password}
                        onChange={(event) =>
                            form.setData('password', event.target.value)
                        }
                        aria-invalid={form.errors.password ? true : undefined}
                        aria-describedby={fieldMessageId('password')}
                    />
                </FormField>
                <div className="flex items-center justify-between gap-3">
                    <Label className="flex cursor-pointer items-center gap-2 font-normal">
                        <Checkbox
                            name="remember"
                            checked={form.data.remember}
                            onCheckedChange={(checked) =>
                                form.setData('remember', checked === true)
                            }
                        />
                        {t('auth.remember')}
                    </Label>
                    {props.forgot_href && (
                        <Link href={props.forgot_href} className="font-medium">
                            {t('auth.forgot_link')}
                        </Link>
                    )}
                </div>
                <Button
                    type="submit"
                    size="auth"
                    disabled={form.processing}
                    aria-busy={form.processing}
                    className="w-full font-semibold"
                >
                    {form.processing && <Spinner />}
                    {t(
                        form.processing
                            ? 'auth.processing'
                            : 'auth.login_submit',
                    )}
                </Button>
            </AuthCard>
            <p className="m-0 text-table text-pretty text-muted-foreground">
                {t('auth.login_note')}
            </p>
        </>
    );
}
