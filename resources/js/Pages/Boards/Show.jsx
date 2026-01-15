import { Head, useForm, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useState } from 'react';

export default function Show({ board }) {
    const [showColumnForm, setShowColumnForm] = useState(false);

    // Formulaire pour nouvelle colonne
    const columnForm = useForm({ name: '' });

    // Formulaire pour nouvelle carte (stocke l'ID de la colonne active)
    const [activeColumnId, setActiveColumnId] = useState(null);
    const cardForm = useForm({ title: '', description: '' });

    function addColumn(e) {
        e.preventDefault();
        columnForm.post(route('columns.store', board.id), {
            onSuccess: () => {
                columnForm.reset();
                setShowColumnForm(false);
            }
        });
    }

    function addCard(e, columnId) {
        e.preventDefault();
        cardForm.post(route('cards.store', columnId), {
            onSuccess: () => {
                cardForm.reset();
                setActiveColumnId(null);
            }
        });
    }

    function deleteColumn(columnId) {
        if (confirm('Supprimer cette colonne et toutes ses cartes ?')) {
            router.delete(route('columns.destroy', columnId));
        }
    }

    function deleteCard(cardId) {
        if (confirm('Supprimer cette carte ?')) {
            router.delete(route('cards.destroy', cardId));
        }
    }

    return (
        <AuthenticatedLayout>
            <Head title={board.name} />

            <div className="p-6">
                <h1 className="text-2xl font-bold mb-6">{board.name}</h1>
                {board.description && (
                    <p className="text-gray-600 mb-6">{board.description}</p>
                )}

                <div className="flex gap-4 overflow-x-auto pb-4">
                    {/* Colonnes */}
                    {board.columns?.map((column) => (
                        <div
                            key={column.id}
                            className="bg-gray-100 rounded p-4 min-w-[280px] max-w-[280px]"
                        >
                            <div className="flex justify-between items-center mb-4">
                                <h3 className="font-semibold">{column.name}</h3>
                                <button
                                    onClick={() => deleteColumn(column.id)}
                                    className="text-red-500 hover:text-red-700 text-sm"
                                >
                                    ✕
                                </button>
                            </div>

                            {/* Cartes */}
                            <div className="space-y-2 mb-4">
                                {column.cards?.map((card) => (
                                    <div
                                        key={card.id}
                                        className="bg-white p-3 rounded shadow"
                                    >
                                        <div className="flex justify-between">
                                            <span>{card.title}</span>
                                            <button
                                                onClick={() => deleteCard(card.id)}
                                                className="text-red-500 hover:text-red-700 text-sm"
                                            >
                                                ✕
                                            </button>
                                        </div>
                                        {card.description && (
                                            <p className="text-gray-500 text-sm mt-1">
                                                {card.description}
                                            </p>
                                        )}
                                    </div>
                                ))}
                            </div>

                            {/* Formulaire nouvelle carte */}
                            {activeColumnId === column.id ? (
                                <form onSubmit={(e) => addCard(e, column.id)}>
                                    <input
                                        type="text"
                                        value={cardForm.data.title}
                                        onChange={(e) => cardForm.setData('title', e.target.value)}
                                        placeholder="Titre de la carte"
                                        className="w-full border rounded px-2 py-1 mb-2 text-sm"
                                        autoFocus
                                    />
                                    <div className="flex gap-2">
                                        <button
                                            type="submit"
                                            className="bg-blue-500 text-white px-2 py-1 rounded text-sm"
                                        >
                                            Ajouter
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => setActiveColumnId(null)}
                                            className="px-2 py-1 text-sm"
                                        >
                                            Annuler
                                        </button>
                                    </div>
                                </form>
                            ) : (
                                <button
                                    onClick={() => setActiveColumnId(column.id)}
                                    className="text-gray-500 hover:text-gray-700 text-sm"
                                >
                                    + Ajouter une carte
                                </button>
                            )}
                        </div>
                    ))}

                    {/* Nouvelle colonne */}
                    <div className="min-w-[280px]">
                        {showColumnForm ? (
                            <form onSubmit={addColumn} className="bg-gray-100 rounded p-4">
                                <input
                                    type="text"
                                    value={columnForm.data.name}
                                    onChange={(e) => columnForm.setData('name', e.target.value)}
                                    placeholder="Nom de la colonne"
                                    className="w-full border rounded px-2 py-1 mb-2"
                                    autoFocus
                                />
                                <div className="flex gap-2">
                                    <button
                                        type="submit"
                                        className="bg-blue-500 text-white px-3 py-1 rounded text-sm"
                                    >
                                        Ajouter
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setShowColumnForm(false)}
                                        className="px-3 py-1 text-sm"
                                    >
                                        Annuler
                                    </button>
                                </div>
                            </form>
                        ) : (
                            <button
                                onClick={() => setShowColumnForm(true)}
                                className="w-full bg-gray-200 hover:bg-gray-300 rounded p-4 text-gray-600"
                            >
                                + Ajouter une colonne
                            </button>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
