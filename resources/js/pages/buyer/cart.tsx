import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import type { FormDataConvertible } from '@inertiajs/core';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { CalendarDays, Trash2 } from 'lucide-react';
import { useRef, type FormEvent } from 'react';

interface CartItem {
    id: number;
    plant_name: string;
    botanical_name: string;
    pot_size: string;
    unit_price: string;
    quantity_available: number;
    quantity: number;
}

interface Buyer {
    name: string;
    contact_no: string | null;
    address: string | null;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Shopping cart', href: '/cart' },
];
const peso = (amount: number) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount);

interface CheckoutForm extends Record<string, FormDataConvertible> {
    name: string;
    contact_no: string;
    address: string;
    payment_method: string;
    dispatch_date: string;
}

function CartLine({ item }: { item: CartItem }) {
    const form = useForm({ quantity: String(item.quantity) });

    function update(event: FormEvent) {
        event.preventDefault();
        form.patch(route('buyer.cart.update', item.id), { preserveScroll: true });
    }

    return (
        <li className="flex flex-wrap items-center justify-between gap-4 p-5">
            <div className="min-w-48">
                <h2 className="font-medium">{item.plant_name}</h2>
                <p className="text-muted-foreground text-sm italic">{item.botanical_name}</p>
                <p className="text-muted-foreground mt-1 text-sm">
                    {item.pot_size} · {peso(Number(item.unit_price))} each
                </p>
            </div>
            <div className="flex flex-wrap items-center gap-3">
                <form onSubmit={update} className="flex items-center gap-2">
                    <Label htmlFor={`quantity-${item.id}`} className="sr-only">
                        Quantity of {item.plant_name}
                    </Label>
                    <Input
                        id={`quantity-${item.id}`}
                        type="number"
                        min="1"
                        max={Math.max(item.quantity_available, 1)}
                        className="w-20"
                        value={form.data.quantity}
                        onChange={(event) => form.setData('quantity', event.target.value)}
                        aria-label={`Quantity of ${item.plant_name}`}
                    />
                    <Button type="submit" variant="outline" size="sm" disabled={form.processing || item.quantity_available < 1}>
                        Update
                    </Button>
                </form>
                <span className="w-24 text-right font-medium">{peso(Number(item.unit_price) * item.quantity)}</span>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    aria-label={`Remove ${item.plant_name}`}
                    onClick={() => router.delete(route('buyer.cart.remove', item.id), { preserveScroll: true })}
                >
                    <Trash2 className="size-4" aria-hidden="true" />
                </Button>
            </div>
            {item.quantity_available < item.quantity && (
                <p className="text-destructive w-full text-sm">Only {item.quantity_available} currently in stock. Update the quantity to continue.</p>
            )}
            <InputError message={form.errors.quantity} />
        </li>
    );
}

export default function BuyerCart({ items, buyer }: { items: CartItem[]; buyer: Buyer }) {
    const subtotal = items.reduce((total, item) => total + Number(item.unit_price) * item.quantity, 0);
    const dispatchDateRef = useRef<HTMLInputElement>(null);
    const form = useForm<CheckoutForm>({
        name: buyer.name,
        contact_no: buyer.contact_no ?? '',
        address: buyer.address ?? '',
        payment_method: 'Cash',
        dispatch_date: '',
    });

    function checkout(event: FormEvent) {
        event.preventDefault();
        form.post(route('buyer.checkout'));
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Shopping cart" />
            <main className="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 md:p-8">
                <header>
                    <h1 className="text-2xl font-semibold">Shopping cart</h1>
                    <p className="text-muted-foreground mt-1">Review your plants and place your order.</p>
                </header>

                {items.length ? (
                    <>
                        <section className="rounded-xl border">
                            <ul className="divide-y">
                                {items.map((item) => (
                                    <CartLine key={item.id} item={item} />
                                ))}
                            </ul>
                            <div className="flex justify-end border-t p-5">
                                <p className="text-lg font-semibold">Subtotal: {peso(subtotal)}</p>
                            </div>
                        </section>

                        <form onSubmit={checkout} className="grid gap-5 rounded-xl border p-5 md:grid-cols-2">
                            <div className="md:col-span-2">
                                <h2 className="text-lg font-semibold">Checkout</h2>
                                <p className="text-muted-foreground mt-1 text-sm">Your order will be pending until the nursery confirms it.</p>
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="buyer-name">Name</Label>
                                <Input
                                    id="buyer-name"
                                    value={form.data.name}
                                    onChange={(event) => form.setData('name', event.target.value)}
                                    required
                                    maxLength={255}
                                />
                                <InputError message={form.errors.name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="buyer-contact">Contact number</Label>
                                <Input
                                    id="buyer-contact"
                                    type="tel"
                                    placeholder="+639171234567"
                                    value={form.data.contact_no}
                                    onChange={(event) => form.setData('contact_no', event.target.value)}
                                    required
                                />
                                <InputError message={form.errors.contact_no} />
                            </div>
                            <div className="grid gap-2 md:col-span-2">
                                <Label htmlFor="buyer-address">Address</Label>
                                <textarea
                                    id="buyer-address"
                                    className="border-input bg-background min-h-20 rounded-md border px-3 py-2 text-sm"
                                    maxLength={1000}
                                    value={form.data.address}
                                    onChange={(event) => form.setData('address', event.target.value)}
                                />
                                <InputError message={form.errors.address} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="payment-method">Payment method</Label>
                                <select
                                    id="payment-method"
                                    className="bg-background h-10 rounded-md border px-3 text-sm"
                                    value={form.data.payment_method}
                                    onChange={(event) => form.setData('payment_method', event.target.value)}
                                >
                                    <option>Cash</option>
                                    <option>GCash</option>
                                    <option>Bank Transfer</option>
                                </select>
                                <InputError message={form.errors.payment_method} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="dispatch-date">Preferred dispatch date (optional)</Label>
                                <div className="flex gap-2">
                                    <Input
                                        ref={dispatchDateRef}
                                        id="dispatch-date"
                                        type="date"
                                        min={new Date().toISOString().slice(0, 10)}
                                        max="2027-12-31"
                                        value={form.data.dispatch_date}
                                        onChange={(event) => {
                                            const value = event.target.value;
                                            if (value && Number(value.slice(0, 4)) > 2027) return;
                                            form.setData('dispatch_date', value);
                                        }}
                                    />
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="icon"
                                        aria-label="Open dispatch date calendar"
                                        onClick={() => {
                                            const input = dispatchDateRef.current;
                                            if (!input) return;

                                            if (typeof input.showPicker === 'function') {
                                                input.showPicker();
                                            } else {
                                                input.focus();
                                                input.click();
                                            }
                                        }}
                                    >
                                        <CalendarDays aria-hidden="true" />
                                    </Button>
                                </div>
                                <InputError message={form.errors.dispatch_date} />
                            </div>
                            <InputError message={form.errors.cart} />
                            <div className="flex flex-wrap items-center justify-between gap-3 md:col-span-2">
                                <Button type="submit" disabled={form.processing || items.some((item) => item.quantity > item.quantity_available)}>
                                    {form.processing ? 'Placing order…' : `Place order · ${peso(subtotal)}`}
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href="/plants">Continue browsing</Link>
                                </Button>
                            </div>
                        </form>
                    </>
                ) : (
                    <div className="rounded-xl border p-10 text-center">
                        <p className="text-muted-foreground">Your cart is empty.</p>
                        <Button asChild className="mt-4">
                            <Link href="/plants">Browse plants</Link>
                        </Button>
                    </div>
                )}
            </main>
        </AppLayout>
    );
}
