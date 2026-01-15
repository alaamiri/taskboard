import { Head, useForm, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useState, useEffect } from 'react';
import {
    DndContext,
    closestCenter,
    PointerSensor,
    useSensor,
    useSensors,
    DragOverlay,
    useDroppable
} from '@dnd-kit/core';
import {
    SortableContext,
    verticalListSortingStrategy,
    useSortable,
    arrayMove
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';

// Composant Card draggable
function SortableCard({ card, onDelete }) {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging
    } = useSortable({
        id: `card-${card.id}`,
        data: {
            type: 'card',
            card
        }
    });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
        opacity: isDragging ? 0.5 : 1
    };

    return (
        <div
            ref={setNodeRef}
            style={style}
            {...attributes}
            {...listeners}
            className="bg-white p-3 rounded shadow cursor-grab active:cursor-grabbing"
        >
            <div className="flex justify-between">
                <span>{card.title}</span>
                <button
                    onClick={(e) => {
                        e.stopPropagation();
                        onDelete(card.id);
                    }}
                    className="text-red-500 hover:text-red-700 text-sm"
                >
                    ✕
                </button>
            </div>
            {card.description && (
                <p className="text-gray-500 text-sm mt-1">{card.description}</p>
            )}
        </div>
    );
}

// Composant Column avec zone droppable
function Column({ column, onDeleteColumn, onDeleteCard, onAddCard, activeColumnId, setActiveColumnId, cardForm }) {
    const { setNodeRef, isOver } = useDroppable({
        id: `column-${column.id}`,
        data: {
            type: 'column',
            column
        }
    });

    const cardIds = column.cards?.map(card => `card-${card.id}`) || [];

    return (
        <div
            ref={setNodeRef}
            className={`bg-gray-100 rounded p-4 min-w-[280px] max-w-[280px] ${isOver ? 'ring-2 ring-blue-400' : ''}`}
        >
            <div className="flex justify-between items-center mb-4">
                <h3 className="font-semibold">{column.name}</h3>
                <button
                    onClick={() => onDeleteColumn(column.id)}
                    className="text-red-500 hover:text-red-700 text-sm"
                >
                    ✕
                </button>
            </div>

            <SortableContext items={cardIds} strategy={verticalListSortingStrategy}>
                <div className="space-y-2 mb-4 min-h-[50px]">
                    {column.cards?.map((card) => (
                        <SortableCard
                            key={card.id}
                            card={card}
                            onDelete={onDeleteCard}
                        />
                    ))}
                </div>
            </SortableContext>

            {activeColumnId === column.id ? (
                <form onSubmit={(e) => onAddCard(e, column.id)}>
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
    );
}

export default function Show({ board }) {
    const [columns, setColumns] = useState(board.columns || []);
    const [showColumnForm, setShowColumnForm] = useState(false);
    const [activeColumnId, setActiveColumnId] = useState(null);
    const [activeCard, setActiveCard] = useState(null);

    const columnForm = useForm({ name: '' });
    const cardForm = useForm({ title: '', description: '' });

    // Met à jour les colonnes quand board change
    useEffect(() => {
        setColumns(board.columns || []);
    }, [board]);

    const sensors = useSensors(
        useSensor(PointerSensor, {
            activationConstraint: {
                distance: 8
            }
        })
    );

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

    // Trouve la colonne qui contient une carte
    function findColumnByCardId(cardId) {
        return columns.find(column =>
            column.cards?.some(card => `card-${card.id}` === cardId)
        );
    }

    // Extrait l'ID numérique d'une carte
    function getCardNumericId(cardId) {
        return parseInt(cardId.replace('card-', ''));
    }

    // Extrait l'ID numérique d'une colonne
    function getColumnNumericId(columnId) {
        return parseInt(columnId.replace('column-', ''));
    }

    function handleDragStart(event) {
        const { active } = event;
        const activeColumn = findColumnByCardId(active.id);
        if (activeColumn) {
            const card = activeColumn.cards.find(c => `card-${c.id}` === active.id);
            setActiveCard(card);
        }
    }

    function handleDragOver(event) {
        const { active, over } = event;
        if (!over) return;

        const activeId = active.id;
        const overId = over.id;

        // Ignore si on survole le même élément
        if (activeId === overId) return;

        const isActiveCard = activeId.toString().startsWith('card-');
        const isOverCard = overId.toString().startsWith('card-');
        const isOverColumn = overId.toString().startsWith('column-');

        if (!isActiveCard) return;

        const activeColumn = findColumnByCardId(activeId);
        let overColumn = null;

        if (isOverCard) {
            overColumn = findColumnByCardId(overId);
        } else if (isOverColumn) {
            overColumn = columns.find(col => col.id === getColumnNumericId(overId));
        }

        if (!activeColumn || !overColumn) return;

        // Si on déplace vers une autre colonne
        if (activeColumn.id !== overColumn.id) {
            setColumns(prev => {
                const activeCards = [...(activeColumn.cards || [])];
                const overCards = [...(overColumn.cards || [])];

                const activeCardIndex = activeCards.findIndex(c => `card-${c.id}` === activeId);
                if (activeCardIndex === -1) return prev;

                const [movedCard] = activeCards.splice(activeCardIndex, 1);

                // Trouve la position d'insertion
                let insertIndex = overCards.length;
                if (isOverCard) {
                    insertIndex = overCards.findIndex(c => `card-${c.id}` === overId);
                    if (insertIndex === -1) insertIndex = overCards.length;
                }

                overCards.splice(insertIndex, 0, movedCard);

                return prev.map(col => {
                    if (col.id === activeColumn.id) {
                        return { ...col, cards: activeCards };
                    }
                    if (col.id === overColumn.id) {
                        return { ...col, cards: overCards };
                    }
                    return col;
                });
            });
        }
    }

    function handleDragEnd(event) {
        const { active, over } = event;
        setActiveCard(null);

        if (!over) return;

        const activeId = active.id;
        const overId = over.id;

        const isActiveCard = activeId.toString().startsWith('card-');
        const isOverCard = overId.toString().startsWith('card-');
        const isOverColumn = overId.toString().startsWith('column-');

        if (!isActiveCard) return;

        const activeColumn = findColumnByCardId(activeId);

        // Réorganisation dans la même colonne
        if (isOverCard && activeColumn) {
            const overColumnSame = findColumnByCardId(overId);

            if (overColumnSame && activeColumn.id === overColumnSame.id) {
                const cardIds = activeColumn.cards.map(c => `card-${c.id}`);
                const oldIndex = cardIds.indexOf(activeId);
                const newIndex = cardIds.indexOf(overId);

                if (oldIndex !== -1 && newIndex !== -1 && oldIndex !== newIndex) {
                    setColumns(prev =>
                        prev.map(col => {
                            if (col.id === activeColumn.id) {
                                return {
                                    ...col,
                                    cards: arrayMove([...col.cards], oldIndex, newIndex)
                                };
                            }
                            return col;
                        })
                    );
                }
            }
        }

        // Envoie la mise à jour au serveur
        const cardNumericId = getCardNumericId(activeId);
        const currentColumn = columns.find(col =>
            col.cards?.some(c => c.id === cardNumericId)
        );

        if (currentColumn) {
            const newPosition = currentColumn.cards.findIndex(c => c.id === cardNumericId);

            router.patch(route('cards.move', cardNumericId), {
                column_id: currentColumn.id,
                position: newPosition >= 0 ? newPosition : 0
            }, {
                preserveScroll: true
            });
        }
    }

    return (
        <AuthenticatedLayout>
            <Head title={board.name} />

            <div className="p-6">
                <h1 className="text-2xl font-bold mb-2">{board.name}</h1>
                {board.description && (
                    <p className="text-gray-600 mb-6">{board.description}</p>
                )}

                <DndContext
                    sensors={sensors}
                    collisionDetection={closestCenter}
                    onDragStart={handleDragStart}
                    onDragOver={handleDragOver}
                    onDragEnd={handleDragEnd}
                >
                    <div className="flex gap-4 overflow-x-auto pb-4">
                        {columns.map((column) => (
                            <Column
                                key={column.id}
                                column={column}
                                onDeleteColumn={deleteColumn}
                                onDeleteCard={deleteCard}
                                onAddCard={addCard}
                                activeColumnId={activeColumnId}
                                setActiveColumnId={setActiveColumnId}
                                cardForm={cardForm}
                            />
                        ))}

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

                    <DragOverlay>
                        {activeCard ? (
                            <div className="bg-white p-3 rounded shadow-lg rotate-3 cursor-grabbing">
                                <span>{activeCard.title}</span>
                            </div>
                        ) : null}
                    </DragOverlay>
                </DndContext>
            </div>
        </AuthenticatedLayout>
    );
}
