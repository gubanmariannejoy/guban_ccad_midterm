import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
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
            <main className="flex flex-1 flex-col gap-6 p-4 md:p-8">
                <header className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold">Browse plants</h1>
                        <p className="text-muted-foreground mt-1">Choose from the plants currently available at the nursery.</p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href="/cart">View shopping cart</Link>
                    </Button>
                </header>

                <form onSubmit={submitSearch} className="flex max-w-xl gap-2">
                    <Input
                        value={query}
                        onChange={(event) => setQuery(event.target.value)}
                        placeholder="Search plant or botanical name"
                        aria-label="Search plants"
                    />
                    <Button type="submit" variant="outline" aria-label="Search">
                        <Search className="size-4" />
                    </Button>
                </form>

                {plants.data.length ? (
                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        {plants.data.map((plant) => (
                            <article key={plant.id} className="flex flex-col rounded-xl border p-5">
                                <h2 className="mt-4 text-lg font-semibold">{plant.plant_name}</h2>
                                <p className="text-muted-foreground text-sm italic">{plant.botanical_name}</p>
                                <dl className="mt-4 grid grid-cols-2 gap-3 text-sm">
                                    <div>
                                        <dt className="text-muted-foreground">Price</dt>
                                        <dd className="mt-1 font-medium">{peso(plant.unit_price)}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-muted-foreground">Pot size</dt>
                                        <dd className="mt-1">{plant.pot_size}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-muted-foreground">Available stock</dt>
                                        <dd className="mt-1">{plant.inventory?.quantity_available ?? 0}</dd>
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
                                        <Button type="submit" disabled={!plant.inventory?.quantity_available}>
                                            {plant.inventory?.quantity_available ? 'Add to cart' : 'Out of stock'}
                                        </Button>
                                    </div>
                                </form>
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
