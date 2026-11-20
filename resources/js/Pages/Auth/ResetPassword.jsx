import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, LockKeyhole } from 'lucide-react';

export default function ResetPassword({ token, email }) {
    const form = useForm({
        token,
        email,
        password: '',
        password_confirmation: '',
    });

    const handleSubmit = (event) => {
        event.preventDefault();
        form.post('/reset-password');
    };

    return (
        <div className="min-h-screen bg-[#F7F8FA] flex flex-col justify-center px-4 py-8 sm:px-6 sm:py-12">
            <Head title="Choose a New Password" />
            <div className="w-full max-w-md mx-auto">
                <div className="text-center mb-8">
                    <img src="/gosika-icon.svg" alt="GOSIKA" className="h-14 w-14 mx-auto mb-4" />
                    <h1 className="text-2xl font-black text-[#202124]">Choose a new password</h1>
                    <p className="mt-2 text-sm text-gray-500">Use at least 12 characters with uppercase, lowercase, a number, and a symbol.</p>
                </div>

                <div className="bg-white rounded-2xl border border-[#E5E7EB] shadow-xl p-6 sm:p-8">
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div>
                            <label htmlFor="email" className="block text-xs font-bold text-gray-700 mb-1.5">Email address</label>
                            <input
                                id="email"
                                type="email"
                                autoComplete="email"
                                required
                                value={form.data.email}
                                onChange={(event) => form.setData('email', event.target.value)}
                                className="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#FF5500] focus:outline-none focus:ring-2 focus:ring-[#FF5500]/20"
                            />
                            {form.errors.email && <p className="mt-1 text-xs text-red-600">{form.errors.email}</p>}
                        </div>

                        <div>
                            <label htmlFor="password" className="block text-xs font-bold text-gray-700 mb-1.5">New password</label>
                            <div className="relative">
                                <LockKeyhole className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                                <input
                                    id="password"
                                    type="password"
                                    autoComplete="new-password"
                                    required
                                    value={form.data.password}
                                    onChange={(event) => form.setData('password', event.target.value)}
                                    className="w-full rounded-lg border border-gray-300 py-2.5 pl-10 pr-3 text-sm focus:border-[#FF5500] focus:outline-none focus:ring-2 focus:ring-[#FF5500]/20"
                                />
                            </div>
                            {form.errors.password && <p className="mt-1 text-xs text-red-600">{form.errors.password}</p>}
                        </div>

                        <div>
                            <label htmlFor="password_confirmation" className="block text-xs font-bold text-gray-700 mb-1.5">Confirm new password</label>
                            <input
                                id="password_confirmation"
                                type="password"
                                autoComplete="new-password"
                                required
                                value={form.data.password_confirmation}
                                onChange={(event) => form.setData('password_confirmation', event.target.value)}
                                className="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#FF5500] focus:outline-none focus:ring-2 focus:ring-[#FF5500]/20"
                            />
                        </div>

                        <button
                            type="submit"
                            disabled={form.processing}
                            className="w-full rounded-lg bg-[#FF5500] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#E04B00] disabled:opacity-60"
                        >
                            {form.processing ? 'Updating...' : 'Reset password'}
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
