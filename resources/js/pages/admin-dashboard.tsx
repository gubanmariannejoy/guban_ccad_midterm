import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowRight, Boxes, Clock3, ReceiptText, Sprout, UsersRound, Wallet } from 'lucide-react';

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
            <main className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-8 p-4 md:p-8">
                <header className="flex flex-wrap items-end justify-between gap-5">
                    <div>
                        <p className="text-primary text-xs font-semibold tracking-[0.18em] uppercase">Nursery operations</p>
                        <h1 className="mt-2 font-serif text-3xl tracking-tight sm:text-4xl">A healthier nursery, at a glance.</h1>
                        <p className="text-muted-foreground mt-2 max-w-2xl">
                            Keep your botanical collection, inventory, and customer orders growing smoothly.
                        </p>
                    </div>
                    <Link
                        href="/plant-species/create"
                        className="bg-primary text-primary-foreground hover:bg-primary/90 inline-flex items-center gap-2 rounded-full px-5 py-3 text-sm font-semibold transition-colors"
                    >
                        <Sprout className="size-4" aria-hidden="true" /> Add a plant
                    </Link>
                </header>

                <section aria-label="Nursery overview" className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-6">
                    {metrics.map(({ label, value, href, icon: Icon }) => (
                        <Link
                            key={label}
                            href={href}
                            className="bg-card hover:border-primary/30 focus-visible:ring-ring group rounded-2xl border p-5 shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md focus-visible:ring-2 focus-visible:outline-none"
                        >
                            <div className="flex items-center justify-between gap-3">
                                <span className="text-muted-foreground text-sm">{label}</span>
                                <span className="bg-secondary text-primary flex size-9 items-center justify-center rounded-xl">
                                    <Icon className="size-4" aria-hidden="true" />
                                </span>
                            </div>
                            <p className="mt-5 text-3xl font-semibold tracking-tight">{value}</p>
                            <span className="text-primary mt-3 inline-flex items-center gap-1 text-xs font-medium opacity-0 transition-opacity group-hover:opacity-100">
                                View details <ArrowRight className="size-3.5" aria-hidden="true" />
                            </span>
                        </Link>
                    ))}
                </section>

                <section aria-label="Quick actions" className="grid gap-4 lg:grid-cols-2">
                    <Link
                        href="/plant-species"
                        className="bg-card hover:border-primary/30 group flex items-center justify-between gap-5 rounded-2xl border p-6 shadow-sm transition-colors"
                    >
                        <span className="flex items-start gap-4">
                            <span className="bg-secondary text-primary flex size-12 shrink-0 items-center justify-center rounded-2xl">
                                <Sprout className="size-5" aria-hidden="true" />
                            </span>
                            <span>
                                <span className="text-muted-foreground text-xs font-semibold tracking-[0.16em] uppercase">Catalogue</span>
                                <span className="mt-1 block font-serif text-xl">Tend your collection</span>
                                <span className="text-muted-foreground mt-1 block text-sm">
                                    Add plants, update prices, and check available stock.
                                </span>
                            </span>
                        </span>
                        <ArrowRight className="text-primary size-5 shrink-0 transition-transform group-hover:translate-x-1" aria-hidden="true" />
                    </Link>
                    <Link
                        href="/nursery-sales"
                        className="bg-card hover:border-primary/30 group flex items-center justify-between gap-5 rounded-2xl border p-6 shadow-sm transition-colors"
                    >
                        <span className="flex items-start gap-4">
                            <span className="bg-secondary text-primary flex size-12 shrink-0 items-center justify-center rounded-2xl">
                                <ReceiptText className="size-5" aria-hidden="true" />
                            </span>
                            <span>
                                <span className="text-muted-foreground text-xs font-semibold tracking-[0.16em] uppercase">Orders</span>
                                <span className="mt-1 block font-serif text-xl">Keep every order on track</span>
                                <span className="text-muted-foreground mt-1 block text-sm">Confirm sales and manage dispatch details.</span>
                            </span>
                        </span>
                        <ArrowRight className="text-primary size-5 shrink-0 transition-transform group-hover:translate-x-1" aria-hidden="true" />
                    </Link>
                </section>
            </main>
        </AppLayout>
    );
}
