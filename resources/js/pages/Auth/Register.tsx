import { Head, Link, useForm } from '@inertiajs/react';
import { AuthCard } from '../../components/admin/AuthCard';
import { AuthSocial } from '../../components/admin/AuthSocial';
import { fieldMessageId, FormField } from '../../components/admin/FormField';
import { PasswordInput } from '../../components/admin/PasswordInput';
import { Button } from '../../components/ui/button';
import { Input } from '../../components/ui/input';
import { useAction } from '../../hooks/useAction';
import { useT } from '../../lib/i18n';

type Field = 'name' | 'username' | 'email';

/** Self-registration (only while Pengaturan → Keamanan → registrasi is on); the account waits for an admin. */
export default function Register(
    props: Yuan.AdminCore.Data.Auth.RegisterPageData,
) {
    const t = useT();
    const sending = useAction();
    const form = useForm({
        name: '',
        username: '',
        email: '',
        password: '',
        password_confirmation: '',
    });
    const submit = () =>
        sending.run((done) =>
            form.post(props.submit_href, {
                onFinish: () => {
                    done();
                    form.reset('password', 'password_confirmation');
                },
            }),
        );
    const text = (name: Field, label: string, autoComplete: string) => (
        <FormField name={name} label={label} required error={form.errors[name]}>
            <Input
                id={name}
                name={name}
                type={name === 'email' ? 'email' : 'text'}
                autoComplete={autoComplete}
                required
                disabled={form.processing}
                value={form.data[name]}
                onChange={(event) => form.setData(name, event.target.value)}
                aria-invalid={form.errors[name] ? true : undefined}
                aria-describedby={fieldMessageId(name)}
            />
        </FormField>
    );

    return (
        <>
            <Head title={t('auth.register_title')} />
            <AuthCard
                title={t('auth.register_title')}
                description={t('auth.register_desc')}
                onSubmit={submit}
            >
                <AuthSocial providers={props.social} />
                {text('name', t('auth.full_name'), 'name')}
                <div className="grid gap-5 sm:grid-cols-2">
                    {text('username', t('auth.username_only'), 'username')}
                    {text('email', t('auth.email'), 'email')}
                </div>
                <FormField
                    name="password"
                    label={t('auth.password')}
                    required
                    help={props.password_hint}
                    error={form.errors.password}
                >
                    <PasswordInput
                        id="password"
                        name="password"
                        autoComplete="new-password"
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
                <FormField
                    name="password_confirmation"
                    label={t('auth.confirm_password')}
                    required
                    error={form.errors.password_confirmation}
                >
                    <PasswordInput
                        id="password_confirmation"
                        name="password_confirmation"
                        autoComplete="new-password"
                        required
                        disabled={form.processing}
                        value={form.data.password_confirmation}
                        onChange={(event) =>
                            form.setData(
                                'password_confirmation',
                                event.target.value,
                            )
                        }
                        aria-describedby={fieldMessageId(
                            'password_confirmation',
                        )}
                    />
                </FormField>
                <Button
                    type="submit"
                    size="auth"
                    loading={form.processing}
                    className="w-full font-semibold"
                >
                    {t(
                        form.processing
                            ? 'auth.processing'
                            : 'auth.register_submit',
                    )}
                </Button>
            </AuthCard>
            <p className="m-0 text-pretty text-muted-foreground">
                {t('auth.have_account')}{' '}
                <Link
                    href={props.login_href}
                    className="font-medium text-foreground"
                >
                    {t('auth.login_submit')}
                </Link>
            </p>
        </>
    );
}
