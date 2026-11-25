import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Leaf, Search, ShoppingBag } from 'lucide-react';
import { useState, type FormEvent } from 'react';

interface Plant {
    id: number;
    plant_name: string;
    botanical_name: string;
    pot_size: string;
    unit_price: string;
    inventory: { quantity_available: number } | null;
}

interface PlantPage {
    data: Plant[];
    links: { url: string | null; label: string; active: boolean }[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Browse plants', href: '/plants' },
];
const peso = (amount: number | string) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(amount));

export default function BrowsePlants({ plants, search }: { plants: PlantPage; search: string }) {
    const [query, setQuery] = useState(search);

    function submitSearch(event: FormEvent) {
        event.preventDefault();
        router.get('/plants', query ? { search: query } : {}, { preserveState: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Browse plants" />
            <main className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-7 p-4 md:p-8">
                <header className="bg-secondary/70 flex flex-wrap items-end justify-between gap-5 rounded-[1.75rem] p-6 sm:p-8">
                    <div>
                        <p className="text-primary text-xs font-semibold tracking-[0.18em] uppercase">The nursery collection</p>
                        <h1 className="mt-2 font-serif text-3xl tracking-tight sm:text-4xl">Find your next green companion.</h1>
                        <p className="text-muted-foreground mt-2 max-w-2xl">
                            Browse available plants and explore their botanical names, pot sizes, and nursery pricing.
                        </p>
                    </div>
                    <Button variant="outline" asChild className="bg-card rounded-full">
                        <Link href="/cart">
                            <ShoppingBag className="mr-2 size-4" aria-hidden="true" /> View cart
                        </Link>
                    </Button>
                </header>

                <form onSubmit={submitSearch} className="bg-card flex max-w-2xl gap-2 rounded-2xl border p-2 shadow-sm">
                    <div className="text-muted-foreground flex flex-1 items-center gap-2 pl-2">
                        <Search className="size-4 shrink-0" aria-hidden="true" />
                        <Input
                            value={query}
                            onChange={(event) => setQuery(event.target.value)}
                            placeholder="Search common or botanical name"
                            aria-label="Search plants"
                            className="border-0 bg-transparent shadow-none focus-visible:ring-0"
                        />
                    </div>
                    <Button type="submit" className="rounded-xl px-5">
                        Search
                    </Button>
                </form>

                {plants.data.length ? (
                    <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                        {plants.data.map((plant) => (
                            <article
                                key={plant.id}
                                className="bg-card group flex flex-col overflow-hidden rounded-2xl border shadow-sm transition-shadow hover:shadow-md"
                            >
                                <div className="bg-secondary/60 relative flex h-36 items-center justify-center">
                                    <div className="border-primary/10 absolute size-28 rounded-full border" />
                                    <span className="bg-card text-primary relative flex size-16 items-center justify-center rounded-2xl shadow-sm">
                                        <Leaf className="size-8" strokeWidth={1.5} aria-hidden="true" />
                                    </span>
                                    <span className="bg-card/90 text-muted-foreground absolute top-3 right-3 rounded-full px-3 py-1 text-[11px] font-medium">
                                        {plant.inventory?.quantity_available
                                            ? `${plant.inventory.quantity_available} in stock`
                                            : 'Currently unavailable'}
                                    </span>
                                </div>
                                <div className="flex flex-1 flex-col p-5">
                                    <div>
                                        <h2 className="font-serif text-xl">{plant.plant_name}</h2>
                                        <p className="text-muted-foreground mt-1 text-sm italic">{plant.botanical_name}</p>
                                    </div>
                                    <dl className="mt-5 grid grid-cols-2 gap-3 border-t pt-4 text-sm">
                                        <div>
                                            <dt className="text-muted-foreground text-xs uppercase">Pot size</dt>
                                            <dd className="mt-1 font-medium">{plant.pot_size}</dd>
                                        </div>
                                        <div>
                                            <dt className="text-muted-foreground text-xs uppercase">Nursery price</dt>
                                            <dd className="text-primary mt-1 font-semibold">{peso(plant.unit_price)}</dd>
                                        </div>
                                    </dl>
                                    <form
                                        className="mt-5"
                                        onSubmit={(event) => {
                                            event.preventDefault();
                                            const form = new FormData(event.currentTarget);
                                            router.post(route('buyer.cart.add', plant.id), { quantity: Number(form.get('quantity')) });
                                        }}
                                    >
                                        <div className="flex items-center gap-2">
                                            <Input
                                                name="quantity"
                                                type="number"
                                                min="1"
                                                max={plant.inventory?.quantity_available ?? 1}
                                                defaultValue="1"
                                                className="w-24"
                                                aria-label={`Quantity of ${plant.plant_name}`}
                                                disabled={!plant.inventory?.quantity_available}
                                            />
                                            <Button type="submit" className="flex-1 rounded-xl" disabled={!plant.inventory?.quantity_available}>
                                                {plant.inventory?.quantity_available ? 'Add to cart' : 'Out of stock'}
                                            </Button>
                                        </div>
                                    </form>
                                </div>
                            </article>
                        ))}
                    </div>
                ) : (
                    <p className="text-muted-foreground rounded-xl border p-8 text-center">
                        {search ? 'No plants match your search.' : 'No plants are currently available.'}
                    </p>
                )}

                {plants.links.length > 3 && (
                    <nav aria-label="Plant pages" className="flex flex-wrap gap-2">
                        {plants.links.map((link, index) =>
                            link.url ? (
                                <Link
                                    key={index}
                                    href={link.url}
                                    aria-current={link.active ? 'page' : undefined}
                                    className={`rounded-md border px-3 py-1 text-sm ${link.active ? 'bg-primary text-primary-foreground' : ''}`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ) : (
                                <span
                                    key={index}
                                    className="text-muted-foreground rounded-md border px-3 py-1 text-sm"
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ),
                        )}
                    </nav>
                )}
            </main>
        </AppLayout>
    );
}
