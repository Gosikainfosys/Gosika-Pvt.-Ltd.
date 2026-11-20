import React from 'react';

export default function PasswordRequirements({ password }) {
    if (!password) {
        return null;
    }

    const rules = [
        { label: 'At least 8 characters', matches: Array.from(password).length >= 8 },
        { label: 'At least one uppercase letter', matches: /[A-Z]/.test(password) },
        { label: 'Contains a number', matches: /\p{N}/u.test(password) },
        { label: 'Contains a symbol', matches: /[\p{P}\p{S}\p{Z}]/u.test(password) },
    ];

    return (
        <ul aria-label="Password requirements" className="space-y-1">
            {rules.map((rule) => (
                <li key={rule.label} className="flex items-center gap-1.5">
                    <span className={`h-1.5 w-1.5 rounded-full ${rule.matches ? 'bg-emerald-500' : 'bg-gray-300'}`} />
                    <span className={`text-[10px] ${rule.matches ? 'text-emerald-600' : 'text-gray-400'}`}>
                        {rule.label}
                    </span>
                </li>
            ))}
        </ul>
    );
}
