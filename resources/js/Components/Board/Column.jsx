import { useDroppable } from '@dnd-kit/core';
import { SortableContext, verticalListSortingStrategy } from '@dnd-kit/sortable';
import Card from './Card';
import CardForm from './CardForm';

export default function Column({ 
    column, 
    onDeleteColumn, 
    onDeleteCard, 
    onAddCard,
    isAddingCard,
    onStartAddCard,
    onCancelAddCard,
    cardForm
}) {
    const { setNodeRef, isOver } = useDroppable({
        id: `column-${column.id}`,
        data: { type: 'column', column }
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
                        <Card
                            key={card.id}
                            card={card}
                            onDelete={onDeleteCard}
                        />
                    ))}
                </div>
            </SortableContext>

            {isAddingCard ? (
                <CardForm
                    form={cardForm}
                    onSubmit={(e) => onAddCard(e, column.id)}
                    onCancel={onCancelAddCard}
                />
            ) : (
                <button
                    onClick={() => onStartAddCard(column.id)}
                    className="text-gray-500 hover:text-gray-700 text-sm"
                >
                    + Ajouter une carte
                </button>
            )}
        </div>
    );
}
