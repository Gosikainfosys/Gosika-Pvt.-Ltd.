import React from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, Mail, ShieldCheck } from 'lucide-react';

export default function ForgotPassword() {
    const { company, flash } = usePage().props;
    const form = useForm({ email: '' });

    const handleSubmit = (event) => {
        event.preventDefault();
        form.post('/forgot-password');
    };

    return (
        <div className="min-h-screen bg-[#F7F8FA] flex flex-col justify-center px-4 py-8 sm:px-6 sm:py-12">
            <Head title="Forgot Password" />
            <div className="w-full max-w-md mx-auto">
                <div className="text-center mb-8">
                    <img
                        src={company?.logo_url || '/gosika-icon.svg'}
                        alt={company?.name || 'GOSIKA'}
                        className="h-14 max-w-48 object-contain mx-auto mb-4"
                    />
                    <h1 className="text-2xl font-black text-[#202124]">Reset your password</h1>
                    <p className="mt-2 text-sm text-gray-500">We’ll email a secure reset link if the address belongs to an administrator account.</p>
                </div>

                <div className="bg-white rounded-2xl border border-[#E5E7EB] shadow-xl p-6 sm:p-8">
                    <div className="flex items-center gap-3 mb-6">
                        <div className="h-10 w-10 rounded-xl bg-[#FFF0E8] text-[#FF5500] flex items-center justify-center">
                            <ShieldCheck className="h-5 w-5" />
                        </div>
                        <div>
                            <h2 className="text-sm font-bold text-gray-900">Account recovery</h2>
                            <p className="text-xs text-gray-500">Enter your administrator email address</p>
                        </div>
                    </div>

                    {flash?.status && (
                        <div role="status" className="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-xs text-emerald-800">
                            {flash.status}
                        </div>
                    )}

                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div>
                            <label htmlFor="email" className="block text-xs font-bold text-gray-700 mb-1.5">Email address</label>
                            <div className="relative">
                                <Mail className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                                <input
                                    id="email"
                                    type="email"
                                    autoComplete="email"
                                    autoFocus
                                    required
                                    value={form.data.email}
                                    onChange={(event) => form.setData('email', event.target.value)}
                                    className="w-full rounded-lg border border-gray-300 py-2.5 pl-10 pr-3 text-sm focus:border-[#FF5500] focus:outline-none focus:ring-2 focus:ring-[#FF5500]/20"
                                />
                            </div>
                            {form.errors.email && <p className="mt-1 text-xs text-red-600">{form.errors.email}</p>}
                        </div>

                        <button
                            type="submit"
                            disabled={form.processing}
                            className="w-full rounded-lg bg-[#FF5500] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#E04B00] disabled:opacity-60"
                        >
                            {form.processing ? 'Sending...' : 'Send reset link'}
                        </button>
                    </form>

                    <Link href="/login" className="mt-5 inline-flex items-center gap-2 text-xs font-semibold text-gray-600 hover:text-[#FF5500]">
                        <ArrowLeft className="h-4 w-4" /> Back to sign in
                    </Link>
                </div>
            </div>
        </div>
    );
}
