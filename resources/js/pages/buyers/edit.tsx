import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { capitalizeFirstLetter } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEventHandler } from 'react';

interface Buyer {
    id: number;
    name: string;
    email: string;
    contact_no: string | null;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Buyers', href: route('buyers.index') },
    { title: 'Edit buyer', href: '#' },
];

export default function EditBuyer({ buyer }: { buyer: Buyer }) {
    const { data, setData, put, processing, errors } = useForm({
        name: buyer.name,
        email: buyer.email,
        contact_no: buyer.contact_no ?? '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        put(route('buyers.update', buyer.id));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit buyer" />
            <main className="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4 md:p-8">
                <header>
                    <p className="text-muted-foreground text-sm font-medium">Administration</p>
                    <h1 className="mt-1 text-2xl font-semibold">Edit buyer</h1>
                    <p className="text-muted-foreground mt-2">Update the buyer’s contact information.</p>
                </header>

                <form onSubmit={submit} className="flex flex-col gap-5">
                    <div className="grid gap-2">
                        <Label htmlFor="name">Name</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(event) => setData('name', capitalizeFirstLetter(event.target.value))}
                            required
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="email">Email</Label>
                        <Input
                            id="email"
                            type="email"
                            value={data.email}
                            onChange={(event) => setData('email', event.target.value)}
                            required
                        />
                        <InputError message={errors.email} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="contact_no">Contact number</Label>
                        <Input
                            id="contact_no"
                            type="tel"
                            value={data.contact_no}
                            onChange={(event) => setData('contact_no', event.target.value)}
                        />
                        <p className="text-muted-foreground text-xs">Use a Philippine mobile number, such as 09171234567.</p>
                        <InputError message={errors.contact_no} />
                    </div>

                    <div className="flex gap-3">
                        <Button type="submit" disabled={processing}>Save changes</Button>
                        <Button variant="outline" asChild>
                            <Link href={route('buyers.index')}>Cancel</Link>
                        </Button>
                    </div>
                </form>
            </main>
        </AppLayout>
    );
}
