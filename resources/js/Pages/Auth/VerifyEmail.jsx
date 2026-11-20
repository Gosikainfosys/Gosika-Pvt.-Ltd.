import React from 'react';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { MailCheck, ShieldCheck } from 'lucide-react';

export default function VerifyEmail() {
    const { auth, flash } = usePage().props;
    const form = useForm({});

    const resend = (event) => {
        event.preventDefault();
        form.post('/email/verification-notification');
    };

    return (
        <div className="min-h-screen bg-[#F7F8FA] flex flex-col justify-center px-4 py-8 sm:px-6 sm:py-12">
            <Head title="Verify Email Address" />
            <div className="w-full max-w-md mx-auto rounded-2xl border border-[#E5E7EB] bg-white p-6 shadow-xl sm:p-8">
                <div className="mb-5 flex h-12 w-12 items-center justify-center rounded-xl bg-[#FFF0E8] text-[#FF5500]">
                    <MailCheck className="h-6 w-6" />
                </div>
                <h1 className="text-xl font-black text-[#202124]">Verify your email address</h1>
                <p className="mt-2 text-sm leading-6 text-gray-600">
                    Send a verification link to <strong className="break-all">{auth?.user?.email}</strong>. You can continue using the app while verification is optional.
                </p>

                {flash?.status && (
                    <div role="status" className="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-xs text-emerald-800">
                        {flash.status}
                    </div>
                )}

                <form onSubmit={resend} className="mt-6">
                    <button
                        type="submit"
                        disabled={form.processing}
                        className="w-full rounded-lg bg-[#FF5500] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#E04B00] disabled:opacity-60"
                    >
                        {form.processing ? 'Sending...' : 'Send verification email'}
                    </button>
                </form>

                <div className="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-4">
                    <span className="inline-flex items-center gap-1.5 text-xs text-gray-500">
                        <ShieldCheck className="h-4 w-4 text-[#FF5500]" /> Administrator account
                    </span>
                    <button
                        type="button"
                        onClick={() => router.post('/logout')}
                        className="text-xs font-semibold text-gray-600 hover:text-[#FF5500]"
                    >
                        Sign out
                    </button>
                </div>
            </div>
        </div>
    );
}
