import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';

interface Buyer {
    id: number;
    name: string;
    email: string;
    contact_no: string | null;
    sales_count: number;
}

interface BuyersPage {
    data: Buyer[];
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Buyers', href: '/buyers' }];

export default function BuyersIndex({ buyers }: { buyers: BuyersPage }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Buyers" />
            <main className="flex flex-1 flex-col gap-6 p-4 md:p-8">
                <header>
                    <p className="text-muted-foreground text-sm font-medium">Administration</p>
                    <h1 className="mt-1 text-2xl font-semibold">Buyers</h1>
                    <p className="text-muted-foreground mt-2">View buyer contact information and their recorded sales.</p>
                </header>

                <div className="overflow-x-auto rounded-lg border">
                    <table className="w-full text-left text-sm">
                        <thead className="bg-muted/50">
                            <tr>
                                <th className="px-4 py-3 font-medium">Name</th>
                                <th className="px-4 py-3 font-medium">Email</th>
                                <th className="px-4 py-3 font-medium">Contact number</th>
                                <th className="px-4 py-3 text-right font-medium">Sales</th>
                                <th className="px-4 py-3 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {buyers.data.map((buyer) => (
                                <tr key={buyer.id}>
                                    <td className="px-4 py-3 font-medium">{buyer.name}</td>
                                    <td className="px-4 py-3">{buyer.email}</td>
                                    <td className="px-4 py-3">{buyer.contact_no || 'Not provided'}</td>
                                    <td className="px-4 py-3 text-right">{buyer.sales_count}</td>
                                    <td className="px-4 py-3">
                                        <div className="flex items-center gap-2">
                                            <Button size="sm" variant="outline" asChild>
                                                <Link href={route('buyers.edit', buyer.id)}>Edit</Link>
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="destructive"
                                                disabled={buyer.sales_count > 0}
                                                title={buyer.sales_count > 0 ? 'Buyers with sales records cannot be deleted.' : undefined}
                                                onClick={() => {
                                                    if (window.confirm(`Delete buyer ${buyer.name}?`)) {
                                                        router.delete(route('buyers.destroy', buyer.id));
                                                    }
                                                }}
                                            >
                                                Delete
                                            </Button>
                                        </div>
                                        {buyer.sales_count > 0 && (
                                            <p className="text-muted-foreground mt-1 text-xs">Buyer has sales records</p>
                                        )}
                                    </td>
                                </tr>
                            ))}
                            {buyers.data.length === 0 && (
                                <tr>
                                    <td colSpan={5} className="text-muted-foreground px-4 py-10 text-center">
                                        No buyers have registered yet.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {buyers.links.length > 3 && (
                    <nav aria-label="Buyer pages" className="flex flex-wrap gap-2">
                        {buyers.links.map((link, index) =>
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
