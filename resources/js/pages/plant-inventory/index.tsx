import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface PlantInventoryRecord {
    id: number;
    plant_name: string;
    botanical_name: string;
    pot_size: string;
    unit_price: string;
    inventory: {
        quantity_available: number;
    } | null;
}

interface PlantInventoryPage {
    data: PlantInventoryRecord[];
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Inventory', href: '/plant-inventory' }];

export default function PlantInventoryIndex({ plants }: { plants: PlantInventoryPage }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Plant inventory" />
            <main className="flex flex-1 flex-col gap-6 p-4 md:p-8">
                <header>
                    <p className="text-muted-foreground text-sm font-medium">Administration</p>
                    <h1 className="mt-1 text-2xl font-semibold">Plant inventory</h1>
                    <p className="text-muted-foreground mt-2">Monitor the available stock for each plant species.</p>
                </header>

                <div className="overflow-x-auto rounded-lg border">
                    <table className="w-full text-left text-sm">
                        <thead className="bg-muted/50">
                            <tr>
                                <th className="px-4 py-3 font-medium">Plant</th>
                                <th className="px-4 py-3 font-medium">Botanical name</th>
                                <th className="px-4 py-3 font-medium">Pot size</th>
                                <th className="px-4 py-3 text-right font-medium">Unit price</th>
                                <th className="px-4 py-3 text-right font-medium">Available stock</th>
                                <th className="px-4 py-3 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {plants.data.map((plant) => (
                                <tr key={plant.id}>
                                    <td className="px-4 py-3 font-medium">{plant.plant_name}</td>
                                    <td className="px-4 py-3 italic">{plant.botanical_name}</td>
                                    <td className="px-4 py-3">{plant.pot_size}</td>
                                    <td className="px-4 py-3 text-right">₱{Number(plant.unit_price).toFixed(2)}</td>
                                    <td className="px-4 py-3 text-right">{plant.inventory?.quantity_available ?? 0}</td>
                                    <td className="px-4 py-3">
                                        <Link className="text-primary underline underline-offset-4" href={route('plant-species.edit', plant.id)}>
                                            Update plant
                                        </Link>
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
                    <nav aria-label="Inventory pages" className="flex flex-wrap gap-2">
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
