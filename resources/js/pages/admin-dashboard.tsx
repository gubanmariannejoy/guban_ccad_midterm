import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Boxes, Clock3, ReceiptText, Sprout, UsersRound, Wallet } from 'lucide-react';

interface AdminDashboardProps {
    stats: {
        plants: number;
        buyers: number;
        sales: number;
        pendingSales: number;
        availableStock: number;
        confirmedRevenue: number;
    };
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];

export default function AdminDashboard({ stats }: AdminDashboardProps) {
    const metrics = [
        { label: 'Plant species', value: stats.plants, href: '/plant-species', icon: Sprout },
        { label: 'Buyers', value: stats.buyers, href: '/buyers', icon: UsersRound },
        { label: 'Total sales', value: stats.sales, href: '/nursery-sales', icon: ReceiptText },
        { label: 'Pending sales', value: stats.pendingSales, href: '/nursery-sales', icon: Clock3 },
        { label: 'Available stock', value: stats.availableStock, href: '/plant-inventory', icon: Boxes },
        {
            label: 'Confirmed revenue',
            value: `₱${Number(stats.confirmedRevenue).toFixed(2)}`,
            href: '/nursery-sales',
            icon: Wallet,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Admin dashboard" />
            <main className="flex flex-1 flex-col gap-6 p-4 md:p-8">
                <header>
                    <p className="text-muted-foreground text-sm font-medium">Administration</p>
                    <h1 className="mt-1 text-2xl font-semibold">Admin dashboard</h1>
                    <p className="text-muted-foreground mt-2">Manage your nursery catalogue and review customer sales.</p>
                </header>

                <section aria-label="Nursery overview" className="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                    {metrics.map(({ label, value, href, icon: Icon }) => (
                        <Link
                            key={label}
                            href={href}
                            className="bg-card hover:bg-accent/50 focus-visible:ring-ring rounded-xl border p-5 transition-colors focus-visible:ring-2 focus-visible:outline-none"
                        >
                            <div className="text-muted-foreground flex items-center justify-between text-sm">
                                <span>{label}</span>
                                <Icon className="size-4" aria-hidden="true" />
                            </div>
                            <p className="mt-3 text-3xl font-semibold">{value}</p>
                        </Link>
                    ))}
                </section>

                <section className="grid gap-4 md:grid-cols-2">
                    <Link href="/plant-species" className="bg-card hover:bg-accent/50 rounded-xl border p-5 transition-colors">
                        <h2 className="font-semibold">Manage plant species</h2>
                        <p className="text-muted-foreground mt-1 text-sm">Add plants, update prices, and check available stock.</p>
                    </Link>
                    <Link href="/nursery-sales" className="bg-card hover:bg-accent/50 rounded-xl border p-5 transition-colors">
                        <h2 className="font-semibold">Review nursery sales</h2>
                        <p className="text-muted-foreground mt-1 text-sm">View orders, confirm pending sales, and manage dispatch details.</p>
                    </Link>
                </section>
            </main>
        </AppLayout>
    );
}
