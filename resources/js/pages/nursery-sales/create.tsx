import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { capitalizeFirstLetter } from '@/lib/utils';
import { type FormDataConvertible } from '@inertiajs/core';
import { Head, Link, useForm } from '@inertiajs/react';
import { CalendarDays } from 'lucide-react';
import { useRef, useState, type FormEventHandler } from 'react';

interface BuyerOption {
    id: number;
    name: string;
    email: string;
    contact_no: string;
}

interface PlantOption {
    id: number;
    plant_name: string;
    unit_price: string;
    inventory: { quantity_available: number } | null;
}

interface SaleRecord {
    id: number;
    user_id: number;
    payment_method: string;
    discount_rate: string;
    dispatch_date: string | null;
    status: string;
    sale_items: {
        plant_species_id: number;
        quantity_bought: number;
    }[];
}

interface NurserySaleForm extends Record<string, FormDataConvertible> {
    user_id: string;
    new_buyer: {
        name: string;
        email: string;
        contact_no: string;
        password: string;
    };
    payment_method: string;
    discount_rate: string;
    dispatch_date: string;
    items: {
        plant_species_id: string;
        quantity_bought: number;
    }[];
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Nursery sales', href: route('nursery-sales.index') }];

export default function CreateNurserySale({ buyers, plants, sale }: { buyers: BuyerOption[]; plants: PlantOption[]; sale?: SaleRecord }) {
    const [addingBuyer, setAddingBuyer] = useState(false);
    const dispatchDateRef = useRef<HTMLInputElement>(null);
    const { data, setData, post, put, transform, processing, errors } = useForm<NurserySaleForm>({
        user_id: sale ? String(sale.user_id) : '',
        new_buyer: {
            name: '',
            email: '',
            contact_no: '',
            password: '',
        },
        payment_method: sale?.payment_method ?? 'Cash',
        discount_rate: sale?.discount_rate ?? '0',
        dispatch_date: sale?.dispatch_date?.slice(0, 10) ?? '',
        items: sale?.sale_items.map((item) => ({
            plant_species_id: String(item.plant_species_id),
            quantity_bought: item.quantity_bought,
        })) ?? [{ plant_species_id: '', quantity_bought: 1 }],
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        transform((formData) => {
            const { user_id, new_buyer, ...saleData } = formData;

            if (addingBuyer) {
                return { ...saleData, user_id: null, new_buyer };
            }

            return { ...saleData, user_id: Number(user_id) };
        });

        if (sale) {
            put(route('nursery-sales.update', sale.id));
            return;
        }

        post(route('nursery-sales.store'));
    };

    const updateItem = (index: number, field: 'plant_species_id' | 'quantity_bought', value: string | number) => {
        const items = data.items.map((item, itemIndex) => (itemIndex === index ? { ...item, [field]: value } : item));
        setData('items', items);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={sale ? 'Edit nursery sale' : 'Create nursery sale'} />
            <main className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 md:p-8">
                <div>
                    <h1 className="text-2xl font-semibold">{sale ? 'Edit nursery sale' : 'Create nursery sale'}</h1>
                    <p className="text-muted-foreground mt-1 text-sm">Choose a buyer and add the plants included in this sale.</p>
                </div>

                <form onSubmit={submit} className="flex flex-col gap-5">
                    <div className="grid gap-2">
                        <div className="flex items-center justify-between gap-3">
                            <Label htmlFor="user_id">Buyer account</Label>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => {
                                    setAddingBuyer((isAdding) => !isAdding);
                                    if (!addingBuyer) {
                                        setData('user_id', '');
                                    }
                                }}
                            >
                                {addingBuyer ? 'Choose existing buyer' : 'Add buyer'}
                            </Button>
                        </div>
                        <select
                            id="user_id"
                            className="bg-background h-10 rounded-md border px-3 text-sm"
                            value={data.user_id}
                            disabled={addingBuyer}
                            required={!addingBuyer}
                            onChange={(event) => setData('user_id', event.target.value)}
                        >
                            <option value="">Select a registered user</option>
                            {buyers.map((buyer) => (
                                <option key={buyer.id} value={buyer.id}>
                                    {buyer.name} — {buyer.email} ({buyer.contact_no})
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.user_id} />
                    </div>

                    {addingBuyer && (
                        <section className="grid gap-4 rounded-lg border p-4">
                            <h2 className="font-medium">New buyer account</h2>
                            <p className="text-muted-foreground text-sm">
                                Create a user account for this buyer. They can sign in with these credentials.
                            </p>
                            <div className="grid gap-2">
                                <Label htmlFor="new_buyer_name">Buyer name</Label>
                                <Input
                                    id="new_buyer_name"
                                    value={data.new_buyer.name}
                                    onChange={(event) =>
                                        setData('new_buyer', {
                                            ...data.new_buyer,
                                            name: capitalizeFirstLetter(event.target.value),
                                        })
                                    }
                                    required
                                />
                                <InputError message={errors['new_buyer.name']} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="new_buyer_email">Email</Label>
                                <Input
                                    id="new_buyer_email"
                                    type="email"
                                    autoComplete="email"
                                    value={data.new_buyer.email}
                                    onChange={(event) => setData('new_buyer', { ...data.new_buyer, email: event.target.value })}
                                    required
                                />
                                <InputError message={errors['new_buyer.email']} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="new_buyer_contact">Contact number</Label>
                                <Input
                                    id="new_buyer_contact"
                                    type="tel"
                                    inputMode="numeric"
                                    maxLength={11}
                                    pattern="[0-9]{11}"
                                    title="Enter exactly 11 digits."
                                    value={data.new_buyer.contact_no}
                                    onChange={(event) =>
                                        setData('new_buyer', {
                                            ...data.new_buyer,
                                            contact_no: event.target.value.replace(/[^0-9]/g, '').slice(0, 11),
                                        })
                                    }
                                    required
                                />
                                <InputError message={errors['new_buyer.contact_no']} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="new_buyer_password">Temporary password</Label>
                                <Input
                                    id="new_buyer_password"
                                    type="password"
                                    autoComplete="new-password"
                                    minLength={8}
                                    value={data.new_buyer.password}
                                    onChange={(event) => setData('new_buyer', { ...data.new_buyer, password: event.target.value })}
                                    required
                                />
                                <InputError message={errors['new_buyer.password']} />
                            </div>
                        </section>
                    )}

                    <div className="grid gap-2">
                        <Label htmlFor="payment_method">Payment method</Label>
                        <select
                            id="payment_method"
                            className="bg-background h-10 rounded-md border px-3 text-sm"
                            value={data.payment_method}
                            onChange={(event) => setData('payment_method', event.target.value)}
                        >
                            <option>Cash</option>
                            <option>GCash</option>
                            <option>Bank Transfer</option>
                        </select>
                        <InputError message={errors.payment_method} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="discount_rate">Discount rate (0-1)</Label>
                        <Input
                            id="discount_rate"
                            type="number"
                            min="0"
                            max="1"
                            step="0.01"
                            value={data.discount_rate}
                            onChange={(event) => setData('discount_rate', event.target.value)}
                            required
                        />
                        <InputError message={errors.discount_rate} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="dispatch_date">Dispatch date</Label>
                        <div className="flex gap-2">
                            <Input
                                ref={dispatchDateRef}
                                id="dispatch_date"
                                type="date"
                                min={new Date().toISOString().slice(0, 10)}
                                max="2027-12-31"
                                value={data.dispatch_date}
                                onChange={(event) => {
                                    const value = event.target.value;
                                    if (value && Number(value.slice(0, 4)) > 2027) return;
                                    setData('dispatch_date', value);
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
                        <InputError message={errors.dispatch_date} />
                    </div>

                    <section className="flex flex-col gap-4 rounded-lg border p-4">
                        <h2 className="font-medium">Sale items</h2>
                        {data.items.map((item, index) => (
                            <div key={index} className="grid gap-3 sm:grid-cols-[1fr_10rem_auto]">
                                <div className="grid gap-2">
                                    <Label htmlFor={`plant-${index}`}>Plant</Label>
                                    <select
                                        id={`plant-${index}`}
                                        className="bg-background h-10 rounded-md border px-3 text-sm"
                                        value={item.plant_species_id}
                                        onChange={(event) => updateItem(index, 'plant_species_id', event.target.value)}
                                        required
                                    >
                                        <option value="">Select a plant</option>
                                        {plants.map((plant) => (
                                            <option key={plant.id} value={plant.id}>
                                                {plant.plant_name} — {Number(plant.unit_price).toFixed(2)} (stock:{' '}
                                                {plant.inventory?.quantity_available ?? 0})
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors[`items.${index}.plant_species_id`]} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor={`quantity-${index}`}>Quantity</Label>
                                    <Input
                                        id={`quantity-${index}`}
                                        type="number"
                                        min="1"
                                        max="100000"
                                        value={item.quantity_bought}
                                        onChange={(event) => updateItem(index, 'quantity_bought', Number(event.target.value))}
                                        required
                                    />
                                    <InputError message={errors[`items.${index}.quantity_bought`]} />
                                </div>
                                <div className="flex items-end">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        disabled={data.items.length === 1}
                                        onClick={() =>
                                            setData(
                                                'items',
                                                data.items.filter((_, itemIndex) => itemIndex !== index),
                                            )
                                        }
                                    >
                                        Remove
                                    </Button>
                                </div>
                            </div>
                        ))}
                        <InputError message={errors.items} />
                        <Button
                            type="button"
                            variant="outline"
                            className="self-start"
                            onClick={() => setData('items', [...data.items, { plant_species_id: '', quantity_bought: 1 }])}
                        >
                            Add another plant
                        </Button>
                    </section>

                    <div className="flex gap-3">
                        <Button type="submit" disabled={processing || (buyers.length === 0 && !addingBuyer) || plants.length === 0}>
                            {sale ? 'Update sale' : 'Save sale'}
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={route('nursery-sales.index')}>Cancel</Link>
                        </Button>
                    </div>
                    {buyers.length === 0 && !addingBuyer && <p className="text-destructive text-sm">Add a buyer before creating a sale.</p>}
                    {plants.length === 0 && <p className="text-destructive text-sm">Add a plant species before creating a sale.</p>}
                </form>
            </main>
        </AppLayout>
    );
}
