import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';

interface NurserySale {
    id: number;
    buyer_name?: string | null;
    buyer_contact_no?: string | null;
    buyer_address?: string | null;
    buyer?: {
        name: string;
    } | null;
    payment_method: string;
    total_amount: string;
    dispatch_date: string | null;
    status: string;
    sale_items_count: number;
    sale_items: {
        quantity_bought: number;
        plant_species: {
            plant_name: string;
        } | null;
    }[];
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
    const { auth } = usePage<SharedData>().props;
    const isAdmin = auth.user.role === 'admin';
    const breadcrumbs: BreadcrumbItem[] = [{ title: isAdmin ? 'Nursery sales' : 'My orders', href: '/nursery-sales' }];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nursery sales" />
            <main className="flex flex-1 flex-col gap-6 p-4 md:p-8">
                <header>
                    <p className="text-muted-foreground text-sm font-medium">{isAdmin ? 'Administration' : 'Customer'}</p>
                    <h1 className="mt-1 text-2xl font-semibold">{isAdmin ? 'Nursery sales' : 'My orders'}</h1>
                    <p className="text-muted-foreground mt-2">
                        {isAdmin ? 'Review buyer orders, payments, and dispatch status.' : 'View your order history and current order status.'}
                    </p>
                    {isAdmin && (
                        <Button asChild className="mt-4">
                            <Link href={route('nursery-sales.create')}>Create sale</Link>
                        </Button>
                    )}
                </header>

                <div className="overflow-x-auto rounded-lg border">
                    <table className="w-full text-left text-sm">
                        <thead className="bg-muted/50">
                            <tr>
                                <th className="px-4 py-3 font-medium">Order</th>
                                {isAdmin && <th className="px-4 py-3 font-medium">Buyer</th>}
                                <th className="px-4 py-3 font-medium">Plants</th>
                                <th className="px-4 py-3 font-medium">Dispatch / payment</th>
                                <th className="px-4 py-3 text-right font-medium">Total</th>
                                <th className="px-4 py-3 font-medium">Status</th>
                                <th className="px-4 py-3 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {sales.data.map((sale) => (
                                <tr key={sale.id} className="align-top">
                                    <td className="px-4 py-4 font-medium">
                                        <div>#{sale.id}</div>
                                        <div className="text-muted-foreground mt-1 text-xs">
                                            {sale.sale_items_count} {sale.sale_items_count === 1 ? 'item' : 'items'}
                                        </div>
                                    </td>
                                    {isAdmin && (
                                        <td className="px-4 py-4">
                                            <div>{sale.buyer_name ?? sale.buyer?.name ?? 'Buyer account not linked'}</div>
                                            {sale.buyer_contact_no && <div className="text-muted-foreground text-xs">{sale.buyer_contact_no}</div>}
                                        </td>
                                    )}
                                    <td className="px-4 py-4">
                                        {sale.sale_items.length ? (
                                            sale.sale_items.map((item) => (
                                                <div key={`${sale.id}-${item.plant_species?.plant_name ?? 'plant'}-${item.quantity_bought}`}>
                                                    {item.plant_species?.plant_name ?? 'Plant'} × {item.quantity_bought}
                                                </div>
                                            ))
                                        ) : (
                                            <span className="text-muted-foreground">No plants</span>
                                        )}
                                    </td>
                                    <td className="px-4 py-4">
                                        <div>
                                            {sale.dispatch_date ? new Date(`${sale.dispatch_date}T00:00:00`).toLocaleDateString() : 'Not scheduled'}
                                        </div>
                                        <div className="text-muted-foreground mt-1 text-xs">{sale.payment_method}</div>
                                    </td>
                                    <td className="px-4 py-4 text-right">₱{Number(sale.total_amount).toFixed(2)}</td>
                                    <td className="px-4 py-4 capitalize">{sale.status}</td>
                                    <td className="px-4 py-4">
                                        <div className="flex flex-wrap items-center gap-2">
                                            {isAdmin && (
                                                <Button size="sm" variant="outline" asChild>
                                                    <Link href={route('nursery-sales.edit', sale.id)}>Edit</Link>
                                                </Button>
                                            )}
                                            {isAdmin && sale.status === 'pending' && (
                                                <>
                                                    <Link
                                                        href={route('nursery-sales.confirm', sale.id)}
                                                        method="post"
                                                        as="button"
                                                        className="rounded border px-2 py-1 text-xs"
                                                    >
                                                        Confirm
                                                    </Link>
                                                    <Link
                                                        href={route('nursery-sales.cancel', sale.id)}
                                                        method="post"
                                                        as="button"
                                                        className="rounded border px-2 py-1 text-xs"
                                                    >
                                                        Cancel
                                                    </Link>
                                                </>
                                            )}
                                            {isAdmin && sale.status === 'confirmed' && (
                                                <>
                                                    <Link
                                                        href={route('nursery-sales.complete', sale.id)}
                                                        method="post"
                                                        as="button"
                                                        className="rounded border px-2 py-1 text-xs"
                                                    >
                                                        Complete
                                                    </Link>
                                                    <Link
                                                        href={route('nursery-sales.cancel', sale.id)}
                                                        method="post"
                                                        as="button"
                                                        className="rounded border px-2 py-1 text-xs"
                                                    >
                                                        Cancel
                                                    </Link>
                                                </>
                                            )}
                                            {isAdmin && (
                                                <Button
                                                    size="sm"
                                                    variant="destructive"
                                                    onClick={() => {
                                                        if (window.confirm(`Delete ${sale.status} sale #${sale.id}?`)) {
                                                            router.delete(route('nursery-sales.destroy', sale.id));
                                                        }
                                                    }}
                                                >
                                                    Delete
                                                </Button>
                                            )}
                                            {!isAdmin && sale.status === 'pending' && (
                                                <Link
                                                    href={route('buyer.orders.cancel', sale.id)}
                                                    method="post"
                                                    as="button"
                                                    className="rounded border px-2 py-1 text-xs"
                                                    onBefore={() => window.confirm(`Cancel order #${sale.id}?`)}
                                                >
                                                    Cancel
                                                </Link>
                                            )}
                                            {!isAdmin && sale.status === 'completed' && (
                                                <Button
                                                    size="sm"
                                                    variant="destructive"
                                                    onClick={() => {
                                                        if (window.confirm(`Delete completed order #${sale.id}?`)) {
                                                            router.delete(route('buyer.orders.destroy', sale.id));
                                                        }
                                                    }}
                                                >
                                                    Delete
                                                </Button>
                                            )}
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            {sales.data.length === 0 && (
                                <tr>
                                    <td colSpan={isAdmin ? 7 : 6} className="text-muted-foreground px-4 py-10 text-center">
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
        </AppLayout>
    );
}
