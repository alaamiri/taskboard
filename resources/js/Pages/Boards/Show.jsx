import { Head, useForm, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useState, useEffect } from 'react';
import {
    DndContext,
    closestCenter,
    PointerSensor,
    useSensor,
    useSensors,
    DragOverlay
} from '@dnd-kit/core';
import { arrayMove } from '@dnd-kit/sortable';

// Composants
import Column from '@/Components/Board/Column';
import ColumnForm from '@/Components/Board/ColumnForm';
import ImportForm from '@/Components/Board/ImportForm';

// Hooks
import useBoardChannel from '@/hooks/useBoardChannel';

export default function Show({ board }) {
    // State
    const [columns, setColumns] = useState(board.columns || []);
    const [showColumnForm, setShowColumnForm] = useState(false);
    const [showImportForm, setShowImportForm] = useState(false);
    const [activeColumnId, setActiveColumnId] = useState(null);
    const [activeCard, setActiveCard] = useState(null);

    // Forms
    const columnForm = useForm({ name: '' });
    const cardForm = useForm({ title: '', description: '' });
    const importForm = useForm({ file: null });

    // WebSocket
    useBoardChannel(board.id);

    // Sync columns with board
    useEffect(() => {
        setColumns(board.columns || []);
    }, [board]);

    // DnD Sensors
    const sensors = useSensors(
        useSensor(PointerSensor, {
            activationConstraint: { distance: 8 }
        })
    );

    // Actions
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

    // DnD Helpers
    function findColumnByCardId(cardId) {
        return columns.find(column =>
            column.cards?.some(card => `card-${card.id}` === cardId)
        );
    }

    function getCardNumericId(cardId) {
        return parseInt(cardId.replace('card-', ''));
    }

    function getColumnNumericId(columnId) {
        return parseInt(columnId.replace('column-', ''));
    }

    // DnD Handlers
    function handleDragStart(event) {
        const activeColumn = findColumnByCardId(event.active.id);
        if (activeColumn) {
            const card = activeColumn.cards.find(c => `card-${c.id}` === event.active.id);
            setActiveCard(card);
        }
    }

    function handleDragOver(event) {
        const { active, over } = event;
        if (!over) return;

        const activeId = active.id;
        const overId = over.id;

        if (activeId === overId) return;

        const isActiveCard = activeId.toString().startsWith('card-');
        const isOverCard = overId.toString().startsWith('card-');
        const isOverColumn = overId.toString().startsWith('column-');

        if (!isActiveCard) return;

        const activeColumn = findColumnByCardId(activeId);
        let overColumn = isOverCard
            ? findColumnByCardId(overId)
            : columns.find(col => col.id === getColumnNumericId(overId));

        if (!activeColumn || !overColumn || activeColumn.id === overColumn.id) return;

        setColumns(prev => {
            const activeCards = [...(activeColumn.cards || [])];
            const overCards = [...(overColumn.cards || [])];
            const activeCardIndex = activeCards.findIndex(c => `card-${c.id}` === activeId);

            if (activeCardIndex === -1) return prev;

            const [movedCard] = activeCards.splice(activeCardIndex, 1);
            let insertIndex = isOverCard
                ? overCards.findIndex(c => `card-${c.id}` === overId)
                : overCards.length;

            if (insertIndex === -1) insertIndex = overCards.length;
            overCards.splice(insertIndex, 0, movedCard);

            return prev.map(col => {
                if (col.id === activeColumn.id) return { ...col, cards: activeCards };
                if (col.id === overColumn.id) return { ...col, cards: overCards };
                return col;
            });
        });
    }

    function handleDragEnd(event) {
        const { active, over } = event;
        setActiveCard(null);

        if (!over) return;

        const activeId = active.id;
        const overId = over.id;
        const isActiveCard = activeId.toString().startsWith('card-');
        const isOverCard = overId.toString().startsWith('card-');

        if (!isActiveCard) return;

        const activeColumn = findColumnByCardId(activeId);

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
                                return { ...col, cards: arrayMove([...col.cards], oldIndex, newIndex) };
                            }
                            return col;
                        })
                    );
                }
            }
        }

        const cardNumericId = getCardNumericId(activeId);
        const currentColumn = columns.find(col =>
            col.cards?.some(c => c.id === cardNumericId)
        );

        if (currentColumn) {
            const newPosition = currentColumn.cards.findIndex(c => c.id === cardNumericId);
            router.patch(route('cards.move', cardNumericId), {
                column_id: currentColumn.id,
                position: newPosition >= 0 ? newPosition : 0
            }, { preserveScroll: true });
        }
    }

    return (
        <AuthenticatedLayout>
            <Head title={board.name} />

            <div className="p-6">
                {/* Header */}
                <div className="flex justify-between items-center mb-2">
                    <h1 className="text-2xl font-bold">{board.name}</h1>
                    <button
                        onClick={() => setShowImportForm(!showImportForm)}
                        className="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600"
                    >
                        Importer CSV
                    </button>
                </div>

                {/* Import Form */}
                {showImportForm && (
                    <ImportForm
                        form={importForm}
                        boardId={board.id}
                        onSuccess={() => {
                            setShowImportForm(false);
                            importForm.reset();
                        }}
                    />
                )}

                {/* Description */}
                {board.description && (
                    <p className="text-gray-600 mb-6">{board.description}</p>
                )}

                {/* Board */}
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
                                isAddingCard={activeColumnId === column.id}
                                onStartAddCard={setActiveColumnId}
                                onCancelAddCard={() => setActiveColumnId(null)}
                                cardForm={cardForm}
                            />
                        ))}

                        {/* New Column */}
                        <div className="min-w-[280px]">
                            {showColumnForm ? (
                                <ColumnForm
                                    form={columnForm}
                                    onSubmit={addColumn}
                                    onCancel={() => setShowColumnForm(false)}
                                />
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
                        {activeCard && (
                            <div className="bg-white p-3 rounded shadow-lg rotate-3 cursor-grabbing">
                                <span>{activeCard.title}</span>
                            </div>
                        )}
                    </DragOverlay>
                </DndContext>
            </div>
        </AuthenticatedLayout>
    );
}
