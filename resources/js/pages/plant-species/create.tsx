import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';

interface PlantSpecies {
    id: number;
    plant_name: string;
    botanical_name: string;
    pot_size: string;
    unit_price: string;
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Plant species', href: route('plant-species.index') }];

export default function CreatePlantSpecies({ plant }: { plant?: PlantSpecies }) {
    const { data, setData, post, put, processing, errors } = useForm({
        plant_name: plant?.plant_name ?? '',
        botanical_name: plant?.botanical_name ?? '',
        pot_size: plant?.pot_size ?? '',
        unit_price: plant?.unit_price ?? '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        if (plant) {
            put(route('plant-species.update', plant.id));
            return;
        }

        post(route('plant-species.store'));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={plant ? 'Edit plant species' : 'Add plant species'} />
            <main className="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4 md:p-8">
                <div>
                    <h1 className="text-2xl font-semibold">{plant ? 'Edit plant species' : 'Add plant species'}</h1>
                    <p className="text-muted-foreground mt-1 text-sm">Enter the plant details for your nursery catalogue.</p>
                </div>

                <form onSubmit={submit} className="flex flex-col gap-5">
                    <div className="grid gap-2">
                        <Label htmlFor="plant_name">Plant name</Label>
                        <Input id="plant_name" value={data.plant_name} onChange={(event) => setData('plant_name', event.target.value)} required />
                        <InputError message={errors.plant_name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="botanical_name">Botanical name</Label>
                        <Input
                            id="botanical_name"
                            value={data.botanical_name}
                            onChange={(event) => setData('botanical_name', event.target.value)}
                            required
                        />
                        <InputError message={errors.botanical_name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="pot_size">Pot size</Label>
                        <Input id="pot_size" value={data.pot_size} onChange={(event) => setData('pot_size', event.target.value)} required />
                        <InputError message={errors.pot_size} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="unit_price">Unit price</Label>
                        <Input
                            id="unit_price"
                            type="number"
                            min="0.01"
                            step="0.01"
                            value={data.unit_price}
                            onChange={(event) => setData('unit_price', event.target.value)}
                            required
                        />
                        <InputError message={errors.unit_price} />
                    </div>

                    <div className="flex gap-3">
                        <Button type="submit" disabled={processing}>
                            {plant ? 'Update plant' : 'Save plant'}
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={route('plant-species.index')}>Cancel</Link>
                        </Button>
                    </div>
                </form>
            </main>
        </AppLayout>
    );
}
