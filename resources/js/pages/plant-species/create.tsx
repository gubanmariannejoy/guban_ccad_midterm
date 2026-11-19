import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { capitalizeFirstLetter } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { type FormDataConvertible } from '@inertiajs/core';
import { Head, Link, useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';

interface PlantSpecies {
    id: number;
    plant_name: string;
    botanical_name: string;
    pot_size: string;
    unit_price: string;
    inventory?: {
        quantity_available: number;
    } | null;
}

const potSizes = ['XS-8cm', 'S-10cm', 'M-12cm', 'L-15cm', 'XL-20cm'];
const breadcrumbs: BreadcrumbItem[] = [{ title: 'Plant species', href: route('plant-species.index') }];

interface PlantSpeciesForm extends Record<string, FormDataConvertible> {
    plant_name: string;
    botanical_name: string;
    pot_size: string;
    unit_price: string;
    quantity_available: string;
}

export default function CreatePlantSpecies({ plant }: { plant?: PlantSpecies }) {
    const { data, setData, post, put, processing, errors } = useForm<PlantSpeciesForm>({
        plant_name: plant?.plant_name ?? '',
        botanical_name: plant?.botanical_name ?? '',
        pot_size: plant && potSizes.includes(plant.pot_size) ? plant.pot_size : '',
        unit_price: plant?.unit_price ?? '',
        quantity_available: String(plant?.inventory?.quantity_available ?? 0),
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
                        <Input
                            id="plant_name"
                            value={data.plant_name}
                            onChange={(event) => setData('plant_name', capitalizeFirstLetter(event.target.value))}
                            required
                        />
                        <InputError message={errors.plant_name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="botanical_name">Botanical name</Label>
                        <Input
                            id="botanical_name"
                            value={data.botanical_name}
                            onChange={(event) => setData('botanical_name', capitalizeFirstLetter(event.target.value))}
                            required
                        />
                        <InputError message={errors.botanical_name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="pot_size">Pot size</Label>
                        <select
                            id="pot_size"
                            className="bg-background h-10 rounded-md border px-3 text-sm"
                            value={data.pot_size}
                            onChange={(event) => setData('pot_size', event.target.value)}
                            required
                        >
                            <option value="">Select a pot size</option>
                            {potSizes.map((potSize) => (
                                <option key={potSize} value={potSize}>
                                    {potSize}
                                </option>
                            ))}
                        </select>
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

                    <div className="grid gap-2">
                        <Label htmlFor="quantity_available">Available quantity</Label>
                        <Input
                            id="quantity_available"
                            type="number"
                            min="0"
                            max="100"
                            step="1"
                            value={data.quantity_available}
                            onChange={(event) => setData('quantity_available', event.target.value)}
                            required
                        />
                        <InputError message={errors.quantity_available} />
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
