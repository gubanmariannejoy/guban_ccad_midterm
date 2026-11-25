import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowRight, Leaf, ReceiptText, Sprout } from 'lucide-react';

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
            <main className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-9 p-4 md:p-8">
                <header className="bg-primary text-primary-foreground relative isolate overflow-hidden rounded-[2rem] p-6 sm:p-9 lg:p-12">
                    <div className="border-primary-foreground/10 absolute -top-24 -right-10 -z-10 size-80 rounded-full border" />
                    <div className="border-primary-foreground/10 absolute -right-24 -bottom-48 -z-10 size-96 rounded-full border" />
                    <div className="grid items-center gap-8 lg:grid-cols-[1fr_auto]">
                        <div className="max-w-2xl">
                            <p className="text-primary-foreground/70 text-xs font-semibold tracking-[0.18em] uppercase">
                                Plant Nursery &amp; Botanical
                            </p>
                            <h1 className="mt-4 font-serif text-4xl leading-tight tracking-tight sm:text-5xl">Bring a little more life home.</h1>
                            <p className="text-primary-foreground/80 mt-4 max-w-xl leading-7">
                                Explore our nursery collection, discover each plant’s botanical details, and find the right green companion for your
                                space.
                            </p>
                            <Button asChild className="bg-primary-foreground text-primary hover:bg-primary-foreground/90 mt-6 rounded-full px-5">
                                <Link href="/plants">
                                    Browse the collection <ArrowRight className="ml-2 size-4" aria-hidden="true" />
                                </Link>
                            </Button>
                        </div>
                        <div className="border-primary-foreground/15 bg-primary-foreground/10 hidden size-48 items-center justify-center rounded-[2.5rem] border lg:flex">
                            <Sprout className="text-primary-foreground/90 size-28" strokeWidth={1.1} aria-hidden="true" />
                        </div>
                    </div>
                </header>

                <section aria-label="Purchase summary" className="grid gap-4 sm:grid-cols-2">
                    <div className="bg-card flex items-center gap-4 rounded-2xl border p-5 shadow-sm">
                        <span className="bg-secondary text-primary flex size-12 items-center justify-center rounded-2xl">
                            <ReceiptText className="size-5" aria-hidden="true" />
                        </span>
                        <span>
                            <span className="text-muted-foreground block text-sm">Pending orders</span>
                            <span className="mt-1 block text-3xl font-semibold tracking-tight">{stats.pendingOrders}</span>
                        </span>
                    </div>
                    <div className="bg-card flex items-center gap-4 rounded-2xl border p-5 shadow-sm">
                        <span className="bg-secondary text-primary flex size-12 items-center justify-center rounded-2xl">
                            <Leaf className="size-5" aria-hidden="true" />
                        </span>
                        <span>
                            <span className="text-muted-foreground block text-sm">Completed purchases</span>
                            <span className="mt-1 block text-3xl font-semibold tracking-tight">{stats.completedPurchases}</span>
                        </span>
                    </div>
                </section>

                <section className="space-y-4">
                    <div className="flex items-center justify-between gap-4">
                        <div>
                            <h2 className="font-serif text-2xl">Featured plants</h2>
                            <p className="text-muted-foreground mt-1 text-sm">A few nursery favorites, ready to order.</p>
                        </div>
                        <Link href="/plants" className="text-primary inline-flex items-center gap-2 text-sm font-medium hover:underline">
                            Browse all <ArrowRight className="size-4" aria-hidden="true" />
                        </Link>
                    </div>
                    {featuredPlants.length ? (
                        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            {featuredPlants.map((plant) => (
                                <article key={plant.id} className="bg-card rounded-2xl border p-5 shadow-sm">
                                    <span className="bg-secondary text-primary flex size-12 items-center justify-center rounded-2xl">
                                        <Leaf className="size-5" aria-hidden="true" />
                                    </span>
                                    <h3 className="mt-4 font-serif text-xl">{plant.plant_name}</h3>
                                    <p className="text-muted-foreground text-sm italic">{plant.botanical_name}</p>
                                    <p className="text-primary mt-4 font-semibold">{peso(plant.unit_price)}</p>
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

                <section aria-labelledby="recent-orders-heading" className="bg-card overflow-hidden rounded-2xl border shadow-sm">
                    <div className="flex items-center justify-between gap-4 border-b p-5 sm:p-6">
                        <div>
                            <h2 id="recent-orders-heading" className="font-serif text-2xl">
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
                                <li key={sale.id} className="flex flex-wrap items-center justify-between gap-3 p-5 sm:px-6">
                                    <div className="flex items-center gap-3">
                                        <span className="bg-secondary text-primary flex size-10 items-center justify-center rounded-xl">
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
