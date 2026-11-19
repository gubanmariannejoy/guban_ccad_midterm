import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowRight, Leaf, ReceiptText } from 'lucide-react';

interface Sale {
    id: number;
    total_amount: string;
    status: string;
    dispatch_date: string | null;
    sale_items_count: number;
}

interface Plant {
    id: number;
    plant_name: string;
    botanical_name: string;
    pot_size: string;
    unit_price: string;
    inventory: { quantity_available: number } | null;
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];
const peso = (amount: number | string) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(amount));

export default function BuyerDashboard({
    sales,
    stats,
    featuredPlants,
}: {
    sales: Sale[];
    stats: { pendingOrders: number; completedPurchases: number };
    featuredPlants: Plant[];
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Buyer dashboard" />
            <main className="flex flex-1 flex-col gap-8 p-4 md:p-8">
                <header className="rounded-2xl bg-emerald-50 p-6 md:p-8 dark:bg-emerald-950/30">
                    <p className="text-muted-foreground text-sm font-medium">Green Nursery</p>
                    <h1 className="mt-1 text-3xl font-semibold">Welcome to Green Nursery! 🌱</h1>
                    <p className="text-muted-foreground mt-2">Find your favorite plants and bring nature closer to home.</p>
                    <Button asChild className="mt-5">
                        <Link href="/plants">Browse plants</Link>
                    </Button>
                </header>

                <section aria-label="Purchase summary" className="grid gap-4 sm:grid-cols-2">
                    <div className="rounded-xl border p-5">
                        <p className="text-muted-foreground text-sm">Pending orders</p>
                        <p className="mt-2 text-3xl font-semibold">{stats.pendingOrders}</p>
                    </div>
                    <div className="rounded-xl border p-5">
                        <p className="text-muted-foreground text-sm">Completed purchases</p>
                        <p className="mt-2 text-3xl font-semibold">{stats.completedPurchases}</p>
                    </div>
                </section>

                <section className="space-y-4">
                    <div className="flex items-center justify-between gap-4">
                        <div>
                            <h2 className="text-xl font-semibold">Featured plants</h2>
                            <p className="text-muted-foreground mt-1 text-sm">A few nursery favorites, ready to order.</p>
                        </div>
                        <Link href="/plants" className="text-primary inline-flex items-center gap-2 text-sm font-medium hover:underline">
                            Browse all <ArrowRight className="size-4" aria-hidden="true" />
                        </Link>
                    </div>
                    {featuredPlants.length ? (
                        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            {featuredPlants.map((plant) => (
                                <article key={plant.id} className="rounded-xl border p-5">
                                    <span className="flex size-11 items-center justify-center rounded-full bg-emerald-50 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">
                                        <Leaf className="size-5" aria-hidden="true" />
                                    </span>
                                    <h3 className="mt-4 font-semibold">{plant.plant_name}</h3>
                                    <p className="text-muted-foreground text-sm italic">{plant.botanical_name}</p>
                                    <p className="mt-3 font-medium">{peso(plant.unit_price)}</p>
                                    <p className="text-muted-foreground text-sm">
                                        {plant.inventory?.quantity_available ?? 0} available · {plant.pot_size}
                                    </p>
                                </article>
                            ))}
                        </div>
                    ) : (
                        <p className="text-muted-foreground rounded-xl border p-6">No plants are currently available.</p>
                    )}
                </section>

                <section aria-labelledby="recent-orders-heading" className="rounded-xl border">
                    <div className="flex items-center justify-between gap-4 border-b p-5">
                        <div>
                            <h2 id="recent-orders-heading" className="font-semibold">
                                Recent orders
                            </h2>
                            <p className="text-muted-foreground mt-1 text-sm">Track your latest nursery orders.</p>
                        </div>
                        <Link href="/nursery-sales" className="text-primary inline-flex items-center gap-2 text-sm font-medium hover:underline">
                            My orders <ArrowRight className="size-4" aria-hidden="true" />
                        </Link>
                    </div>
                    {sales.length ? (
                        <ul className="divide-y">
                            {sales.map((sale) => (
                                <li key={sale.id} className="flex flex-wrap items-center justify-between gap-3 p-5">
                                    <div className="flex items-center gap-3">
                                        <span className="bg-muted flex size-10 items-center justify-center rounded-full">
                                            <ReceiptText className="size-5" aria-hidden="true" />
                                        </span>
                                        <div>
                                            <p className="font-medium">Order #{sale.id}</p>
                                            <p className="text-muted-foreground text-sm">
                                                {sale.sale_items_count} {sale.sale_items_count === 1 ? 'item' : 'items'}
                                                {sale.dispatch_date
                                                    ? ` · Dispatch ${new Date(`${sale.dispatch_date}T00:00:00`).toLocaleDateString()}`
                                                    : ' · Dispatch not scheduled'}
                                            </p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <span className="text-sm font-medium">{peso(sale.total_amount)}</span>
                                        <span className="bg-muted rounded-full px-3 py-1 text-xs capitalize">{sale.status}</span>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <div className="text-muted-foreground px-5 py-10 text-center">
                            <p>You don’t have any orders yet.</p>
                            <p className="mt-1 text-sm">Your orders will appear here after checkout.</p>
                        </div>
                    )}
                </section>
            </main>
        </AppLayout>
    );
}
