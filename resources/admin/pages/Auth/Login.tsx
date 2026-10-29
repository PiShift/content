import { Head, useForm } from '@inertiajs/react';
import { useEffect, type FormEvent } from 'react';
import { BrandMark } from '../../components/ui/BrandMark';
import { Button } from '../../components/ui/Button';
import { FieldWrapper, TextInput } from '../../components/ui/Form';
import { cn, LOCALE_NAMES, useShared, useT } from '../../lib/utils';

export default function Login({ loginUrl }: { loginUrl: string }) {
    const t = useT();
    const { admin } = useShared();
    const form = useForm({ email: '', password: '', remember: false });

    useEffect(() => {
        document.documentElement.lang = admin.locale;
        document.documentElement.dir = admin.dir;
    }, [admin.locale, admin.dir]);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(loginUrl, { onFinish: () => form.reset('password') });
    };

    return (
        <div className="grid min-h-dvh place-items-center px-4 py-10">
            <Head title={t('login')} />

            <div className="w-full max-w-sm">
                <div className="mb-6 flex flex-col items-center gap-3 text-center">
                    <BrandMark size="lg" />
                    <div>
                        <p className="text-sm font-semibold text-ink-soft">{admin.name}</p>
                        <h1 className="mt-1 text-xl font-semibold text-balance">{t('login_title')}</h1>
                    </div>
                </div>

                <form onSubmit={submit} className="flex flex-col gap-4 rounded-panel border border-line bg-panel p-6 shadow-panel">
                    <FieldWrapper id="email" label={t('email')} error={form.errors.email}>
                        <TextInput
                            id="email"
                            type="email"
                            dir="ltr"
                            autoComplete="username"
                            autoFocus
                            value={form.data.email}
                            invalid={Boolean(form.errors.email)}
                            onChange={(event) => form.setData('email', event.target.value)}
                        />
                    </FieldWrapper>
                    <FieldWrapper id="password" label={t('password')} error={form.errors.password}>
                        <TextInput
                            id="password"
                            type="password"
                            dir="ltr"
                            autoComplete="current-password"
                            value={form.data.password}
                            invalid={Boolean(form.errors.password)}
                            onChange={(event) => form.setData('password', event.target.value)}
                        />
                    </FieldWrapper>
                    <label className="inline-flex items-center gap-2 text-sm text-ink-soft">
                        <input
                            type="checkbox"
                            className="size-4 accent-[var(--color-accent)]"
                            checked={form.data.remember}
                            onChange={(event) => form.setData('remember', event.target.checked)}
                        />
                        {t('remember')}
                    </label>
                    <Button type="submit" variant="primary" loading={form.processing} className="mt-1 w-full">
                        {t('login')}
                    </Button>
                </form>

                <div className="mt-5 flex justify-center gap-1">
                    {admin.locales.map((locale) => (
                        <a
                            key={locale}
                            href={admin.localeUrl.replace('__LOCALE__', locale)}
                            className={cn('rounded-[6px] px-2.5 py-1 text-[13px]', locale === admin.locale ? 'bg-sunken font-semibold' : 'text-ink-faint hover:text-ink')}
                        >
                            {LOCALE_NAMES[locale] ?? locale}
                        </a>
                    ))}
                </div>
            </div>
        </div>
    );
}
