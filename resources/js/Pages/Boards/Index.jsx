import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function Index({ boards }) {
    return (
        <AuthenticatedLayout>
            <Head title="Mes Boards" />

            <div className="p-6">
                <div className="flex justify-between items-center mb-6">
                    <h1 className="text-2xl font-bold">Mes Boards</h1>
                    <Link
                        href={route('boards.create')}
                        className="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
                    >
                        Nouveau Board
                    </Link>
                </div>

                {boards.length === 0 ? (
                    <p className="text-gray-500">Aucun board. Créez-en un !</p>
                ) : (
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        {boards.map((board) => (
                            <Link
                                key={board.id}
                                href={route('boards.show', board.id)}
                                className="block p-4 bg-white rounded shadow hover:shadow-lg transition"
                            >
                                <h2 className="text-lg font-semibold">{board.name}</h2>
                                <p className="text-gray-500 text-sm">
                                    {board.columns?.length || 0} colonnes
                                </p>
                            </Link>
                        ))}
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
