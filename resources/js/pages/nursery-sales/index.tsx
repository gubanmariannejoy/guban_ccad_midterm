import { Button } from '@/components/ui/button';
import { Head, Link, router } from '@inertiajs/react';

interface NurserySale {
    id: number;
    buyer: {
        buyer_name: string;
    };
    payment_method: string;
    total_amount: string;
    dispatch_date: string | null;
    status: string;
    sale_items_count: number;
}

interface NurserySalesPage {
    data: NurserySale[];
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
}

export default function NurserySalesIndex({ sales }: { sales: NurserySalesPage }) {
    return (
        <>
            <Head title="Nursery sales" />
            <div className="bg-background text-foreground min-h-screen">
                <header className="border-b">
                    <div className="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 md:px-8">
                        <Link href="/dashboard" className="font-semibold">
                            Back
                        </Link>
                        <nav className="flex gap-4 text-sm">
                            <Link href="/plant-species">Plant species</Link>
                            <Link href="/nursery-sales" className="underline underline-offset-4">
                                Sales
                            </Link>
                            <Link href="/login">Sign in</Link>
                        </nav>
                    </div>
                </header>

                <main className="mx-auto flex max-w-7xl flex-col gap-6 p-4 md:p-8">
                    <div>
                        <h1 className="text-2xl font-semibold">Nursery sales</h1>
                        <p className="text-muted-foreground mt-1 text-sm">Browse sales, buyers, payment methods, and dispatch status.</p>
                        <Button asChild className="mt-4">
                            <Link href={route('nursery-sales.create')}>Create sale</Link>
                        </Button>
                    </div>

                    <div className="overflow-x-auto rounded-lg border">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-muted/50">
                                <tr>
                                    <th className="px-4 py-3 font-medium">Sale</th>
                                    <th className="px-4 py-3 font-medium">Buyer</th>
                                    <th className="px-4 py-3 font-medium">Dispatch date</th>
                                    <th className="px-4 py-3 font-medium">Payment</th>
                                    <th className="px-4 py-3 text-right font-medium">Items</th>
                                    <th className="px-4 py-3 text-right font-medium">Total</th>
                                    <th className="px-4 py-3 font-medium">Status</th>
                                    <th className="px-4 py-3 font-medium">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {sales.data.map((sale) => (
                                    <tr key={sale.id}>
                                        <td className="px-4 py-3 font-medium">#{sale.id}</td>
                                        <td className="px-4 py-3">{sale.buyer.buyer_name}</td>
                                        <td className="px-4 py-3">
                                            {sale.dispatch_date ? new Date(`${sale.dispatch_date}T00:00:00`).toLocaleDateString() : 'Not scheduled'}
                                        </td>
                                        <td className="px-4 py-3">{sale.payment_method}</td>
                                        <td className="px-4 py-3 text-right">{sale.sale_items_count}</td>
                                        <td className="px-4 py-3 text-right">{Number(sale.total_amount).toFixed(2)}</td>
                                        <td className="px-4 py-3 capitalize">
                                            <div className="flex items-center gap-2">
                                                <span>{sale.status}</span>
                                                {sale.status === 'pending' && (
                                                    <Link
                                                        href={route('nursery-sales.confirm', sale.id)}
                                                        method="post"
                                                        as="button"
                                                        className="rounded border px-2 py-1 text-xs"
                                                    >
                                                        Confirm
                                                    </Link>
                                                )}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3">
                                            {sale.status === 'pending' ? (
                                                <div className="flex items-center gap-2">
                                                    <Button size="sm" variant="outline" asChild>
                                                        <Link href={route('nursery-sales.edit', sale.id)}>Edit</Link>
                                                    </Button>
                                                    <Button
                                                        size="sm"
                                                        variant="destructive"
                                                        onClick={() => {
                                                            if (window.confirm(`Delete pending sale #${sale.id}?`)) {
                                                                router.delete(route('nursery-sales.destroy', sale.id));
                                                            }
                                                        }}
                                                    >
                                                        Delete
                                                    </Button>
                                                </div>
                                            ) : (
                                                <span className="text-muted-foreground text-xs">Confirmed sale</span>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                                {sales.data.length === 0 && (
                                    <tr>
                                        <td colSpan={8} className="text-muted-foreground px-4 py-10 text-center">
                                            No nursery sales have been recorded yet.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {sales.links.length > 3 && (
                        <nav aria-label="Nursery sales pages" className="flex flex-wrap gap-2">
                            {sales.links.map((link, index) =>
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
            </div>
        </>
    );
}
