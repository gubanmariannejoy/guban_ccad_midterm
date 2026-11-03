import { Button } from '@/components/ui/button';
import { Head, Link, router } from '@inertiajs/react';

interface PlantSpecies {
    id: number;
    plant_name: string;
    botanical_name: string;
    pot_size: string;
    unit_price: string;
    inventory?: {
        quantity_available: number;
    } | null;
    sale_items_count: number;
}

interface PlantSpeciesPage {
    data: PlantSpecies[];
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
}

export default function PlantSpeciesIndex({ plants }: { plants: PlantSpeciesPage }) {
    return (
        <>
            <Head title="Plant species" />
            <div className="bg-background text-foreground min-h-screen">
                <header className="border-b">
                    <div className="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 md:px-8">
                        <Link href="/dashboard" className="font-semibold">
                            Back
                        </Link>
                        <nav className="flex gap-4 text-sm">
                            <a href="/plant-species" className="underline underline-offset-4">
                                Plant species
                            </a>
                            <a href="/nursery-sales">Sales</a>
                            <a href="/login">Sign in</a>
                        </nav>
                    </div>
                </header>
                <main className="mx-auto flex max-w-7xl flex-col gap-6 p-4 md:p-8">
                    <div>
                        <h1 className="text-2xl font-semibold">Plant species</h1>
                        <p className="text-muted-foreground mt-1 text-sm">Browse your nursery catalogue and available stock.</p>
                        <Button asChild className="mt-4">
                            <Link href={route('plant-species.create')}>Add plant species</Link>
                        </Button>
                    </div>

                    <div className="overflow-x-auto rounded-lg border">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-muted/50">
                                <tr>
                                    <th className="px-4 py-3 font-medium">Plant</th>
                                    <th className="px-4 py-3 font-medium">Botanical name</th>
                                    <th className="px-4 py-3 font-medium">Pot size</th>
                                    <th className="px-4 py-3 text-right font-medium">Unit price</th>
                                    <th className="px-4 py-3 text-right font-medium">Available</th>
                                    <th className="px-4 py-3 font-medium">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {plants.data.map((plant) => (
                                    <tr key={plant.id}>
                                        <td className="px-4 py-3 font-medium">{plant.plant_name}</td>
                                        <td className="px-4 py-3 italic">{plant.botanical_name}</td>
                                        <td className="px-4 py-3">{plant.pot_size}</td>
                                        <td className="px-4 py-3 text-right">{Number(plant.unit_price).toFixed(2)}</td>
                                        <td className="px-4 py-3 text-right">{plant.inventory?.quantity_available ?? 0}</td>
                                        <td className="px-4 py-3">
                                            <div className="flex items-center gap-2">
                                                <Button size="sm" variant="outline" asChild>
                                                    <Link href={route('plant-species.edit', plant.id)}>Edit</Link>
                                                </Button>
                                                <Button
                                                    size="sm"
                                                    variant="destructive"
                                                    disabled={plant.sale_items_count > 0}
                                                    onClick={() => {
                                                        if (window.confirm(`Delete ${plant.plant_name}?`)) {
                                                            router.delete(route('plant-species.destroy', plant.id));
                                                        }
                                                    }}
                                                >
                                                    Delete
                                                </Button>
                                            </div>
                                            {plant.sale_items_count > 0 && <p className="text-muted-foreground mt-1 text-xs">Used in sales</p>}
                                        </td>
                                    </tr>
                                ))}
                                {plants.data.length === 0 && (
                                    <tr>
                                        <td colSpan={6} className="text-muted-foreground px-4 py-10 text-center">
                                            No plants have been added yet.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {plants.links.length > 3 && (
                        <nav aria-label="Plant species pages" className="flex flex-wrap gap-2">
                            {plants.links.map((link, index) =>
                                link.url ? (
                                    <a
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
            </div>
        </>
    );
}
