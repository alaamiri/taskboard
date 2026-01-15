import { Head, useForm, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function Create() {
    // useForm gère le state du formulaire et la soumission
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        description: ''
    });

    function handleSubmit(e) {
        e.preventDefault();
        post(route('boards.store'));
    }

    return (
        <AuthenticatedLayout>
            <Head title="Nouveau Board" />

            <div className="p-6 max-w-md mx-auto">
                <h1 className="text-2xl font-bold mb-6">Créer un Board</h1>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <div>
                        <label className="block text-sm font-medium mb-1">
                            Nom
                        </label>
                        <input
                            type="text"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            className="w-full border rounded px-3 py-2"
                            placeholder="Mon projet"
                        />
                        {errors.name && (
                            <p className="text-red-500 text-sm mt-1">{errors.name}</p>
                        )}
                    </div>

                    <div>
                        <label className="block text-sm font-medium mb-1">
                            Description (optionnel)
                        </label>
                        <textarea
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            className="w-full border rounded px-3 py-2"
                            rows="3"
                        />
                        {errors.description && (
                            <p className="text-red-500 text-sm mt-1">{errors.description}</p>
                        )}
                    </div>

                    <div className="flex gap-2">
                        <button
                            type="submit"
                            disabled={processing}
                            className="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 disabled:opacity-50"
                        >
                            {processing ? 'Création...' : 'Créer'}
                        </button>
                        <Link
                            href={route('boards.index')}
                            className="px-4 py-2 border rounded hover:bg-gray-100"
                        >
                            Annuler
                        </Link>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
