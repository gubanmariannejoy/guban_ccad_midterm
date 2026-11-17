import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowRight, ReceiptText } from 'lucide-react';

interface Sale {
    id: number;
    total_amount: string;
    status: string;
    dispatch_date: string | null;
    sale_items_count: number;
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];

export default function BuyerDashboard({ sales }: { sales: Sale[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="User dashboard" />
            <main className="flex flex-1 flex-col gap-6 p-4 md:p-8">
                <header>
                    <p className="text-muted-foreground text-sm font-medium">Your account</p>
                    <h1 className="mt-1 text-2xl font-semibold">User dashboard</h1>
                    <p className="text-muted-foreground mt-2">Check the latest updates on your nursery sales.</p>
                </header>

                <section aria-labelledby="recent-sales-heading" className="bg-card rounded-xl border">
                    <div className="flex items-center justify-between gap-4 border-b p-5">
                        <div>
                            <h2 id="recent-sales-heading" className="font-semibold">
                                Recent sales
                            </h2>
                            <p className="text-muted-foreground mt-1 text-sm">Your 10 most recent sales.</p>
                        </div>
                        <Link href="/nursery-sales" className="text-primary inline-flex items-center gap-2 text-sm font-medium hover:underline">
                            All sales <ArrowRight className="size-4" aria-hidden="true" />
                        </Link>
                    </div>

                    {sales.length > 0 ? (
                        <ul className="divide-y">
                            {sales.map((sale) => (
                                <li key={sale.id} className="flex flex-wrap items-center justify-between gap-3 p-5">
                                    <div className="flex items-center gap-3">
                                        <span className="bg-muted flex size-10 items-center justify-center rounded-full">
                                            <ReceiptText className="size-5" aria-hidden="true" />
                                        </span>
                                        <div>
                                            <p className="font-medium">Sale #{sale.id}</p>
                                            <p className="text-muted-foreground text-sm">
                                                {sale.sale_items_count} {sale.sale_items_count === 1 ? 'item' : 'items'}
                                                {sale.dispatch_date
                                                    ? ` · Dispatch ${new Date(`${sale.dispatch_date}T00:00:00`).toLocaleDateString()}`
                                                    : ' · Dispatch not scheduled'}
                                            </p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <span className="text-sm font-medium">{Number(sale.total_amount).toFixed(2)}</span>
                                        <span className="bg-muted rounded-full px-3 py-1 text-xs capitalize">{sale.status}</span>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <div className="text-muted-foreground px-5 py-12 text-center">
                            <ReceiptText className="mx-auto mb-3 size-8" aria-hidden="true" />
                            <p>You don’t have any sales yet.</p>
                            <p className="mt-1 text-sm">Your sales will appear here when they are recorded.</p>
                        </div>
                    )}
                </section>
            </main>
        </AppLayout>
    );
}
